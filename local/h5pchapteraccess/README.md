# local_h5pchapteraccess

`local_h5pchapteraccess` lets Moodle decide chapter availability for `H5P.CustomizableInteractiveBook`. It extracts the deployed book manifest on the server, stores policy by stable chapter `subContentId`, evaluates Moodle Availability API conditions for the authenticated user, and sends only the final decision to the H5P iframe.

The implementation does not modify Moodle core, `mod_h5pactivity`, `core_h5p`, or a theme.

## Requirements

- Moodle 4.5, build `2024100700` or newer within the 4.5 branch;
- PHP and database versions supported by that Moodle installation;
- `mod_h5pactivity` and `core_h5p` from Moodle 4.5;
- `H5P.CustomizableInteractiveBook` 1.0.32 or newer, containing the version 1 host contract;
- JavaScript enabled for the H5P iframe bridge;
- Availability plugins required by the conditions selected by teachers.

The plugin component is `local_h5pchapteraccess` and its code must be located at `local/h5pchapteraccess` under the Moodle root.

## Installation

This solution has two independent deployable artifacts: the Moodle plugin and the modified H5P library. Installing one does not install the other.

1. Build and package the compatible H5P library from the repository `src` directory, then install or update it through Moodle's H5P library administration workflow. A Git checkout does not contain `src/dist`, because those generated assets are intentionally ignored; run `npm ci` and `npm run build` before packaging the library.
2. Confirm that Moodle reports `H5P.CustomizableInteractiveBook` version 1.0.32 or newer. Older releases do not send the version 1 `ready` message and therefore use the intentional H5P allow-all fallback.
3. Copy this plugin directory to the Moodle code tree:

   ```text
   <moodle>/local/h5pchapteraccess
   ```

4. From the Moodle root, run:

   ```bash
   php admin/cli/upgrade.php --non-interactive
   php admin/cli/purge_caches.php
   ```

   Alternatively, complete the standard upgrade from **Site administration → Notifications**.

5. Open a compatible H5P activity as a manager or editing teacher and select **Chapter access** in the activity settings navigation.
6. On that Moodle installation, enable the integration and configure the chapters. The records in `local_h5pca_book` and `local_h5pca_chapter` belong to the site's database; copying the plugin folder or the H5P activity file does not copy those records.

Do not deploy `node_modules/`, test data, temporary build directories, or uncompiled `amd/src` as a substitute for the generated `amd/build` files.

## Updating

Replace the plugin code while preserving `config.php`, Moodle data, and the database, then run `admin/cli/upgrade.php`. Versioned changes use `db/upgrade.php`. The current documentation release adds no database fields; its upgrade step only advances the plugin savepoint.

After updating either the plugin or the H5P library, purge Moodle caches. The next management-page or policy request validates `contenthash` and synchronizes a changed manifest.

## Permissions

Both capabilities use `context_module`:

- `local/h5pchapteraccess:manage`: edit activity and chapter configuration. Allowed by default for manager and editing teacher archetypes.
- `local/h5pchapteraccess:viewlocked`: view every chapter through a runtime bypass while Moodle editing mode is enabled. Allowed by default for manager and editing teacher archetypes.

With editing mode disabled, managers and teachers receive the same chapter policy as students. `viewlocked` never changes the stored configuration students receive.

The AJAX endpoint also requires `mod/h5pactivity:view`, normal activity login, and visibility/access checks.

## Database

### `local_h5pca_book`

One row per H5P course module:

- unique `cmid`;
- optional cached `contentid`, `contenthash`, and deterministic `manifesthash`;
- `enabled` integration flag;
- optional plain-text `defaultmessage`;
- creation and modification timestamps.

`contentid` has a non-unique index. H5P identity fields are caches, not portable foreign keys.

### `local_h5pca_chapter`

One row per known chapter UUID:

- foreign key `bookid`;
- `chapteruuid` up to 128 characters;
- cached title, position, and stability flag;
- `accessmode` (`open`, `locked`, or `conditional`);
- optional Availability API JSON;
- optional plain-text message;
- restriction-display and active flags;
- timestamps.

`bookid + chapteruuid` is unique and `bookid + active` is indexed. Missing chapters are retained with `active = 0`; configuration is not deleted or migrated by title.

The privacy provider is a `null_provider`: these records belong to an activity and chapter and contain no user IDs or saved per-user decisions.

## Configuration

Open:

```text
/local/h5pchapteraccess/manage.php?cmid=123
```

The page requires login and `local/h5pchapteraccess:manage`, validates that the module is `h5pactivity`, confirms the main library, and synchronizes the manifest. It supports:

- a concise status overview that only exposes deployment warnings when action is required;
- enabling or disabling integration for the activity;
- an activity-level default locked message;
- explicit re-synchronization;
- a focused list of active chapters without technical IDs or hashes;
- inline chapter access mode and message editing;
- the standard Availability API editor on the same page. Selecting `conditional` reveals its options immediately without an intermediate save.

Messages are `PARAM_TEXT` plain text. A chapter-specific message wins over visible condition information, which wins over the activity default and then the plugin default.

Unstable legacy IDs are shown with a warning and cannot receive persistent rules. Teachers must republish the H5P with stable `subContentId` values before configuring them.

## Manifest extraction and synchronization

The extractor receives `cmid`, loads course/module/activity through Moodle 4.5 APIs, checks access, locates the package through the File API and `core_h5p`, confirms `H5P.CustomizableInteractiveBook`, and reads `config.chapters`.

The normal path obtains deployed parameters through `core_h5p`. If those parameters are unavailable, the documented fallback uses Moodle's `zip_packer` and reads only `content/content.json`; it does not call `ZipArchive` directly.

Identity rules:

- `subContentId` is the permanent key;
- title is never an identifier;
- absent IDs receive `legacy-position-N` and `stable = false`;
- duplicate or oversized IDs and malformed JSON are rejected;
- `manifesthash` is SHA-256 over ordered IDs, titles, and positions.

Synchronization runs in a delegated transaction and:

- creates new chapters as `open`;
- refreshes cached title, position, and stability;
- preserves mode, condition JSON, messages, and display flag by UUID;
- marks absent UUIDs inactive;
- reactivates returning UUIDs with their previous configuration.

It is triggered lazily from management and policy paths when content or structure changes. The CLI command is:

```bash
php local/h5pchapteraccess/cli/sync.php --cmid=123
```

## Access modes and Availability API

- `open`: always available; stored condition JSON is preserved but not evaluated.
- `locked`: always unavailable for users without bypass; condition JSON is not evaluated.
- `conditional`: evaluated for the requested user by Moodle's Availability API.

The chapter editor reuses `core_availability\frontend`, `availabilityconditionsjson`, and core validation. It supports the enabled Moodle condition plugins, including date, group, grouping, grade, activity completion, profile, and nested AND/OR trees. It does not implement custom date/group/grade selectors.

An empty conditional tree is treated as available and is reported as a configuration warning. Invalid condition JSON fails closed, emits developer debugging information, and exposes only a generic message. `showrestriction = 0` suppresses the condition explanation. Any HTML generated by the Availability API is converted to plain text before entering the H5P contract.

## AJAX endpoint

The AJAX function is:

```text
local_h5pchapteraccess_get_policy(cmid, contentid)
```

It requires the authenticated activity context and returns a serialized version 1 policy containing only:

- `contractVersion`;
- trusted server `contentId`;
- `required`;
- `teacherBypass`;
- `chapters[id].available` and plain-text `message`.

The service does not trust chapter titles, UUIDs, or the manifest announced by the iframe. It independently extracts the deployed server package, validates that the requested content belongs to the current `cmid`, and returns only current manifest IDs. It never returns `availabilityjson`, course structure, groups, grades, completion records, or internal database rows.

## Hooks API and AMD bridge

`db/hooks.php` registers `core\hook\output\before_standard_head_html_generation`. On the compatible `/h5p/embed.php` page, an enabled book causes Moodle to schedule `local_h5pchapteraccess/bridge` before the inner H5P iframe initializes. Unrelated pages perform no output and only the enabled-record check is needed on the embed path.

The AMD bridge:

- accepts only the version 1 `ready` type and library name;
- requires `event.origin === window.location.origin`;
- locates an `iframe.h5p-iframe` with the matching trusted `data-content-id`;
- requires `event.source === iframe.contentWindow`;
- validates a non-empty bounded `requestId` and a positive safe content ID;
- calls the AJAX service once for a request ID;
- limits the per-page request registry;
- responds to `event.source` using `event.origin`, never `"*"`;
- removes listeners on page disposal;
- hides AJAX implementation errors from the browser.

The H5P-side timeout provides standalone `allowAll` behavior when the bridge is absent or fails.

## Backup, restore, and duplication

The plugin uses Moodle 4.5 `backup_local_plugin` and `restore_local_plugin` integration attached to a course module. Backups include activity settings and all active/inactive chapter policy fields.

Restore and duplication:

- use the new restored `cmid`, never the original;
- create a new book record;
- reconcile chapters by UUID;
- clear cached H5P identity before extracting the restored package;
- call Availability API restore mechanisms to remap activity, group, grouping, and grade references and apply date offsets;
- defer package reconciliation safely when the H5P package is not readable until a later restore step.

If a referenced activity or condition target is not included in a backup, Moodle's normal Availability API restore behavior applies and records warnings.

## Cache and lifecycle

The MUC application cache stores only the structural manifest, keyed by `cmid` and accepted only while the current `contenthash` matches. It never stores a final user policy, grade result, completion result, group decision, or date decision.

Observers invalidate structural cache on `course_module_updated`. Permanent module deletion removes its book and chapters transactionally; course deletion performs a defensive cleanup. Lazy hash validation remains the source of truth because Moodle 4.5 exposes no specific event for every deployed H5P content change.

## Tests and build

From the H5P repository:

```bash
cd src
npm ci
npm test
npm run lint
npm run build
```

From the Moodle root after configuring the Moodle PHPUnit environment:

```bash
php vendor/bin/phpunit --testcompanion local_h5pchapteraccess_testcompanion
npx grunt amd --root=local/h5pchapteraccess
php admin/cli/purge_caches.php
```

Additional release checks include PHP lint, XMLDB schema validation, language-key validation, Moodle Code Checker when installed, activity backup/restore, and the manual matrix in the repository's `docs/test-plan.md`.

Generated files under `amd/build` must come from Grunt and must not be edited manually.

## Uninstallation

Use **Site administration → Plugins → Plugins overview → Uninstall** for `local_h5pchapteraccess`, then remove the directory when Moodle instructs. Standard Moodle plugin uninstallation removes the plugin tables and cached definitions. Back up the site first if chapter rules may be needed later.

Uninstallation does not modify or remove H5P activities, H5P packages, child libraries, grades, or attempts. Removing only the plugin code without completing the Moodle uninstall workflow is unsupported.

## Troubleshooting

- **It works on one computer but not another:** plugin files, the modified H5P library, and database rules are three separate requirements. Install `H5P.CustomizableInteractiveBook` 1.0.32 or newer on the destination Moodle, run the plugin upgrade, purge caches, and configure the destination activity. Copying only `local/h5pchapteraccess` cannot transfer either the H5P runtime or the source site's database rows.
- **All chapters appear after a delay:** open the management page and review its installation checks. Confirm the activity integration is enabled, at least one chapter is locked or conditional, the compatible AMD build is deployed, caches are purged, and the iframe is same-origin. The H5P fallback is intentionally allow-all.
- **Chapter access menu is absent:** confirm the activity uses `H5P.CustomizableInteractiveBook` and the user has `manage` in the module context.
- **A chapter cannot be configured:** it is inactive or has an unstable legacy ID. Republish with a stable `subContentId` and synchronize.
- **Package changes are not visible:** use **Synchronize again**, run the CLI sync, and purge MUC caches.
- **Conditional mode is unavailable:** enable Moodle availability and the required availability plugins.
- **A condition is always blocked:** enable developer debugging and validate its JSON through the standard editor; malformed JSON fails closed.
- **Teacher needs to preview the student experience:** turn Moodle editing mode off and reload the activity. Turn editing mode on to activate the `viewlocked` bypass.
- **PHPUnit does not start:** configure `$CFG->phpunit_prefix` and `$CFG->phpunit_dataroot`, initialize the test database, and keep it separate from production.
- **AMD changes do not load:** regenerate `amd/build` with Grunt and purge Moodle caches; never edit the minified files directly.

## Security limitation

**O bloqueio controla visualização, inicialização das bibliotecas filhas e navegação, mas o pacote H5P continua contendo os parâmetros originais dos capítulos. A solução não deve ser apresentada como mecanismo de proteção para informações confidenciais.**

The policy is a pedagogical and navigation control. Confidential information requires server-side authorization and separately protected resources.

## License

GNU GPL v3 or later, consistent with Moodle plugin requirements.
