# H5P.CustomizableInteractiveBook

Interactive Book library with a platform-neutral, host-controlled chapter policy. The host decides availability; H5P applies the decision without knowing Moodle concepts.

## Access architecture

`src/scripts/access/` contains:

- `chapter-manifest.js`: immutable `subContentId` manifest with controlled legacy fallback and duplicate detection;
- `access-policy.js`: normalized immutable policy and `allowAll` factory;
- `access-controller.js`: availability, messages, visible positions, and available next/previous navigation;
- `host-bridge.js`: version 1 `postMessage` exchange with exact parent-origin/source validation, retries, timeout, and disposal;
- `chapter-runtime.js`: guarded runtime, score, and UUID-state helpers.

The manifest schema is:

```json
[{"id":"chapter-uuid","title":"Chapter","position":0,"stable":true}]
```

Chapter position and title are not identifiers. Legacy content receives `legacy-position-N` with `stable: false` for the current runtime only.

## postMessage version 1

H5P sends:

```json
{
  "type": "h5p-customizable-interactive-book:ready",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "123",
  "library": "H5P.CustomizableInteractiveBook",
  "chapters": [{"id":"chapter-uuid","title":"Chapter","position":0,"stable":true}]
}
```

The host replies:

```json
{
  "type": "h5p-customizable-interactive-book:policy",
  "contractVersion": 1,
  "requestId": "...",
  "contentId": "123",
  "required": true,
  "teacherBypass": false,
  "chapters": {
    "chapter-uuid": {"available": false, "message": "Plain-text explanation"}
  }
}
```

The exact `event.source`, origin, type, version, `requestId`, and `contentId` are required. `targetOrigin: "*"` is never used. Host messages are rendered as text, not HTML.

## Runtime flow

The constructor sanitizes configuration, creates the manifest, and requests a policy before constructing `PageContent`. `attach()` may show an accessible loading indicator. A valid policy or the approximately 2.5-second `allowAll` fallback creates `AccessController`, then the cover, content, sidebar, and status bars exactly once.

If the library runs outside Moodle, outside an iframe, without a known referrer origin, or without a responding plugin, every chapter remains available after the timeout.

Available chapters create their real child with `H5P.newRunnable`. Locked chapters create only an accessible placeholder and keep `instance = null`; they do not bubble events or expose internal sections. The book opens at the first available chapter by default. A saved chapter is restored if it is still available; otherwise startup uses the first available chapter. Menu/hash selection may show a locked placeholder, while sequential navigation skips it. If all chapters are locked, startup shows the first placeholder.

## State and results

State is keyed primarily by UUID in `chaptersById`. The legacy positional array is fallback-only and does not override UUID association after reordering. State already held for a currently locked UUID is preserved.

Only available non-summary chapters with valid instances participate in answer checks, score, maximum score, state, completion, reset, solutions, summary, progress, and xAPI. If all chapters are locked, score/max are zero, no summary is created, and completion is not automatic.

## Build and tests

```bash
npm ci
npm test
npm run lint
npm run build
```

Generated production JS/CSS is written to `dist/`; do not edit it manually. Do not distribute `node_modules/`.

The Node companion covers manifest identity, stable/legacy/duplicate IDs, policy normalization, available navigation, all-locked behavior, message validation, timeout, disposal, duplicate policy responses, locked runtime prevention, score filtering, and UUID state.

## Security limitation

The host contract is a UI/runtime policy, not encryption. The host must independently authorize the user and, in the Moodle integration, must ignore the browser manifest and extract its own trusted server manifest. The standalone timeout is intentionally fail-open.

**Chapter blocking controls display, child-library initialization, and navigation, but the H5P package still contains the original parameters of every chapter. This solution must not be presented as a protection mechanism for confidential information.**

## License

MIT.
