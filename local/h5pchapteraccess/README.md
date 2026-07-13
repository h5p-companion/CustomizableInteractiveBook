# H5P chapter access

`local_h5pchapteraccess` stores activity-level access policy configuration for chapters of an H5P Customizable Interactive Book.

This version contains the database schema, capabilities, manifest extraction and synchronization services, a CLI synchronization tool, and the privacy declaration. It intentionally provides no user interface, JavaScript integration, or Availability API rules yet.

## Manifest extraction

`service\manifest_extractor::extract()` receives a Moodle course module ID and uses APIs available in the bundled Moodle 4.5 version:

1. `get_course_and_cm_from_cmid()` and `mod_h5pactivity\local\manager` load the course, module, and activity;
2. normal login, module visibility, and `mod/h5pactivity:view` checks are applied;
3. the File API locates `mod_h5pactivity/package`;
4. `core_h5p\api::get_original_content_from_pluginfile_url()` resolves the package, including a Content Bank reference;
5. `core_h5p\api::get_library()` and `core_h5p\factory::get_core()->loadContent()` obtain the public H5P identity, main library, and parameters.

Only `H5P.CustomizableInteractiveBook` is accepted. Chapter identity always comes from `subContentId`. A chapter without one receives `legacy-position-N` for that synchronization and is marked unstable; its title is never used as an identifier. Duplicate IDs and malformed content are rejected. The manifest hash is a deterministic SHA-256 over ordered IDs, titles, and positions.

If `core_h5p` identifies the deployed content but does not return its parameters, the extractor uses Moodle's File API and `zip_packer`. It copies the package to Moodle's temporary area and extracts only `content/content.json`; it does not use `ZipArchive` directly or unpack child libraries. If no deployed `core_h5p` record exists, extraction fails explicitly instead of inventing a content ID.

## Synchronization

`service\manifest_synchronizer::synchronize()` runs in one delegated database transaction. It creates or refreshes the book identity and then synchronizes chapters by UUID:

- new chapters start with `accessmode = open`;
- existing titles, positions, and stability flags are refreshed;
- `accessmode`, `availabilityjson`, `lockedmessage`, and `showrestriction` are preserved;
- absent chapters remain stored with `active = 0`;
- a UUID that reappears is reactivated with its previous policy intact.

The caller must have `local/h5pchapteraccess:manage` in the activity context.

## CLI synchronization

From the Moodle root:

```text
php local/h5pchapteraccess/cli/sync.php --cmid=123
```

The script validates the parameter, runs only under CLI, explicitly establishes the site administrator as the Moodle user, and still executes the extractor's context checks and the synchronizer's `manage` capability check. It prints content identity, hashes, chapter count, and counts of created, updated, reactivated, and deactivated records.

## Data model

### `local_h5pca_book`

One record per Moodle course module (`cmid`). It caches optional H5P content identifiers and hashes, enables or disables policy handling for the activity, and stores an optional default plain-text lock message.

### `local_h5pca_chapter`

One record per chapter manifest entry. `chapteruuid` stores the H5P `subContentId`; legacy runtime fallback identifiers fit within the same 128-character field and are distinguished by `stableid`.

`accessmode` is prepared for these application-level values:

- `open`: available without a restriction;
- `locked`: explicitly unavailable;
- `conditional`: reserved for a future condition evaluated by Moodle.

The database uses a portable character field rather than a database-specific enum. Future services must validate the allowed values before persistence. `availabilityjson` is reserved storage only; no Availability API behaviour is implemented in this version.

## Capabilities

- `local/h5pchapteraccess:manage` allows managers and editing teachers to manage future activity-level rules.
- `local/h5pchapteraccess:viewlocked` allows managers and editing teachers to bypass chapter visibility when viewing. The bypass does not modify or replace the policy configured for students.

Both capabilities use the module context.

## Privacy

The plugin implements Moodle's `null_provider`. Its records describe an activity and its chapters, and contain no user IDs, attempts, grades, group membership, or other personal data. Access decisions may use Moodle context in future services, but this initial schema does not persist per-user decisions.

## Requirements

- Moodle 4.5 (`requires = 2024100700`).
