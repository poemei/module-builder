# Changelog

All notable changes to ChAoS MVC Module Builder are documented here.

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

