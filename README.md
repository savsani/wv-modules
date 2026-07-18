# wv-modules

Source for all of Wv's reusable Laravel modules (Core, and future modules like Auth, Admin, ActivityLog...) — one module per top-level folder in this repo.

This repo is not installed directly. [`wv/module-installer-kit`](https://github.com/savsani/wv-module-installer-kit) downloads a plain zip snapshot of this repo, extracts just the requested module's folder, and copies it into the host app's `Modules/{Name}` directory:

```bash
php artisan wv:install core
```

## Layout

Each module is a self-contained folder at the repo root:

```
Core/
  wv-module.json      # { "version": "1.2.0" } — bump this on every change
  package.deps.json    # optional — npm deps merged into the host app's package.json
  source/              # the module's file tree, copied verbatim into Modules/Core
    module.json
    composer.json
    app/
    resources/
    ...
```

## Adding or changing a module

- New module: add a new top-level folder following the same shape, then register it in `wv/module-installer-kit`'s `config/wv-modules.php`.
- Changing an existing module: edit its `source/` tree as needed, then **bump the `version` in that module's `wv-module.json`** in the same commit. `wv:update` in host apps compares versions, not file contents — if the version doesn't change, host apps won't see the update.
