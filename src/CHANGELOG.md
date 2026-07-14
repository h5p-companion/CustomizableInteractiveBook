# Changelog

All notable changes to `H5P.CustomizableInteractiveBook` are documented here. H5P library versions use `majorVersion.minorVersion.patchVersion` from `library.json`.

## 1.0.34 - 2026-07-14

- Documented the completed host-policy architecture, contract, state, scoring, build, tests, and security limits.
- Added the production Node test runner for access-domain and HostBridge behavior.
- Added guarded runtime helpers that make locked-child construction and result aggregation directly testable.
- Hardened lint compatibility without changing unrelated runtime behavior.
- Regenerated production JavaScript and CSS.

## 1.0.33 - 2026-07-14

- Added version 1 `postMessage` HostBridge with exact origin/source/correlation validation and standalone allow-all fallback.
- Added UUID manifest, immutable AccessPolicy, and AccessController.
- Prevented `H5P.newRunnable` for locked chapters.
- Excluded locked chapters from navigation, state changes, score, maximum score, progress, completion, summary, reset, solutions, and xAPI.
- Added accessible locked placeholders and localized access strings.
