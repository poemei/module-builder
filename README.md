# ChAoS MVC Module Builder

> **Build modules. Validate them. Package them. Ship them.**

**Module Builder 1.0.4** is the developer workspace for creating and packaging modules for ChAoS MVC.

It provides a controlled development environment inside ChAoS MVC while keeping module development where it belongs: **outside the Core.**

---

## ✦ Module Builder 1.0.4

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
  ↓
Develop
  ↓
Validate
  ↓
Build
  ↓
Artifacts
  ↓
Sign
  ↓
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
- Build packages
- Generate unsigned release artifacts

Certification adds the ability to produce **ChAoS-certified signed releases**.

Non-secret certification identity is configured in:

```text
user/data/certified_developer.json
```

The certification query endpoint is configured through:

```text
CHAOS_CERTIFICATION_ENDPOINT
```

Private signing keys are **never stored by Module Builder**.

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

It is not permission to learn or develop.

---

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

**ChAoS MVC Module Builder 1.0.4**

*Protect the Core. Grow outward.*