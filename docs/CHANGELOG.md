# Changelog

## [1.2.3] - 2026-10-05

- Generate RSA-SHA256 signing keys at 4096 bits, above Core's required
  3072-bit minimum.
- Inspect the generated OpenSSL key before export and fail if its actual type
  or size does not satisfy the Core updater contract.
- Reject imported RSA public keys and keypair ZIP metadata backed by keys below
  3072 bits instead of allowing an update that Core will reject later.
- Record the inspected RSA size in key metadata rather than trusting a supplied
  `rsa_bits` value.

## [1.2.2] - 2026-09-20

- Complete live module-project creation, editing, validation, and bounded file operations.
- Support generic and database-backed module scaffolding with explicit lifecycle metadata.
- Generate, retain, and download versioned release artifacts and verified local release history.
- Integrate read-only developer certification without granting certification signing authority.
- Support RSA-SHA256 and OpenPGP release signing through the shared Core release contract.
- Emit the exact six-field remote update JSON consumed by ChAoS MVC Core.
- Align documentation with the canonical `signing.algorithm` metadata and the module-owned certification configuration path.
- Exclude installation-specific `data/certification.json` runtime state from source control and release packages.
- Reserve live OpenPGP round-trip qualification for a Linux server with PHP GnuPG 1.5 or newer.

## [1.1.10] - 2026-09-04

- Prefer PHP cURL with verified TLS for hosted certification requests and retain HTTPS streams as a fallback.

## [1.1.9] - 2026-09-04

- Add one-time, module-owned certification configuration under `data/`.
- Verify and preload the account public key; never store the private key.
- Hide setup after valid configuration and restore it when configuration data is missing.
- Apply the configured developer identity automatically to new module projects.

## [1.1.8] - 2026-09-04

- Replace the legacy certification protocol with the shared read-only `/developers/verify` contract.
- Verify exact developer, domain, module credential, algorithm, key ID, active result, and expiry before writing `certified: "Yes"`.
- Keep certification at `No` for mismatch, expiry, malformed response, or unavailable service without disabling Builder or signing work.
- Add per-project signing identity onboarding; verified account responses may preload the public key and OpenPGP fingerprint.
- Cache verified assertions for up to 24 hours and negative assertions for up to five minutes; stale success is never accepted.
- Never request, transmit, cache, or import private keys from certification.
- Document certification and cryptographic release signing as separate trust relationships.

## [1.1.7] - 2026-09-04

- Replace the opaque “Validation failed” build error with every concrete project validation failure.
- Preserve line breaks in the admin error display so each failing rule is readable.
- Add regression coverage proving invalid projects are blocked and their exact validation cause is returned.

## [1.1.6] - 2026-09-04

- Keep “Build and sign current module” clickable so server-side validation can report the exact problem.
- Remove template-only disabled logic based on cached project/signing state.
- Retain all authoritative validation, signing-key, package, and verification gates on submission.

## [1.1.5] - 2026-09-04

- Write the entered developer domain into every generated local `module.json`, normalized to lowercase.
- Write the form's certification selection as the exact local JSON string `"Yes"` or `"No"`.
- Preserve domain and certification across ordinary project edits.
- Keep certification metadata independent from publisher signing-key configuration.

## [1.1.4] - 2026-09-04

- Confirm and preserve the current-project build-and-sign transaction: Module Builder never accepts a ZIP filename from admin.
- Label the action “Build and sign current module” to make artifact selection behavior explicit.
- Add regression coverage proving the internally selected ZIP contains the selected module's current identity, version, and signing trust from module.json.

## [1.1.3] - 2026-09-04

- Gate signing on the actual PHP crypto backend, not assumptions about the host OS.
- Reopen and verify saved JSON, signature, statement, and ZIP using public-key trust before reporting success.
- Copy only public release files into project release data under verified/<release-identity>/; verify the copies and reject conflicting existing files.
- Record and display build-time verification results separately from unchecked public-domain availability.
- Add tamper tests for saved outputs, copied releases, and ZIP checksums.

## [1.1.2] - 2026-09-04

- Apply DEV_SIGNATURE_OPENSSL.md: generated local manifests use signing.algorithm and base64 public PEM trust.
- Emit separate <slug>.remote.json with exactly Core's six signed release fields for the developer's update_url.
- Emit binary ZIP.sig and exact LF/no-trailing-newline release statement for OpenSSL verification.
- Remove stale remote JSON and statement on rebuild; preserve automatic project identifiers and lifecycle scaffolding.
- Test generated publication JSON against the local Core verifier.

## [1.1.1] - 2026-09-04

- Restore automatic SHA-256 project identity generation on creation, with a matching fingerprint.
- Preserve generated identity during ordinary settings edits; keep ZIP checksums and Core-compatible release signatures separate.

## [1.1.0] - 2026-09-04

- Match Core 98613e2 release statements and embedded base64 signature contract.
- Generate RSA-3072+ or OpenPGP detached signatures; verify before emitting signed JSON.
- Require an exact HTTPS ZIP download URL and enforce installed package host policy.
- Separate checksum integrity, publisher signing, and certification status.
- Stop generating arbitrary SHA-256 identities; retain optional public-key fingerprints as metadata.
- Preserve package_hosts and extra project metadata on settings edits.
- Add actual Core verifier/archive integration tests and explicit GnuPG dependency checks.
- Package the signing helper and existing OpenSSL configuration in the builder manifest.

## 1.0.7 — 2026-09-02

### Added

- Optional **Uses Database** project-creation control, unchecked by default.
- Configurable module-owned table list with module-slug ownership validation;
  a blank list defaults to the module slug.
- Database-backed modules include configured owned tables plus complete Install
  SQL, Update SQL, unavailable, Delete Data, and Core-owned Nuke lifecycle.
- Database-backed modules generate a model, `sql/schema.sql`, `sql/patches/`,
  schema-version state, and `missing`, `current`, and `update` detection.
- Database-backed manifests declare their owned tables and SQL files.
- Typed OpenPGP fingerprint and public-key metadata support.
- Canonical signing metadata shape using `type`, `fingerprint`, `sha256`,
  `key_id`, and `public_key`.
- Validation for generated lifecycle controls and database ownership.
- Authenticated download links for every generated project artifact, with
  project confinement, traversal rejection, and symbolic-link rejection.
- Packaged `sql/patches/.gitkeep` placeholder for database-backed modules.

### Changed

- SHA-256 identity is mandatory for every module manifest.
- RSA key ID and Public PEM remain optional but must be supplied together.
- Lightweight modules no longer receive unused models or SQL architecture.
- Core-owned Nuke remains available for both generic and database-backed modules.
- Generated database mutations use Admin authentication, POST, CSRF protection,
  and explicit action allowlists.
- Generated PHP now uses readable PSR-12-style formatting and the established
  `theme::render()` header/footer contract with Core-view fallback.
- Clarified that the release ZIP checksum is generated during build and written
  beside the artifact; it is distinct from the manifest signing identity.

### Fixed

- Blank, unreadable, or invalid installed schema versions now produce an
  explicit `invalid` state instead of being reported as current.
- Version mismatches without an exact migration patch now produce `invalid`
  instead of silently collapsing to current.
- Qualified manifest generation, schema creation, state detection, Delete Data,
  and ownership validation with three module-owned tables.

All notable changes to ChAoS MVC Module Builder are documented here.

## 1.0.6 — 2026-08-31

### Changed

- The standalone RSA-SHA256 keypair generator remains available at the top of Module Builder.
- Initial project creation asks for no keypair, SHA-256, or passphrase data.
- Clicking **Create** makes the server generate a unique SHA-256 for that project and write it into `module.json`.
- Opening the project displays its generated SHA-256 in Project Settings.
- Developers copy/paste the remaining keypair metadata needed to sign their modules.

### Fixed

- Removed certification, keypair, and passphrase inputs from initial project creation.
- New projects begin with `"certified": "No"`, a server-generated SHA-256, and blank key ID and public-key values.
- Creation requests cannot substitute caller-provided signing metadata for the server-generated project SHA-256.
- Public-key input now accepts full PEM, base64-encoded PEM, or the base64 PEM body and normalizes it for `module.json`.

## 1.0.5 — 2026-08-30

### Added

- Added `docs/CHANGELOG.md` to Module Builder itself.
- Added `docs/CHANGELOG.md` as a standard generated module file.
- New projects now begin with an initial changelog entry containing:
  - The developer-selected starting version.
  - The actual UTC creation date.
  - An initial implementation note.
- Added regression coverage for generated changelog files and content.
- Added strict admin-view rendering coverage to detect undefined template variables.

### Changed

- Generated project structure now includes:

    ```text
    <slug>/
    ├── controllers/
    │   └── <slug>.php
    ├── models/
    │   └── <slug>_model.php
    ├── views/
    │   ├── index.php
    │   └── admin/
    │       └── <slug>.php
    ├── docs/
    │   └── CHANGELOG.md
    └── module.json
    ```

- Generated `module.json` manifests now declare `docs/CHANGELOG.md` in `files`.
- Project validation now requires both the changelog file and its manifest entry.
- Release packages now include the module changelog.
- Module Builder version updated to 1.0.5.

### Fixed

- Fixed an undefined `$hasSigning` variable in the Module Builder admin view.
- Added defensive signing-state initialization before rendering build controls.
- Preserved the established `views/admin/<slug>.php` convention instead of switching generated modules to a generic admin view filename.

## 1.0.4 — 2026-08-29

### Rebuilt

- Rebuilt Module Builder as a clean implementation rather than continuing the earlier patch-based architecture.
- Established `user/modules/<slug>/` as the authoritative editable module source.
- Separated editable project source from generated release artifacts.
- Kept Module Builder admin-only with no public Module Builder view.
- Preserved protected ChAoS MVC Core, router, bootstrap, authentication, and model infrastructure.

### Added

- Added complete project creation, editing, selection, and deletion.
- Added a compact project workspace with:
  - Project list.
  - Project settings.
  - File tree.
  - Text editor.
  - File and directory creation.
  - File and directory renaming.
  - File and directory deletion.
  - Project validation.
  - Per-project release artifacts.
- Added bounded file operations limited to the selected module root.
- Added path traversal and symbolic-link protection.
- Added editable file-type and file-size restrictions.
- Added release generation under installation-root `/releases/<slug>/`.
- Added release ZIPs, SHA-256 checksums, manifests, and signature artifacts.
- Added project metadata fields for:
  - `name`
  - `module`
  - `version`
  - `description`
  - `update_url`
  - `creator`
  - `domain`
  - `certified`
  - `signing`
  - `files`
  - `routes`
- Added signing metadata fields:
  - `sha256`
  - `key_id`
  - `public_key`
- Added blank signing fields for newly created uncertified modules.
- Added validation for controller structure, views, routes, files, metadata, signing identity, and HTTPS update URLs.
- Added generated controller methods:
  - `index()`
  - `admin()`
- Added generated `views/index.php`.
- Added the public `index` route to generated `routes`.
- Added the public view to generated file manifests.
- Added ChAoS theme wrappers to generated public views:

    ```php
    <?php require APPROOT . '/views/inc/head.php'; ?>

    <!-- Module content -->

    <?php require APPROOT . '/views/inc/foot.php'; ?>
    ```

### RSA-SHA256 Signing

- Added developer-generated 3072-bit RSA keypairs.
- Added RSA-SHA256 signing and verification compatibility.
- Added a bundled Windows-safe OpenSSL configuration.
- Added a required minimum 12-character private-key passphrase.
- Added encrypted private-key export.
- Added SHA-256 public-key fingerprints.
- Added developer key IDs.
- Added base64-encoded public keys for use in `module.json`.
- Added immediate in-memory keypair ZIP downloads containing only:

    ```text
    private-key.pem
    public-key.pem
    key-metadata.json
    ```

- Added non-secret key metadata containing:
  - Format version.
  - Algorithm.
  - RSA key size.
  - SHA-256 fingerprint.
  - Developer key ID.
  - Creation time.
  - Encryption status.
  - File names.
  - Key-handling instructions.
- Added verification that generated encrypted private keys reopen with the submitted passphrase.
- Added verification that incorrect passphrases cannot open private keys.
- Added test signing and verification using RSA-SHA256.

### Security

- Private keys are generated and packaged entirely in memory.
- Private keys and passphrases are never stored in:
  - Module projects.
  - Release artifacts.
  - Sessions.
  - Logs.
  - Local configuration.
  - Server temporary files.
- Local RSA generation remains separate from ChAoS MVC certification.
- Certification status remains separate from general development capability.
- Uncertified developers retain complete project creation, editing, validation, key-generation, and metadata-management capability.
- ChAoS MVC remains the certification and trusted signing authority.

### Changed

- Replaced separate unsigned-build and signing workflows with one **Build & Sign Release** action.
- A release is signed only when the module contains complete signing metadata and the developer supplies the matching encrypted private key and passphrase.
- Signing verifies that the uploaded private key matches the public key and SHA-256 identity declared by the module.
- Release manifests record:
  - Signed status.
  - RSA-SHA256 algorithm.
  - Developer key ID.
  - Signing time.
- The Module Builder interface now inherits the active ChAoS MVC theme.
- Release artifacts are treated as generated output and cannot be edited as project source.

### Fixed

- Fixed the previous `deleteProject()` failure.
- Fixed the missing `module_package_builder.php` dependency path by loading it from the module’s own `lib` directory.
- Restored the missing `generateSigningKeypair()` implementation.
- Replaced unusable escaped JSON key downloads with directly usable PEM files inside a ZIP.
- Fixed the confusion between modules containing signing metadata and artifacts that had actually been cryptographically signed.
- Fixed generated module shape inconsistencies.
- Fixed missing public routes and views in generated manifests.
- Fixed the admin workspace becoming an unbounded generic project list.
- Restored project editing, deletion, file management, validation, and artifact controls.

## 1.0.0–1.0.3 — Legacy Development

### Initial Development

- Introduced the original Module Builder concept.
- Explored automatic ChAoS MVC module scaffolding and packaging.
- Established the need for an admin development workspace.
- Identified the required separation between module source and release output.
- Identified controller, model, view, route, and manifest requirements.
- Exposed architectural problems that led to the clean 1.0.4 rebuild.

### Superseded

- These early versions relied on incomplete and increasingly patch-based implementations.
- Their architecture was replaced by the clean Module Builder 1.0.4 rebuild.
- Reliable individual release notes were not retained for these prototypes.
