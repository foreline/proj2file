# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `--dedup` / `-d` CLI option to deduplicate consecutive repeated lines and truncate long lines (useful for log files with repetitive entries); optional parameter sets max line length (default 500 characters)
- Enhanced redaction to detect and mask PHP array credential assignments (e.g., `$DB['PASSWORD'] = '...'`)

## [0.4.0] - 2026-03-29

### Added
- `--strip-comments` / `-s` CLI option to remove comment lines and blank lines from packed files for reduced noise and token count
- Transparent gzip decompression for `.gz` files (useful for rotated log files)
- Improved tree rendering with ASCII characters for better terminal compatibility

## [0.3.1] - 2026-03-29

### Added
- PHPUnit 10 test suite with 19 comprehensive tests covering RunCommand, Redactor, and TokenCounter
- `phpunit.xml` configuration file

### Fixed
- Fatal type error when running `--help` due to `$defaultName` property conflict with parent class; migrated to `#[AsCommand]` attribute (Symfony 6.1+ recommended approach)

## [0.2.0] - 2026-03-29

### Added
- Automatic secret redaction feature to mask sensitive data (API keys, tokens, passwords, private keys, email addresses, IPv4 addresses, JWT tokens, and more)
- `--no-redact` CLI option to disable automatic redaction
- Redaction count output after packing
- Shebang-based language detection for files without extensions
- Comprehensive installation instructions (global install, from source, as dev dependency)
- Detailed documentation of redaction patterns and usage scenarios

## [0.1.05]

Historical releases are documented via git tags.
