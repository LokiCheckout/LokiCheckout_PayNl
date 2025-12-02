# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.18] - 02 December 2025
### Fixed
- Compatibility with Pay NL v4.X
- Fix integration test
- Add translation strings
- Improve UX with CSS

## [2.0.17] - 21 November 2025
### Fixed
- Manually execute dispatch to checkout in integration tests
- Fix tests with GitLab repo
- Upgrade unit tests to M 2.4.8-p3
- Fix button styling
- Update PHP comments

## [2.0.16] - 22 October 2025
### Fixed
- Do not escape `$css()` with `escapeHtmlAttr()` but `escapeHtml()`

## [2.0.15] - 01 October 2025
### Fixed
- Improve image rendering
- Fix escaping

## [2.0.14] - 30 September 2025
### Fixed
- Add CSS class to icon
- Copy generic CI/CD files

## [2.0.13] - 27 September 2025
### Fixed
- Fix wrong escaping

## [2.0.12] - 25 September 2025
### Fixed
- Undo escaping of images via imageRenderer
- Update README
- Add ignore file

## [2.0.11] - 24 September 2025
### Fixed
- Remove redundant CSS classes from icon containers
- Implement new imageRenderer
- Add `.prevent` modifier to `@click` event handler

## [2.0.10] - 04 September 2025
### Fixed
- Copy generic CI/CD files

## [2.0.9] - 02 September 2025
### Fixed
- Refactor Loki-library location in Playwright tests

## [2.0.8] - 28 August 2025
### Fixed
- Add CI files
- Replace yireo/opensearch with yireo/opensearch-dummy in Gitlab CI

## [2.0.7] - 26 August 2025
### Fixed
- 6098dd7 Add GitLab CI files

## [2.0.6] - 21 August 2025
### Fixed
- Add dependency with loki/magento2-css-utils
- Declare used PHP namespaces
- Document latest version of template

## [2.0.5] - 19 August 2025
### Fixed
- Fix final SVG

## [2.0.4] - 18 August 2025
### Fixed
- Prevent escaping of SVGs

## [2.0.3] - 18 August 2025
### Fixed
- Lower requirements to PHP 8.1

## [2.0.2] - 06 August 2025
### Fixed
- Lower PHP requirement to PHP 8.2+

## [2.0.1] - 01 August 2025
### Fixed
- Add `Loki_FieldComponents` anyway

## [2.0.0] - 01 July 2025
### Fixed
- Upgrade to major 2

## [1.0.1] - 08 July 2025
### Fixed
- Use Loki test-case in Playwright to detect JS errors automatically
- Rewrite @helpers to @loki in Playwright tests
- Update deps

## [1.0.1] - 24 May 2025
### Fixed
- Lower constraints with LokiComponents dep

## [1.0.0] - 19 May 2025
### Fixed
- Add integration tests

## [0.0.5] - 15 May 2025
### Fixed
- Rename FastCheckout to iDeal Fast Checkout
- Apply LokiForm to submit form
- Update composer dependencies
- Set default of `fast_checkout_enabled` to 1
- Add option for enabling/disabling fast checkout
- Generate new MODULE.json with simple test count
- Allow PHP 8.4 in CI

## [0.0.4] - 25 April 2025
### Fixed
- Allow upgrading to LokiFieldComponents and LokiCheckout 1.0
- CSP fixes
- Add Fast Checkout with PAY

## [0.0.3] - 16 April 2025
### Fixed
- Add Playwright tests

## [0.0.2] - 08 April 2025
### Added
- Payment logos

## [0.0.1] - 21 January 2025
- Initial release
