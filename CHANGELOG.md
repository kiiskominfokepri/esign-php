# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.4] - 2026-07-27

### Added
- `docs/ci3.md` — CodeIgniter 3 integration guide
- Link to the CI3 guide from README ("Framework-specific guides")

## [1.0.3] - 2026-07-27

### Fixed
- Align Composer `license` with repository `LICENSE` (`GPL-3.0-or-later`)
- Remove empty `config.platform` leftover from local PHP 7.2 testing

### Added
- Isolation unit tests for `BaseResponse`/`JsonResponse`, `HandlesDates`, V1/V2 `VerifyResponse`, and exception hierarchy
- `CHANGELOG.md`

## [1.0.2] - 2026-07-27

### Added
- Expanded unit test suite (16 → 82 tests, 286 assertions)
- Coverage for V1/V2 client methods, DTOs, `FileHelper`, `AbstractClient`, `ApiException`, and response models

### Notes
- No production `src/` changes

## [1.0.1] - 2026-07-27

### Changed
- Minimum PHP version: **7.2.5+** (or 8.0+)
- PHPUnit support: `^8.5.39 || ^9.6`
- CI matrix: PHP 7.2–8.4 on `ubuntu-22.04`

### Added
- `symfony/polyfill-php80` for `str_contains` / related helpers on PHP &lt; 8.0

### Fixed
- Rewrite production code for PHP 7.2 syntax (no typed properties, unions, `match`, nullsafe, arrow functions, etc.)

## [1.0.0] - 2026-07-26

### Added
- Initial public release of BSrE Esign Client Service PHP library (V1 & V2)
- `EsignFactory`, HTTP client with Basic Auth, DTOs, response models, exceptions
- Unit tests with mocked Guzzle
- Usage examples and README
- GitHub Actions PHPUnit workflow

[Unreleased]: https://github.com/kiiskominfokepri/esign-php/compare/v1.0.4...HEAD
[1.0.4]: https://github.com/kiiskominfokepri/esign-php/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/kiiskominfokepri/esign-php/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/kiiskominfokepri/esign-php/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/kiiskominfokepri/esign-php/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/kiiskominfokepri/esign-php/releases/tag/v1.0.0
