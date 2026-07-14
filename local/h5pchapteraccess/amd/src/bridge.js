// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Same-origin postMessage bridge between Moodle and the H5P Interactive Book.
 *
 * @module     local_h5pchapteraccess/bridge
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {
    findSourceIframe,
    isValidReadyMessage,
    parsePolicyResponse,
    POLICY_MESSAGE_TYPE,
    RequestRegistry,
} from './bridge_helpers';

const activeBridges = new Map();

/**
 * Request the server-side policy for one verified H5P content ID.
 *
 * @param {number} cmid Course module ID injected by Moodle
 * @param {string} contentId H5P content ID from the verified iframe
 * @returns {Promise}
 */
const requestPolicy = (cmid, contentId) => fetchMany([{
    methodname: 'local_h5pchapteraccess_get_policy',
    args: {
        cmid,
        contentid: Number(contentId),
    },
}])[0];

/**
 * One bridge instance for one Moodle activity embed page.
 */
export class PolicyBridge {
    /**
     * @param {number} cmid Course module ID
     * @param {Object} options Testable browser dependencies
     */
    constructor(cmid, options = {}) {
        this.cmid = cmid;
        this.window = options.windowRef || window;
        this.document = options.documentRef || document;
        this.fetchPolicy = options.fetchPolicy || requestPolicy;
        this.requests = new RequestRegistry();
        this.disposed = false;

        this.handleMessage = this.handleMessage.bind(this);
        this.dispose = this.dispose.bind(this);
    }

    /**
     * Register browser listeners.
     */
    start() {
        this.window.addEventListener('message', this.handleMessage);
        this.window.addEventListener('pagehide', this.dispose, {once: true});
    }

    /**
     * Validate a ready message, fetch policy, and answer the exact source iframe.
     *
     * @param {MessageEvent} event Browser message event
     */
    handleMessage(event) {
        if (this.disposed || !isValidReadyMessage(event, this.window.location.origin)) {
            return;
        }

        const iframe = findSourceIframe(this.document, event.source, event.data.contentId);
        if (!iframe || !this.requests.add(event.data.requestId)) {
            return;
        }

        const requestId = event.data.requestId;
        const contentId = event.data.contentId;
        this.fetchPolicy(this.cmid, contentId)
            .then(response => parsePolicyResponse(response, contentId))
            .then(policy => {
                if (!policy || this.disposed || iframe.contentWindow !== event.source) {
                    return null;
                }
                event.source.postMessage({
                    type: POLICY_MESSAGE_TYPE,
                    contractVersion: policy.contractVersion,
                    requestId,
                    contentId,
                    required: policy.required,
                    teacherBypass: policy.teacherBypass,
                    chapters: policy.chapters,
                }, event.origin);
                return policy;
            })
            .catch(() => {
                // The H5P-side timeout provides the safe allow-all fallback.
                return null;
            });
    }

    /**
     * Release browser listeners when the page is discarded.
     */
    dispose() {
        if (this.disposed) {
            return;
        }
        this.disposed = true;
        this.window.removeEventListener('message', this.handleMessage);
        this.window.removeEventListener('pagehide', this.dispose);
        activeBridges.delete(this.cmid);
    }
}

/**
 * Initialize one bridge for the current activity.
 *
 * @param {number} cmid Course module ID
 */
export const init = cmid => {
    cmid = Number(cmid);
    if (!Number.isInteger(cmid) || cmid <= 0 || activeBridges.has(cmid)) {
        return;
    }

    const bridge = new PolicyBridge(cmid);
    activeBridges.set(cmid, bridge);
    bridge.start();
};
