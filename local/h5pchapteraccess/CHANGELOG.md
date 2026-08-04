# Changelog

All notable changes to `local_h5pchapteraccess` are documented here.

## 1.0.9 - 2026-08-04

- Replaced the technical management view with a focused teacher-facing chapter list.
- Embedded the standard Moodle Availability API editor on the same management page.
- Made conditional options appear immediately when Conditional is selected, without an intermediate save.
- Kept deployment diagnostics visible only when they require teacher or administrator action.
- Added responsive, plugin-scoped presentation styles without changing policy evaluation or the H5P runtime.

## 1.0.8 - 2026-07-20

- Added visible deployment checks for the minimum compatible H5P library, generated Moodle AMD bridge, activity integration, and configured chapter rules.
- Clarified that copying plugin code does not install the modified H5P runtime or transfer per-site database configuration.
- Simplified the management form with mode explanations, chapter status badges, expandable technical IDs, and direct conditional-access buttons.
- Preserved all policy, bypass, Availability API, and browser communication behavior.

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
