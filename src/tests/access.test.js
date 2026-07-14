import assert from 'node:assert/strict';
import test from 'node:test';

import createChapterManifest, { FALLBACK_TITLE } from '../src/scripts/access/chapter-manifest.js';
import AccessPolicy from '../src/scripts/access/access-policy.js';
import AccessController from '../src/scripts/access/access-controller.js';
import {
  createAvailableChapterInstance,
  getPreviousChapterState,
  sumAvailableChapterMetric
} from '../src/scripts/access/chapter-runtime.js';
import HostBridge, {
  CONTRACT_VERSION,
  POLICY_MESSAGE_TYPE,
  READY_MESSAGE_TYPE
} from '../src/scripts/access/host-bridge.js';

const manifest = () => createChapterManifest([
  { subContentId: 'uuid-a', metadata: { title: 'First' } },
  { subContentId: 'uuid-b', metadata: { title: 'Second' } },
  { subContentId: 'uuid-c', metadata: { title: 'Third' } }
]);

const policy = (chapterRules = {}) => new AccessPolicy({
  contractVersion: 1,
  required: true,
  teacherBypass: false,
  chapters: chapterRules
}, manifest());

const createFakeBrowser = () => {
  const listeners = new Map();
  const posted = [];
  const parent = {
    postMessage: (message, origin) => posted.push({ message, origin })
  };
  const windowRef = {
    parent,
    crypto: { randomUUID: () => 'request-uuid' },
    setTimeout,
    clearTimeout,
    setInterval,
    clearInterval,
    addEventListener: (type, listener) => {
      if (!listeners.has(type)) {
        listeners.set(type, new Set());
      }
      listeners.get(type).add(listener);
    },
    removeEventListener: (type, listener) => listeners.get(type)?.delete(listener)
  };
  return {
    documentRef: { referrer: 'https://moodle.example/mod/h5pactivity/view.php?id=7' },
    listeners,
    parent,
    posted,
    windowRef
  };
};

const validPolicyMessage = (bridge, parent, overrides = {}) => ({
  source: parent,
  origin: 'https://moodle.example',
  data: {
    type: POLICY_MESSAGE_TYPE,
    contractVersion: CONTRACT_VERSION,
    requestId: bridge.requestId,
    contentId: '42',
    required: true,
    teacherBypass: false,
    chapters: {
      'uuid-a': { available: false, message: 'Plain message' }
    },
    ...overrides
  }
});

test('chapter manifest creates immutable stable entries', () => {
  const chapters = [{ subContentId: ' uuid-a ', metadata: { title: ' Chapter ' } }];
  const result = createChapterManifest(chapters);

  assert.deepEqual(result[0], {
    id: 'uuid-a',
    title: 'Chapter',
    position: 0,
    stable: true
  });
  assert.ok(Object.isFrozen(result));
  assert.ok(Object.isFrozen(result[0]));
  assert.equal(chapters[0].subContentId, ' uuid-a ');
});

test('chapter manifest creates a controlled legacy ID and fallback title', () => {
  const result = createChapterManifest([{}]);

  assert.equal(result[0].id, 'legacy-position-0');
  assert.equal(result[0].title, FALLBACK_TITLE);
  assert.equal(result[0].stable, false);
});

test('chapter manifest rejects duplicate IDs', () => {
  assert.throws(() => createChapterManifest([
    { subContentId: 'duplicate' },
    { subContentId: 'duplicate' }
  ]), /Duplicate chapter id/);
});

test('allowAll exposes every manifest chapter', () => {
  const controller = new AccessController(manifest(), AccessPolicy.allowAll(manifest()));

  assert.deepEqual(controller.getAvailableIds(), ['uuid-a', 'uuid-b', 'uuid-c']);
  assert.equal(controller.getAvailableCount(), 3);
});

test('policy normalizes invalid values and missing chapters safely', () => {
  const normalized = new AccessPolicy({
    contractVersion: 'wrong',
    required: 1,
    teacherBypass: 'yes',
    chapters: {
      'uuid-a': { available: 'no', message: { html: '<b>unsafe</b>' } },
      'uuid-b': { available: false, message: 'Plain text' }
    }
  }, manifest());

  assert.equal(normalized.contractVersion, 1);
  assert.equal(normalized.required, false);
  assert.equal(normalized.teacherBypass, false);
  assert.deepEqual(normalized.chapters['uuid-a'], { available: true, message: '' });
  assert.deepEqual(normalized.chapters['uuid-b'], { available: false, message: 'Plain text' });
  assert.deepEqual(normalized.chapters['uuid-c'], { available: true, message: '' });
});

test('next and previous navigation skip locked chapters', () => {
  const controller = new AccessController(manifest(), policy({
    'uuid-b': { available: false, message: '' }
  }));

  assert.equal(controller.getNextAvailableId('uuid-a'), 'uuid-c');
  assert.equal(controller.getPreviousAvailableId('uuid-c'), 'uuid-a');
  assert.equal(controller.getPreviousAvailableId('uuid-a'), null);
});

test('all blocked chapters produce no available navigation target', () => {
  const controller = new AccessController(manifest(), policy({
    'uuid-a': { available: false },
    'uuid-b': { available: false },
    'uuid-c': { available: false }
  }));

  assert.equal(controller.hasAvailableChapters(), false);
  assert.equal(controller.getAvailableCount(), 0);
  assert.equal(controller.getNextAvailableId('uuid-a'), null);
});

test('locked chapter never invokes its runnable factory', () => {
  let calls = 0;
  const instance = createAvailableChapterInstance({ available: false, locked: true }, () => {
    calls += 1;
    return {};
  });

  assert.equal(instance, null);
  assert.equal(calls, 0);
});

test('score aggregation excludes locked and summary chapters', () => {
  const chapters = [
    { available: true, locked: false, isSummary: false, instance: { getScore: () => 4 } },
    { available: false, locked: true, isSummary: false, instance: { getScore: () => 100 } },
    { available: true, locked: false, isSummary: true, instance: { getScore: () => 50 } },
    { available: true, locked: false, isSummary: false, instance: { getScore: () => 3 } }
  ];

  assert.equal(sumAvailableChapterMetric(chapters, 'getScore'), 7);
});

test('previous state is restored by UUID and not by a reordered position', () => {
  const previousState = {
    chaptersById: {
      'uuid-a': { state: { answer: 'A' } },
      'uuid-b': { state: { answer: 'B' } }
    },
    chapters: [{ id: 'uuid-a', state: { answer: 'legacy' } }]
  };

  assert.deepEqual(
    getPreviousChapterState(previousState, { id: 'uuid-b', position: 0 }),
    { state: { answer: 'B' } }
  );
  assert.equal(getPreviousChapterState(previousState, { id: 'uuid-c', position: 0 }), null);
});

test('host bridge sends the exact ready contract to the referrer origin', async () => {
  const browser = createFakeBrowser();
  const bridge = new HostBridge(42, {
    ...browser,
    timeoutMs: 20,
    retryIntervalMs: 50,
    maxAttempts: 1
  });
  const waiting = bridge.requestPolicy(manifest());

  assert.equal(browser.posted.length, 1);
  assert.equal(browser.posted[0].origin, 'https://moodle.example');
  assert.equal(browser.posted[0].message.type, READY_MESSAGE_TYPE);
  assert.equal(browser.posted[0].message.contractVersion, CONTRACT_VERSION);
  assert.equal(browser.posted[0].message.requestId, bridge.requestId);
  assert.equal(browser.posted[0].message.contentId, '42');
  bridge.dispose();
  await waiting;
});

test('host bridge ignores invalid origin, source, requestId, contentId, type and version', async () => {
  const cases = [
    event => ({ ...event, origin: 'https://attacker.example' }),
    event => ({ ...event, source: {} }),
    event => ({ ...event, data: { ...event.data, requestId: 'wrong' } }),
    event => ({ ...event, data: { ...event.data, contentId: '99' } }),
    event => ({ ...event, data: { ...event.data, type: READY_MESSAGE_TYPE } }),
    event => ({ ...event, data: { ...event.data, contractVersion: 2 } })
  ];

  for (const mutate of cases) {
    const browser = createFakeBrowser();
    const bridge = new HostBridge(42, {
      ...browser,
      timeoutMs: 5,
      retryIntervalMs: 50,
      maxAttempts: 1
    });
    const waiting = bridge.requestPolicy(manifest());
    bridge.handleMessage(mutate(validPolicyMessage(bridge, browser.parent)));
    const result = await waiting;
    assert.equal(result.required, false);
    assert.equal(result.chapters['uuid-a'].available, true);
  }
});

test('host bridge timeout falls back to allow-all and removes listeners', async () => {
  const browser = createFakeBrowser();
  const bridge = new HostBridge(42, {
    ...browser,
    timeoutMs: 5,
    retryIntervalMs: 50,
    maxAttempts: 1
  });

  const result = await bridge.requestPolicy(manifest());

  assert.equal(result.required, false);
  assert.equal(result.chapters['uuid-a'].available, true);
  assert.equal(browser.listeners.get('message')?.size || 0, 0);
});

test('host bridge dispose resolves safely and clears timers and listener', async () => {
  const browser = createFakeBrowser();
  const bridge = new HostBridge(42, {
    ...browser,
    timeoutMs: 100,
    retryIntervalMs: 50,
    maxAttempts: 2
  });
  const waiting = bridge.requestPolicy(manifest());

  bridge.dispose();
  const result = await waiting;

  assert.equal(result.required, false);
  assert.equal(browser.listeners.get('message')?.size || 0, 0);
  assert.equal(bridge.retryTimer, null);
  assert.equal(bridge.timeoutTimer, null);
});

test('host bridge accepts one matching policy and preserves plain message text', async () => {
  const browser = createFakeBrowser();
  const bridge = new HostBridge(42, {
    ...browser,
    timeoutMs: 100,
    retryIntervalMs: 50,
    maxAttempts: 1
  });
  const waiting = bridge.requestPolicy(manifest());

  bridge.handleMessage(validPolicyMessage(bridge, browser.parent));
  const result = await waiting;

  assert.equal(result.required, true);
  assert.equal(result.chapters['uuid-a'].available, false);
  assert.equal(result.chapters['uuid-a'].message, 'Plain message');
  assert.equal(browser.listeners.get('message')?.size || 0, 0);
});

test('host bridge resolves only once when duplicate policy responses arrive', async () => {
  const browser = createFakeBrowser();
  const bridge = new HostBridge(42, {
    ...browser,
    timeoutMs: 100,
    retryIntervalMs: 50,
    maxAttempts: 1
  });
  const waiting = bridge.requestPolicy(manifest());
  const first = validPolicyMessage(bridge, browser.parent);
  const duplicate = validPolicyMessage(bridge, browser.parent, {
    chapters: { 'uuid-a': { available: true, message: 'Duplicate' } }
  });

  bridge.handleMessage(first);
  bridge.handleMessage(duplicate);
  const result = await waiting;

  assert.equal(result.chapters['uuid-a'].available, false);
  assert.equal(result.chapters['uuid-a'].message, 'Plain message');
});
