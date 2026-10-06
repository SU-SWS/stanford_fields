# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`stanford_fields` is a Drupal custom module (field types, widgets, formatters, BEF filter widgets, GraphQL Compose plugins). It is installed inside a Drupal project whose layout varies (e.g. `web/` or `docroot/` as the webroot, `modules/custom/` or `modules/contrib/` for this module), so locate the project root (the directory with `vendor/`) and webroot before running commands. The main branch is `9.x`; releases are tagged automatically from semver PR labels when a PR merges (see README "Releases"), and each release gets a CHANGELOG.md entry plus a version bump in `stanford_fields.info.yml`.

### Preparing a release

- Feature PRs are squash-merged into `9.x`, so the local feature branch and local `9.x` are usually stale. Run `git fetch origin` and branch from `origin/9.x`, not the current branch.
- Name the branch `release-X.Y.Z`. Don't let it track `origin/9.x` (`git checkout -b` from a remote ref sets that upstream; remove it with `git branch --unset-upstream`).
- The release commit only bumps `version:` in `stanford_fields.info.yml` (the only info.yml) and adds a CHANGELOG.md entry at the top, in the existing format: version, a line of 80 dashes, `_Release Date: YYYY-MM-DD_`, then bullets summarizing the PRs since the last version commit (`git log --oneline <last-version-commit>..origin/9.x`).
- Open the PR against `9.x` (the README's mention of `master` is outdated) with a semver label, usually `patch`.

## Commands

PHP tests run from the Drupal project root using core's phpunit config (kernel tests need `SIMPLETEST_DB` set in `<webroot>/core/phpunit.xml`). Below, `<webroot>` is the Drupal webroot and `<module>` is this module's path relative to the project root:

```bash
cd <project-root>
vendor/bin/phpunit -c <webroot>/core <module>/tests/src/Unit
vendor/bin/phpunit -c <webroot>/core <module>/tests/src/Kernel
# Single test / method
vendor/bin/phpunit -c <webroot>/core --filter testMethodName <module>/tests/src/Kernel/Hook/StanfordFieldsHooksTest.php
```

CI (`.github/workflows/tests.yml`) runs PHPUnit with coverage on PHP 8.3–8.5 through `sws-caravan`. Classes marked `@codeCoverageIgnore` (e.g. the BEF preact plugins) are not unit tested.

Drush from the project root; on multisite installs pass the site URI: `vendor/bin/drush -l <site-uri> cr`.

Front-end islands (`js/lib`, Node per `.nvmrc`, yarn 4):

```bash
cd js/lib
yarn build       # production build into ../dist (commit the dist output)
yarn typecheck   # tsc --noEmit over all island sources
yarn dev         # webpack dev server on :6464 using template.html
```

`yarn build` also rewrites `js/index.html` from `template.html`; that's expected. TypeScript must stay on 5.x — TS 7 fails to install with yarn 4.9's builtin patch.

## Preact BEF filter islands

The most intricate part of the module is the set of Better Exposed Filters widgets that replace a hidden `<select>` with a Preact UI. Understanding it requires reading across PHP, JS, CSS and library definitions:

- **PHP side** — `src/Plugin/better_exposed_filters/filter/PreactFiltersBase.php` wraps the filter in `<div id="preact-{field}-{first 8 chars of view dom_id}" class="preact-filter {plugin-id}">` and adds `drupalSettings.preactFilters[pluginId][wrapperId] = {id, options, viewId}`. Settings are keyed by wrapper id (not a list) because Drupal deep-merges AJAX settings and would merge lists by index across views. Subclasses (`PreactComboBox`, `PreactTaxonomyLabelHierarchyComboBox`, `PreactTaxonomyLabelHierarchyCheckbox`) only attach their library.
- **JS entry points** — `js/lib/select-lists`, `hierarchy-label-select-lists`, `hierarchy-label-checkboxes` each call `registerPreactFilter(behaviorName, pluginId, Component)` from `js/lib/components/drupal-filter.tsx`. Behavior names must be unique per island.
- **`drupal-filter.tsx` owns the Drupal lifecycle**: `once()` per select, renders with `preact.render` into an appended `.preact-filter-mount`, and on `detach(..., 'unload')` unmounts with `render(null, ...)` so Base UI listeners/portals are cleaned up when views AJAX replaces the form. Don't reintroduce `preact-island`; it queried the whole document and never unmounted.
- **The original `<select>` is the source of truth.** Components update `option.selected` on the original select and call `submitFilter(select, focusKey)`, which dispatches a bubbling `change` event so BEF's `auto_submit.js` decides whether/when to submit. Don't click `[data-bef-auto-submit-click]` directly.
- **Focus after AJAX**: `submitFilter` remembers the wrapper id + focus key; on the next attach the element with `data-filter-focus-key` matching it is focused. Never focus on mount unconditionally (it steals focus and scrolls on page load).
- **Keep widget inputs out of the views request**: the Base UI select has no `name` and its hidden input gets `data-bef-auto-submit-exclude`; hierarchy radios use a `form` attribute pointing at a nonexistent form id. Extra params in the views AJAX query have caused odd behavior (see the "404 on reset" history).
- The hierarchy widgets remove `view_path` from `drupalSettings.views.ajaxViews[...]` (`removeViewPath` option) — a workaround added for Drupal 11.3; the combobox intentionally does not.
- Hierarchy options are flattened taxonomy terms where children have a leading `-` in the label; `components/option-sets.ts` groups them (by value, skipping `All`).
- `components/select-list.tsx` wraps `@base-ui/react/select` (React aliased to `preact/compat` in webpack). Multiple selects commit on popup close; single selects commit on change. UI strings go through `Drupal.t()`.
- **Styles** live in `css/preact-combobox.css` (library `bef-styles`), not CSS-in-JS. It also hides the original select via `html.js .preact-filter .form-type-select`.
- **Bundles/libraries**: webpack splits `shared-runtime`, `shared-preact` and `shared-select-list` chunks. Chunk names in `webpack.config.js` must match `stanford_fields.libraries.yml` (`preact-shared` → `select-list-shared` → `combobox`/`hierarchy-combo`; `hierarchy-checkbox` depends only on `preact-shared`). Adding an entry point means updating both.
- `js/lib/globals.d.ts` types the `Drupal`, `once` and `drupalSettings` globals. `template.html` shims these so `yarn dev` runs the same behavior code as Drupal.

### Testing islands against a real site

- Find views using the widgets by searching config for the plugin ids (`preact_combo_box`, `taxonomy_label_hierarchy`, `taxonomy_label_hierarchy_checkbox`) under `bef.filter.*.plugin_id`, then find a page that renders that view. Test with a view that has BEF auto-submit and AJAX enabled, since that is where lifecycle bugs show up.
- If no page uses a widget, `yarn dev` exercises all three islands against `template.html`.
- Local sites may send long-lived `Cache-Control` headers, so the browser can keep serving stale HTML/settings after `drush cr`. Hard-reload or add a query string before concluding a change didn't take effect.
