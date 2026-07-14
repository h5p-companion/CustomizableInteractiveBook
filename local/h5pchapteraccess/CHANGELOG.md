# Changelog

All notable changes to `local_h5pchapteraccess` are documented here.

## 1.0.0 - 2026-07-14

- Released the complete Moodle 4.5 chapter-policy integration.
- Added server-side H5P manifest extraction and UUID-preserving synchronization.
- Added manual activity/chapter configuration with Moodle's standard Availability API editor.
- Added per-user `open`, `locked`, and `conditional` policy evaluation and teacher bypass.
- Added authenticated AJAX policy service, Hooks API integration, and same-origin AMD bridge.
- Added MUC structural manifest caching and lifecycle observers.
- Added activity backup, restore, duplication reconciliation, and Availability API reference remapping.
- Added repository, extractor, synchronizer, policy, availability, external-service, capability, cache, observer, and backup/restore PHPUnit coverage.
- Hardened content ID and duplicate-request validation, reduced redundant persistence reads, and skipped condition evaluation for bypass users.
- Added complete installation, operation, troubleshooting, architecture, test, and security documentation.

## 0.7.1 - 2026-07-14

- Completed production security/performance review and generated AMD assets.

## 0.7.0 - 2026-07-14

- Added resilient synchronization, cache, lifecycle observers, and backup/restore support.
