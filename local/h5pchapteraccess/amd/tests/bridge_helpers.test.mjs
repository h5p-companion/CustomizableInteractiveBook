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

import assert from 'node:assert/strict';
import test from 'node:test';
import {
    findSourceIframe,
    isValidReadyMessage,
    parsePolicyResponse,
    RequestRegistry,
} from '../src/bridge_helpers.js';

const readyEvent = overrides => ({
    origin: 'https://moodle.example',
    source: {},
    data: {
        type: 'h5p-customizable-interactive-book:ready',
        contractVersion: 1,
        requestId: 'request-1',
        contentId: '42',
        library: 'H5P.CustomizableInteractiveBook',
    },
    ...overrides,
});

test('ready validation rejects an invalid origin', () => {
    assert.equal(isValidReadyMessage(readyEvent(), 'https://other.example'), false);
});

test('ready validation rejects another message type', () => {
    const event = readyEvent();
    event.data.type = 'untrusted:ready';
    assert.equal(isValidReadyMessage(event, event.origin), false);
});

test('ready validation rejects an unsafe content ID', () => {
    const event = readyEvent();
    event.data.contentId = '999999999999999999999999';
    assert.equal(isValidReadyMessage(event, event.origin), false);
});

test('iframe lookup requires both content ID and contentWindow', () => {
    const source = {};
    const wrongSource = {};
    const documentRef = {
        querySelectorAll: () => [
            {dataset: {contentId: '42'}, contentWindow: wrongSource},
            {dataset: {contentId: '99'}, contentWindow: source},
        ],
    };
    assert.equal(findSourceIframe(documentRef, source, '42'), null);
});

test('iframe lookup accepts the exact source iframe', () => {
    const source = {};
    const iframe = {dataset: {contentId: '42'}, contentWindow: source};
    const documentRef = {querySelectorAll: () => [iframe]};
    assert.equal(findSourceIframe(documentRef, source, '42'), iframe);
});

test('request registry rejects a duplicate request ID', () => {
    const requests = new RequestRegistry();
    assert.equal(requests.add('request-1'), true);
    assert.equal(requests.add('request-1'), false);
});

test('request registry has a bounded lifetime set', () => {
    const registry = new RequestRegistry();
    for (let index = 0; index < 100; index++) {
        assert.equal(registry.add(`request-${index}`), true);
    }
    assert.equal(registry.add('request-over-limit'), false);
});

test('policy parsing rejects a content ID from another H5P', () => {
    const response = {
        policy: JSON.stringify({
            contractVersion: 1,
            contentId: 99,
            required: true,
            teacherBypass: false,
            chapters: {},
        }),
    };
    assert.equal(parsePolicyResponse(response, '42'), null);
});

test('policy parsing returns contract fields only', () => {
    const response = {
        policy: JSON.stringify({
            contractVersion: 1,
            contentId: 42,
            required: true,
            teacherBypass: false,
            chapters: {'uuid-a': {available: false, message: 'Locked'}},
            availabilityjson: 'must not pass',
        }),
    };
    assert.deepEqual(
        JSON.parse(JSON.stringify(parsePolicyResponse(response, '42'))),
        {
            contractVersion: 1,
            contentId: '42',
            required: true,
            teacherBypass: false,
            chapters: {'uuid-a': {available: false, message: 'Locked'}},
        }
    );
});
