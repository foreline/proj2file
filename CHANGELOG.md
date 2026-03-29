# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- `--exec` / `-x` option to capture and include shell command output in packed files
- `--include` / `-i` option to include files or directories from other locations
- `--tail` / `-t` option to limit file and command output to last N lines with truncation notices
- Sysadmin/troubleshooting example in documentation for gathering system state snapshots

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
