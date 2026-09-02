# ChAoS MVC Module Builder

> **Build modules. Validate them. Package them. Ship them.**

<<<<<<< HEAD
**Module Builder 1.0.7** is the developer workspace for creating and packaging modules for ChAoS MVC.
=======
**Module Builder 1.0.6** is the developer workspace for creating and packaging modules for ChAoS MVC.
>>>>>>> 7dc5abd9ee720cad8c2b41799f07e245319daed0

It provides a controlled development environment inside ChAoS MVC while keeping module development where it belongs: **outside the Core.**

---

<<<<<<< HEAD
## ✦ Module Builder 1.0.7
=======
## ✦ Module Builder 1.0.6
>>>>>>> 7dc5abd9ee720cad8c2b41799f07e245319daed0

Module Builder is an **admin-only development tool**.

There is no public Module Builder view.

Each module project is developed directly from its live source directory:

```text
user/modules/<slug>/
```

Release artifacts are generated separately under:

```text
/releases/<slug>/
```

Your project source remains your project source.

Your releases remain generated artifacts.

---

## ✦ Development Workflow

```text
Create
  ->
Develop
  ->
Validate
  ->
Build & Sign
  ->
Signed Artifacts
  ->
Release
```

Module Builder is designed to support the complete module development lifecycle without turning ChAoS MVC into a generic server file manager.

---

## ✦ Project Workspace

Module projects are maintained within their own project boundary.

A project may contain its own:

```text
<slug>/
├── controllers/
├── models/
├── views/
├── assets/
└── supporting module files
```

The live module directory is authoritative.

Every generated `module.json` declares `name`, `module`, `version`,
`description`, `update_url`, `creator`, `domain`, `certified`, `signing`,
`files`, and `routes`. Database-backed projects additionally declare
`database_tables`. The
`signing` object uses the canonical `type`, `fingerprint`, `sha256`,
`key_id`, and `public_key` shape. Every project receives a SHA-256 identity.
Key ID and public key are optional, but must be supplied together. Projects use
`"certified": "No"`.

The required lightweight source shape includes `controllers/<slug>.php`,
`views/admin/<slug>.php`, `views/index.php`, and `docs/CHANGELOG.md`; the public
`index` route is declared explicitly. When **Uses Database** is selected, the
builder additionally creates the module model, `sql/schema.sql`, `sql/patches/`,
owned-table declarations, and the explicit database lifecycle. New projects
initialize the changelog with the selected version and UTC creation date.

Module Builder does not maintain a second editable copy of the project inside `/releases`.

---

## ✦ Generic and Database-Backed Modules

Database support is explicit and optional. The **Uses Database** option is
unchecked by default when a project is created.

### Generic module

When **Uses Database** is unchecked, Module Builder creates a lightweight
module without unused persistence architecture:

```text
<slug>/
├── controllers/
│   └── <slug>.php
├── views/
│   ├── index.php
│   └── admin/
│       └── <slug>.php
├── docs/
│   └── CHANGELOG.md
└── module.json
```

A generic module does not receive:

- a model;
- `sql/schema.sql`;
- `sql/patches/`;
- `database_tables` metadata;
- database-state detection;
- Install SQL, Update SQL, or Delete Data actions.

Core-owned Nuke remains available because module removal is independent of
whether the module owns database tables.

### Database-backed module

When **Uses Database** is checked, the creation form accepts one or more
module-owned table names. If the table list is blank, the first table defaults
to the module slug.

Valid ownership names are:

```text
<module_slug>
<module_slug>_*
```

For example:

```text
letters
letters_subscribers
```

The generated module includes:

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
├── sql/
│   ├── schema.sql
│   └── patches/
├── docs/
│   └── CHANGELOG.md
└── module.json
```

Its explicit lifecycle is:

```text
Missing schema → Admin Install SQL
Missing schema → Public unavailable
Pending patch → Admin Update SQL
Current schema → Normal operation
Delete Data → Records removed; tables and module preserved
Nuke → Core verifies ownership and removes tables plus module
```

Install SQL, Update SQL, and Delete Data use authenticated Admin access, POST,
CSRF protection, and an explicit action allowlist. Database changes are never
triggered merely by loading a public or Admin page.

The configured tables are written to `module.json` as `database_tables`. This
is an ownership declaration used by Core during Nuke, so unrelated or external
tables are rejected.

---

## ✦ Release Artifacts

Built releases are written to:

```text
/releases/<slug>/
```

Release output may include artifacts such as:

```text
module-name-version.zip
release-manifest.json
checksum.sha256
signature.sig
```

Artifacts are generated distribution output and are separate from editable module source.
Each generated artifact appears at the bottom of its project as an authenticated
download link. Downloads are confined to that project's artifact directory and
reject traversal and symbolic-link targets.

---

## ✦ Certification & Signing

Module development does **not** require ChAoS MVC developer certification.

Uncertified developers retain the ability to:

- Create modules
- Develop and edit module projects
- Validate projects
- Generate their own RSA-SHA256 identity
- Populate signing metadata when ready

<<<<<<< HEAD
Initial project creation does not ask developers for keypair data. When Create
is clicked, the server generates a unique SHA-256 value for that project and
writes it into `module.json`; the value appears in Project Settings when the
project is opened. Developers use the standalone keypair generator and then
copy/paste the key ID and public key needed for signing. OpenPGP identity may
instead be recorded with `type: openpgp`, its fingerprint, key ID, and compact
base64 public key. The project remains
`"certified": "No"` until that status is changed explicitly. A release is produced through the
=======
Initial project creation does not request certification or signing values. New
projects are always created with `"certified": "No"` and blank `sha256`,
`key_id`, and `public_key` values. After creation, the developer can generate
an RSA-SHA256 identity and add its metadata from Project Settings. A release is produced through the
>>>>>>> 7dc5abd9ee720cad8c2b41799f07e245319daed0
combined **Build & Sign Release** action after the developer supplies the
module signing metadata, matching encrypted private PEM, and passphrase.

Non-secret certification identity is configured in:

```text
user/data/certified_developer.json
```

Private signing keys are **never stored by Module Builder**.

Module Builder can generate a Windows-compatible 3072-bit RSA keypair for
RSA-SHA256 signing. The private PEM is encrypted with a developer-supplied
passphrase. The keypair downloads as a ZIP containing `private-key.pem`,
`public-key.pem`, and `key-metadata.json`. The metadata provides the
developer-generated `sha256`, `key_id`, and base64 `public_key` values
used by `module.json`. The generator uses the bundled
`config/openssl.cnf` so it does not depend on a working global Windows
OpenSSL configuration. No generated key material is written to the server.

When signing is authorized, the private key is supplied for the individual signing request and is not retained.

---

## ✦ Project Boundaries

Module Builder operates only within the selected module project's development boundary:

```text
user/modules/<slug>/
```

The workspace is intended for **module development**, not unrestricted filesystem administration.

That boundary preserves one of the fundamental architectural principles behind ChAoS MVC:

> **Protect the Core. Grow outward.**

---

## ✦ Public Views

Modules that provide public views use the standard ChAoS MVC view layout wrappers:

```php
<?php require APPROOT . '/views/inc/head.php'; ?>

<!-- Module content -->

<?php require APPROOT . '/views/inc/foot.php'; ?>
```

These wrappers allow ChAoS MVC to resolve the active Theme Library implementation while preserving the framework's standard fallback behavior.

Modules should not implement their own duplicate page shell.

---

## ✦ Who Is Module Builder For?

Module Builder is intentionally useful **before certification**.

Developers can use it to learn the ChAoS MVC module structure, create projects, experiment, validate their work, and produce packages.

Certification represents trusted release authority.

---

## ✦ Documentation
[CHANGELOG](docs/CHANGELOG.md)

## ✦ Philosophy

ChAoS MVC modules extend the framework without consuming the framework.

Module Builder follows the same idea.

```text
Core
 │
 └── Module System
      │
      └── Your Module
           ├── Controllers
           ├── Models
           ├── Views
           └── Assets
```

Build inside the module boundary.

Keep the Core protected.

Grow outward.

---

<<<<<<< HEAD
**ChAoS MVC Module Builder 1.1.0**
=======
**ChAoS MVC Module Builder 1.0.6**
>>>>>>> 7dc5abd9ee720cad8c2b41799f07e245319daed0

*Protect the Core. Grow outward.*
