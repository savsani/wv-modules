# wv-core-module

Source for the Wv "Core" Laravel module — shared UI kit, design tokens, layouts, and Alpine.js components every other Wv module builds on.

This repo is not installed directly. It's fetched and copied into an app's `Modules/Core` directory by [`wv/module-installer-kit`](https://github.com/savsani/wv-module-installer-kit) via:

```
php artisan wv:install core
```

## Layout

- `source/` — the module's file tree, copied verbatim into `Modules/Core`.
- `package.deps.json` — npm dependencies merged into the host app's `package.json` on install.
