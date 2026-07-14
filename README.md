# H5P.CustomizableInteractiveBook

`H5P.CustomizableInteractiveBook` is an Interactive Book variant with a host-controlled chapter access layer. A hosting platform decides which chapters are available; the H5P library receives a versioned policy and applies it to rendering, navigation, state, scoring, progress, completion, summary, and xAPI.

The library is platform-neutral. It does not know about Moodle courses, groups, grades, completion rules, database records, internal URLs, or PHP classes. The companion Moodle implementation is `local_h5pchapteraccess`.

## Repository layout

- `src/`: buildable H5P library source and package metadata;
- `src/src/scripts/access/`: chapter manifest, policy, controller, host bridge, and testable runtime helpers;
- `src/src/scripts/`: Interactive Book application and UI components;
- `src/src/styles/`: SCSS sources;
- `src/tests/`: Node test suite;
- `local/h5pchapteraccess/`: companion Moodle 4.5 plugin;
- `docs/architecture.md`: end-to-end architecture and trust boundaries;
- `docs/test-plan.md`: automated and manual test matrix.

## Chapter manifest

The manifest is created from `config.chapters` before child H5P instances are built. It is immutable and contains only host-facing chapter metadata:

```json
[
  {
    "id": "15f0a80e-4ba1-4b51-9c4a-113e986474a8",
    "title": "Introduction",
    "position": 0,
    "stable": true
  }
]
```

`id` is the chapter `subContentId`, never its numeric position. Legacy content without a usable `subContentId` receives a runtime-only `legacy-position-N` identifier and `stable: false`. Duplicate identifiers stop initialization instead of allowing ambiguous state or policy application. Titles are display metadata and are never identifiers.

## AccessPolicy

`AccessPolicy` normalizes an external policy into an immutable chapter map. It contains:

- `contractVersion`;
- `required`;
- `teacherBypass`;
- `chapters[id].available`;
- `chapters[id].message` as plain text.

Invalid optional values are normalized conservatively. A manifest chapter omitted from the policy remains available for forward compatibility. `AccessPolicy.allowAll(manifest)` is the standalone fallback.

## AccessController

`AccessController` is the domain API used by the book. It answers availability and message queries and calculates the available sequence independently from the original numeric order. It provides available IDs/count, visible positions, and next/previous available chapter lookup. UI components do not parse the host policy directly.

## HostBridge

`HostBridge` is the only H5P component that communicates with the immediate parent window. It:

- creates a `requestId` with `crypto.randomUUID()`, `crypto.getRandomValues()`, or a runtime fallback;
- derives the exact parent origin from `document.referrer`;
- sends `ready` only to that origin;
- retries during a short initialization window;
- validates `event.source`, `event.origin`, message type, contract version, request ID, and content ID;
- removes message listeners and timers after success, timeout, or `dispose()`;
- falls back to `allowAll` after approximately 2.5 seconds.

The bridge never uses `targetOrigin: "*"`.

## postMessage contract version 1

H5P sends:

```json
{
  "type": "h5p-customizable-interactive-book:ready",
  "contractVersion": 1,
  "requestId": "7de9f73b-eef1-4d6a-a582-d291c9834894",
  "contentId": "123",
  "library": "H5P.CustomizableInteractiveBook",
  "chapters": [
    {
      "id": "15f0a80e-4ba1-4b51-9c4a-113e986474a8",
      "title": "Introduction",
      "position": 0,
      "stable": true
    }
  ]
}
```

The host replies to the verified source window and origin:

```json
{
  "type": "h5p-customizable-interactive-book:policy",
  "contractVersion": 1,
  "requestId": "7de9f73b-eef1-4d6a-a582-d291c9834894",
  "contentId": "123",
  "required": true,
  "teacherBypass": false,
  "chapters": {
    "15f0a80e-4ba1-4b51-9c4a-113e986474a8": {
      "available": false,
      "message": "Complete the prerequisite activity."
    }
  }
}
```

Contract fields are case-sensitive. The response must repeat the exact `requestId` and string `contentId`. Messages are plain text. Platform rules and internal records are outside the contract.

## Initialization flow

1. The constructor sanitizes configuration and creates the chapter manifest.
2. `HostBridge.requestPolicy()` starts before `PageContent` or child H5P runtimes exist.
3. `attach()` may display an accessible loading status while the policy is pending.
4. A valid policy, or the timeout fallback, creates `AccessController`.
5. `initializeRuntime()` runs exactly once and creates the cover, page content, sidebar, and status bars.
6. Available chapters create their child instance with `H5P.newRunnable`.
7. Locked chapters create only an accessible placeholder.

Outside an iframe, without a determinable parent origin, without the Moodle plugin, or after an invalid/failed response, every chapter becomes available after the fallback timeout.

## Locked chapter behavior

A locked chapter remains selectable in the sidebar so the learner can read its explanation. Its placeholder uses an accessible status/focus structure and inserts the host message with `textContent`.

For a locked chapter:

- `H5P.newRunnable` is not called;
- no child library is initialized and no events are bubbled;
- `instance` remains `null` and `sections` remains empty;
- sequential next/previous navigation skips it;
- direct menu, hash, or restored-state selection shows only the placeholder;
- it is excluded from score, maximum score, answer-given checks, progress, completion, summary, reset, solutions, state changes, and xAPI.

If all chapters are locked, the first placeholder is shown, the summary is not created, scores are zero, and the book is not automatically completed.

## State by UUID

Current state is stored primarily in `chaptersById`, keyed by `subContentId`:

```json
{
  "chaptersById": {
    "15f0a80e-4ba1-4b51-9c4a-113e986474a8": {
      "completed": false,
      "tasksLeft": 1,
      "sections": [],
      "state": {}
    }
  },
  "chapters": [],
  "score": 0,
  "maxScore": 0,
  "urlFragments": {}
}
```

The legacy `chapters` array is read only as a compatibility fallback. Position fallback is never used to associate a UUID-bearing chapter after reordering. State already stored for a currently locked UUID is preserved unchanged so it can be restored if the chapter becomes available later.

## Scoring, progress, completion, summary, and xAPI

Only available, non-summary chapters with a valid child instance participate in score, maximum score, answers, state collection, solutions, reset, xAPI aggregation, and completion. Completion means that every available non-summary chapter is complete. The status bars count available chapters only; an intentionally opened locked placeholder shows a textual locked state without advancing progress.

The summary is created only when enabled and at least one content chapter is available. It contains no locked chapter tasks. Per-chapter xAPI and final summary submission ignore locked or uninitialized chapters.

## Build

Requirements: a supported Node.js release and npm.

```bash
cd src
npm ci
npm run build
```

Production assets are generated in `src/dist/`. Do not edit generated files manually. `node_modules/` is development-only and must not be distributed in an H5P library package.

Development mode:

```bash
npm run dev
npm run watch
```

## Tests and lint

```bash
cd src
npm test
npm run lint
npm run build
```

The Node suite covers manifest identity and immutability, legacy and duplicate IDs, policy normalization, available navigation, all-locked behavior, host-message validation, timeout/disposal, duplicate responses, locked runtime prevention, score filtering, and state restoration by UUID.

The complete cross-project matrix is in [docs/test-plan.md](docs/test-plan.md).

## Security limits

The Moodle host does not trust the manifest sent by the browser; it independently extracts the deployed H5P package and sends only the resulting per-chapter decision. The H5P side validates the immediate parent but cannot authenticate the business meaning of a policy beyond the verified browser origin and correlation fields. The allow-all timeout is intentional to preserve standalone H5P behavior and is therefore fail-open when no host responds.

**Chapter blocking controls display, child-library initialization, and navigation, but the H5P package still contains the original parameters of every chapter. This solution must not be presented as a protection mechanism for confidential information.**

Use server-side authorization and separate protected resources for confidential material.

## License

MIT. See [LICENSE](src/LICENSE).
