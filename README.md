# ChAoS MVC Module Builder

> **Build modules. Validate them. Package them. Ship them.**

**Module Builder 1.2.2** is the developer workspace for creating and packaging modules for ChAoS MVC.

It provides a controlled development environment inside ChAoS MVC while keeping module development where it belongs: **outside the Core.**

---

## ✦ Module Builder 1.2.2

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
`signing` object uses the canonical `algorithm`, `fingerprint`, `sha256`,
`key_id`, and `public_key` shape. New projects are unsigned and receive a generated SHA-256 project identity and matching fingerprint. Release ZIP checksums and publisher signatures remain separate.
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

Initial projects are unsigned. Creation automatically generates a unique SHA-256 project identity and stores the same value in fingerprint; normal settings edits preserve both. Configure RSA-SHA256 or OpenPGP with a key ID and public key, then use **Build & Sign Release** with the matching private key and exact public ZIP download URL. Core verifies the publisher signature independently of certification status. See [Core release contract](docs/CORE_RELEASE_CONTRACT.md) for the signed statement, publication steps and integration tests.

Non-secret certification identity is configured in:

```text
user/modules/module_builder/data/certification.json
```

This runtime file contains installation-specific certification state and is
excluded from Git and release packages. Module Builder creates and maintains it
on the installed site.

RSA signing keys are held in memory apart from PHP's temporary upload. OpenPGP requires PHP GnuPG 1.5+ and uses an isolated private temporary keyring removed after signing.

Module Builder can generate a Windows-compatible 3072-bit RSA keypair for
RSA-SHA256 signing. The private PEM is encrypted with a developer-supplied
passphrase. The keypair downloads as a ZIP containing `private-key.pem`,
`public-key.pem`, and `key-metadata.json`. The metadata provides the
developer-generated `sha256`, `key_id`, and base64 `public_key` values
used by `module.json`. The generator uses the bundled
`config/openssl.cnf` so it does not depend on a working global Windows
OpenSSL configuration. No generated key material is written to the server.

The matching private key is supplied for the individual signing request. Core's trusted key, not a certification badge or checksum, authenticates updates.

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

**ChAoS MVC Module Builder 1.2.2**

*Protect the Core. Grow outward.*

### OpenSSL-compatible release files

Local `module.json` uses `signing.algorithm`, `key_id`, and the base64 public key. Configure publisher trust before signing. Automatic project identity values remain separate from the final ZIP checksum in meaning.

Signing creates `<slug>.remote.json` for publication at your configured `update_url`, a binary `.zip.sig`, and an exact `-release.txt` statement. The remote JSON contains the six Core release fields, including a verified signature; the versioned `.manifest.json` remains the builder receipt. Download the files from the project's artifacts; publication to your developer domain is manual. See [the signing contract](docs/CORE_RELEASE_CONTRACT.md).

### Verification gates (1.2.2)

The builder checks its actual PHP OpenSSL/GnuPG backend, validates the package, signs and verifies the statement, then reopens the saved files and verifies the ZIP checksum and signature using only the configured public key. It then copies the four public files (ZIP, remote JSON, binary signature, statement) into the project's managed release directory under `verified/<release-identity>/` and verifies those copies. Private keys are not copied. Conflicting staged files cause failure, not overwrite.

The build receipt and artifact list report these build-time results and the local verified-copy directory. This is local-only preparation: `developer_domain: not_checked` means neither HTTP availability nor publication at `update_url` has been verified. No webroot writes, server uploads, or Core changes occur. A rebuild invalidates current signature/publication receipts but retains earlier isolated verified copies as release history.

### Per-project developer certification

Projects use the shared read-only Chaos MVC Developers verification contract. Enter the project's developer, domain, signing algorithm, and published key ID. An exact, active, unexpired module credential may preload the account's public key/fingerprint and sets local `certified` to `Yes`. Every other result writes `No` while leaving all Builder and local signing features available. Private keys remain upload-only at signing time. See [Certification Integration](docs/CERTIFICATION.md).
