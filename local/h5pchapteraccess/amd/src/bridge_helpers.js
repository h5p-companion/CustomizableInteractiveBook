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
 * Pure validation helpers for the H5P host bridge.
 *
 * @module     local_h5pchapteraccess/bridge_helpers
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const CONTRACT_VERSION = 1;
export const LIBRARY_NAME = 'H5P.CustomizableInteractiveBook';
export const POLICY_MESSAGE_TYPE = 'h5p-customizable-interactive-book:policy';
export const READY_MESSAGE_TYPE = 'h5p-customizable-interactive-book:ready';
const MAX_REQUEST_IDS = 100;

/**
 * Validate the non-DOM fields and same-origin requirement of a ready event.
 *
 * Chapter titles and IDs announced by the iframe are deliberately ignored.
 *
 * @param {MessageEvent|Object} event Message event
 * @param {string} expectedOrigin Moodle page origin
 * @returns {boolean}
 */
export const isValidReadyMessage = (event, expectedOrigin) => {
    const data = event?.data;
    const numericContentId = Number(data?.contentId);
    return event?.origin === expectedOrigin &&
        event?.source !== null &&
        typeof event?.source === 'object' &&
        data !== null &&
        typeof data === 'object' &&
        data.type === READY_MESSAGE_TYPE &&
        data.contractVersion === CONTRACT_VERSION &&
        data.library === LIBRARY_NAME &&
        typeof data.requestId === 'string' &&
        data.requestId.length > 0 &&
        data.requestId.length <= 200 &&
        typeof data.contentId === 'string' &&
        /^[1-9][0-9]*$/.test(data.contentId) &&
        Number.isSafeInteger(numericContentId) &&
        numericContentId > 0;
};

/**
 * Locate the one H5P iframe matching both the trusted DOM content ID and source window.
 *
 * @param {Document|Object} documentRef Parent document
 * @param {Window|Object} sourceWindow Message source window
 * @param {string} contentId H5P content ID
 * @returns {HTMLIFrameElement|null}
 */
export const findSourceIframe = (documentRef, sourceWindow, contentId) => {
    const iframes = documentRef.querySelectorAll('iframe.h5p-iframe[data-content-id]');
    for (const iframe of iframes) {
        if (iframe.dataset.contentId === contentId && iframe.contentWindow === sourceWindow) {
            return iframe;
        }
    }
    return null;
};

/**
 * Parse and reduce an AJAX response to contract version 1 fields only.
 *
 * @param {Object} response Moodle external function response
 * @param {string} expectedContentId Content ID announced by the verified iframe
 * @returns {Object|null}
 */
export const parsePolicyResponse = (response, expectedContentId) => {
    if (!response || typeof response.policy !== 'string') {
        return null;
    }

    let policy;
    try {
        policy = JSON.parse(response.policy);
    } catch {
        return null;
    }

    if (!policy ||
        typeof policy !== 'object' ||
        policy.contractVersion !== CONTRACT_VERSION ||
        String(policy.contentId) !== expectedContentId ||
        typeof policy.required !== 'boolean' ||
        typeof policy.teacherBypass !== 'boolean' ||
        !policy.chapters ||
        typeof policy.chapters !== 'object' ||
        Array.isArray(policy.chapters)
    ) {
        return null;
    }

    const chapters = Object.create(null);
    for (const [chapterId, rule] of Object.entries(policy.chapters)) {
        if (!rule || typeof rule !== 'object' || typeof rule.available !== 'boolean') {
            return null;
        }
        chapters[chapterId] = {
            available: rule.available,
            message: typeof rule.message === 'string' ? rule.message : '',
        };
    }

    return {
        contractVersion: CONTRACT_VERSION,
        contentId: expectedContentId,
        required: policy.required,
        teacherBypass: policy.teacherBypass,
        chapters,
    };
};

/**
 * Tracks request IDs that have already started processing.
 */
export class RequestRegistry {
    constructor() {
        this.requestIds = new Set();
    }

    /**
     * Mark an ID once.
     *
     * @param {string} requestId Correlation ID
     * @returns {boolean} True only for the first call
     */
    add(requestId) {
        if (this.requestIds.has(requestId) || this.requestIds.size >= MAX_REQUEST_IDS) {
            return false;
        }
        this.requestIds.add(requestId);
        return true;
    }
}
