# Banner Manager Kit

[![Code Standards](https://github.com/limegreentangerine/BannerManagerKit/actions/workflows/CodeStandards.yml/badge.svg)](https://github.com/limegreentangerine/BannerManagerKit/actions/workflows/CodeStandards.yml)
[![System Tests](https://github.com/limegreentangerine/BannerManagerKit/actions/workflows/SystemTests.yml/badge.svg)](https://github.com/limegreentangerine/BannerManagerKit/actions/workflows/SystemTests.yml)

A Concrete CMS package (`banner_manager_kit`) for managing banners and
slideshows from the dashboard and placing them on pages with a block.

## Features

- **Banners** group an ordered list of slides, with an optional schedule
  (start/end), an optional fallback banner shown outside the schedule, dot and
  arrow settings for desktop and mobile, and a slide animation setting.
  Banners can be duplicated; the copy shares the original's slides.
- **Slides** hold their content in Concrete Express entries, so the available
  fields come from the slide's type. Slides can be duplicated, which copies the
  entry values.
- **Slide types** are backed by Express entities. A "Standard" type is seeded on
  install with media, mobile media, title, description, button link
  (page or URL), button text, open in new tab and slide duration. Fields added
  to it in later versions are applied to existing installs on upgrade.
- **Banner block** (`bm_banner`, in the "Banner Manager" block set) renders a
  chosen banner. Slides linked to pages the viewer cannot read are hidden.

## Requirements

- PHP 8.4 or later.
- Concrete CMS 9.5.0 or later.
- The `class_kit` package (`limegreentangerine/class_kit`), installed in the
  site before this package.
- A theme that provides a `slideshow` element, which the block's `slideshow`
  template renders through.
- Composer.
- Node.js and npm, used by the Composer post-install script and JavaScript
  formatting commands.
- Access to the private Composer registry at
  `https://packages.limegreentangerine.net` to install private dependencies.

## Install

```bash
composer install
```

Composer runs `npm ci` after installation. If you need to authenticate to the
private registry, configure Composer to send the Cloudflare Access service
token headers for `packages.limegreentangerine.net`:

- `CF-ACCESS-CLIENT-ID` -> Cloudflare Access service-token client ID.
- `CF-ACCESS-CLIENT-SECRET` -> matching client secret.

Keep these credentials private; do not commit them to the repository.

To use the package in a site, install `class_kit` first, then install Banner
Manager from the dashboard. Installation adds the dashboard pages under
**Banner Manager**, the block type and the Standard slide type. Bumping the
package version and running the upgrade re-applies any new Standard slide type
fields.

## Usage

1. In **Dashboard > Banner Manager > Slides**, create slides.
2. In **Banners**, create a banner, add and order its slides, and set any
   schedule, fallback and display options.
3. Add the **Banner** block to a page and choose the banner.

## Development commands

Run these commands from the repository root:

| Command                                             | Purpose                                                                                                                        |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `composer test`                                     | Run PHPUnit tests.                                                                                                             |
| `composer test-coverage`                            | Run PHPUnit and print a coverage report.                                                                                       |
| `composer typecheck`                                | Run PHPStan using `phpstan.neon`. Findings are displayed, but the command exits successfully even when PHPStan reports errors. |
| `composer format:check`                             | Check PHP and JavaScript formatting.                                                                                           |
| `composer format`                                   | Format PHP and JavaScript files.                                                                                               |
| `composer format:php:check` / `composer format:php` | Check or format PHP files with PHP-CS-Fixer.                                                                                   |
| `composer format:js:check` / `composer format:js`   | Check or format files with Prettier.                                                                                           |

PHPStan analyzes `controller.php`, `src` and `tests` at level 4, with bootstrap
configuration from `phpstan-bootstrap.php`. If you add package directories,
update the `paths` in `phpstan.neon` and the directories in
`.php-cs-fixer.dist.php` so they are included in analysis and formatting.

The tests are unit tests that run without booting Concrete. The dashboard
controllers, block and Express setup need to be checked in a running site.

## GitHub Actions

- **CodeStandards** runs formatting checks and PHPStan on pushes and pull
  requests.
- **SystemTests** runs PHPUnit on pull requests.

Both workflows install dependencies from the private Composer registry. Add
these **repository secrets** under **Settings > Secrets and variables >
Actions** so the workflows can authenticate:

- `CF_ACCESS_CLIENT_ID` -> Cloudflare Access service-token client ID.
- `CF_ACCESS_CLIENT_SECRET` -> matching client secret.
