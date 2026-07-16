# Changelog

All notable changes to `local_h5pchapteraccess` are documented here.

## 1.0.7 - 2026-07-16

- Restored runtime H5P content ID resolution for referenced mod_h5pactivity packages, preventing policy requests from falling back to allow-all.
- Restored the editing-mode rule for `viewlocked`: teachers preview the student policy with editing disabled and bypass only with editing enabled.
- Advanced the plugin version beyond the already-installed database version after the source tree regressed to 1.0.4.

## 1.0.4 - 2026-07-15

- Fixed policy generation when Moodle's database driver hydrates the module context instance ID as a numeric string.
- Prevented valid policies from being discarded into the H5P allow-all timeout fallback.

## 1.0.3 - 2026-07-15

- Fixed lazy manifest synchronization after editing an H5P package when the initial management-page request has no sesskey parameter.

## 1.0.2 - 2026-07-15

- Fixed web and CLI bootstrapping when the plugin is installed through a Windows junction or symbolic link.
- Prevented `manage.php` and `edit.php` from resolving Moodle's `config.php` against the plugin's physical source repository.

## 1.0.1 - 2026-07-15

- Fixed the Moodle 4.5.12 activity settings navigation entry by keeping package extraction out of the lightweight callback.
- Fixed manifest extraction for the library's deployed root-level `chapters` parameters while retaining `config.chapters` compatibility.
- Added the exact **H5P chapter access** item to the activity **More** menu for authorized users.
- Made `open`, `locked`, and `conditional` selectable from the main management form.
- Limited the **Edit restrictions** button to chapters currently saved in conditional mode.

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
