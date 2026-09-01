# ChAoS MVC Module Builder

> **Build modules. Validate them. Package them. Ship them.**

**Module Builder 1.0.6** is the developer workspace for creating and packaging modules for ChAoS MVC.

It provides a controlled development environment inside ChAoS MVC while keeping module development where it belongs: **outside the Core.**

---

## ✦ Module Builder 1.0.6

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

Every generated `module.json` declares, in order: `name`, `module`,
`version`, `description`, `update_url`, `creator`, `domain`,
`certified`, `signing`, `files`, and `routes`. The `signing` object
always contains `sha256`, `key_id`, and the base64-encoded
`public_key`. Unsigned projects keep those three values empty and use
`"certified": "No"`.

The required generated source shape includes `controllers/<slug>.php`,
`models/<slug>_model.php`, `views/admin/<slug>.php`, and
`views/index.php`, and `docs/CHANGELOG.md`; the public `index` route is declared explicitly. New projects initialize the changelog with the selected version and UTC creation date.

Module Builder does not maintain a second editable copy of the project inside `/releases`.

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

---

## ✦ Certification & Signing

Module development does **not** require ChAoS MVC developer certification.

Uncertified developers retain the ability to:

- Create modules
- Develop and edit module projects
- Validate projects
- Generate their own RSA-SHA256 identity
- Populate signing metadata when ready

Initial project creation does not request certification or signing values. New
projects are always created with `"certified": "No"` and blank `sha256`,
`key_id`, and `public_key` values. After creation, the developer can generate
an RSA-SHA256 identity and add its metadata from Project Settings. A release is produced through the
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

**ChAoS MVC Module Builder 1.0.6**

*Protect the Core. Grow outward.*
