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

### Changed

- Started from the LGT Concrete CMS Composer package template; the template's
  tooling is retained (GitHub Actions workflows for code standards and system
  tests, PHPStan, PHP-CS-Fixer, Prettier and Cloudflare Access authentication
  for the private Composer registry).
