# Changelog

Notable changes to this project are documented here.

## 0.0.1 (unreleased)

### Added

- Banner Manager Concrete CMS package with a dashboard section at
  `/dashboard/banner_manager` for managing banners, slides and slide types.
- Banners: ordered slides, optional schedule start/end, an optional fallback
  banner shown outside the schedule, and dot/arrow display settings (desktop
  and mobile) plus a slide animation setting.
- Slides: content stored as Concrete Express entries, with the fields defined by
  their slide type.
- Slide types: backed by Express entities, with a seeded "Standard" type (media,
  mobile media, title, description, button link type/page/URL/text, open in new
  tab, slide duration). Existing installs are topped up with newly added fields
  on upgrade.
- `bm_banner` block that renders a banner through the theme's `slideshow`
  element. Slide lists are cached, filtered per viewer by page read permission,
  and block output is cached until the banner's next schedule boundary.
- Duplicate action for banners (shares slides with the original) and slides
  (copies the Express entry values).
- Dashboard search, sorting and column sets for banners, slides and slide types.
- PHPUnit tests for the entities and search column sets.

### Fixed

- Entity IDs are GUID strings: lookups no longer cast them to integers and
  comparisons are strict.
- New slide positions no longer use the GUID as an integer position.
- `btBmBanner.bmBannersId` is stored as a GUID column.
- Dashboard pages rendered blank: the view files lived in `single_page/`
  instead of Concrete's `single_pages/`, so the installed pages had no
  `cFilename`. The directory is renamed and the upgrade now writes the missing
  filenames for existing installs (package version 0.0.4).
- The slides and slide types search controllers had namespaces that didn't match
  their file paths, and the banners/slides pages requested search header
  elements that don't exist.
- The slide type details page failed to load its `Control` element controller:
  it lived in `controllers/elements/dashboard/slides_types/` instead of
  `controllers/element/dashboard/slide_type/`.
- Added the missing controller for the slide type attributes page, so the
  "Edit Attributes" button works (list, add, edit and delete attributes).

- The `bm_banner` block no longer throws a `TypeError` when no banner is set or
  the chosen banner has been deleted, and the `slideshow` template no longer
  calls `count()` on `false`.
- A fallback loop (A falls back to B, B falls back to A) hung the block's cache
  lifetime calculation. Fallback chains are now walked with a repeat check, and
  the banner form rejects a fallback that would create a loop.

### Changed

- Started from the LGT Concrete CMS Composer package template; the template's
  tooling is retained (GitHub Actions workflows for code standards and system
  tests, PHPStan, PHP-CS-Fixer, Prettier and Cloudflare Access authentication
  for the private Composer registry).
- Dashboard search lists now resolve rows through the identity map (`find()`)
  so the prefetch query actually removes the per-row query, and the `bm_banner`
  block loads slides, Express entries and attribute values in a single query.
- A banner follows its whole fallback chain until it finds one within its
  schedule, instead of one level.
- Saving a banner or changing its slides now expires the cached output of every
  `bm_banner` block showing it (or falling back to it), so guests see the change
  straight away rather than after the cache lifetime.
