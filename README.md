# Proj2File

A PHP console application designed to pack entire project into a single markdown file.

* The packed file is placed to `.proj2file` directory.
* The file contains project structure and project files content wrapping in triple backticks.
* Respects `.gitignore` file rules.
* Generates detailed statistics about the packed project including the number of lines of code and tokens.

## Project status

Proj2File is best treated as a focused utility, not a full AI workflow platform.

Many AI tools can now read repositories directly, so the "single markdown for AI" value is no longer unique by itself.
The strongest value of Proj2File today is deterministic export: creating a reproducible, shareable snapshot of a project in one file.

## Possible usage scenarios

Use Proj2File when you need one or more of the following:

* **Restricted environments**: You cannot grant direct repository access to an external assistant or vendor.
* **Snapshot handoff**: You need a timestamped project dump for asynchronous review.
* **Audit and compliance workflows**: You need a stable artifact for legal, procurement, or security review.
* **Incident and postmortem capture**: You want a "state at time T" export of code and structure.
* **Prompt budgeting and context checks**: You want rough file/line/token statistics before sending data to an LLM.
* **Client communication**: You need a low-friction way to share project internals without requiring full repo setup.

## When not to use Proj2File

Proj2File is usually unnecessary when your AI tooling already has safe and direct project access, keeps context over time, and supports selective file retrieval.

The tool is not intended to replace:

* Source control and code review tools.
* Full backup and archival systems.
* Fine-grained context retrieval pipelines.

## Installation

### Requirements

* PHP 8.0+
* Composer

### Global install (Windows/macOS/Linux)

```shell
composer global require foreline/proj2file
```

Make sure Composer global bin is in your `PATH`:

* Windows: `%APPDATA%\Composer\vendor\bin`
* macOS/Linux: `$HOME/.composer/vendor/bin` or `$HOME/.config/composer/vendor/bin`

Verify global install:

```shell
proj2file --help
```

Use globally from any project directory:

```shell
proj2file run
```

### Install this project (from source)

```shell
git clone https://github.com/foreline/proj2file.git
cd proj2file
composer install
```

Run the CLI:

```shell
php bin/proj2file run
```

### Install as a dev dependency in another project

```shell
composer require-dev foreline/proj2file
```

Run as local dependency:

* macOS/Linux:

```shell
./vendor/bin/proj2file run
```

* Windows:

```shell
.\vendor\bin\proj2file.bat run
```

## Usage

Packs project from current working directory to default output directory `./.proj2file`.

Use `proj2file` for global install. If running from source, replace it with `php bin/proj2file`.

```shell
# Run with default options.
proj2file run
```

Packs specific directory only:
```shell
proj2file run ./path/to/dir
```

### Formatting
```shell
# Default format (4 digits, right-aligned)
proj2file run -l

# Left-aligned numbers with width 4
proj2file run -l --number-format "left:4"

# Zero-padded 3 digits
proj2file run -l --number-format "03d"

# Centered numbers with width 5
proj2file run -l --number-format "center:5"
```

#### Format options:

* `4d` - Default: right-aligned 4-digit numbers (e.g., "   1", "  12", " 123", "1234")
* `03d` - Zero-padded 3-digit numbers (e.g., "001", "012", "123")
* `left:4` - Left-aligned numbers with width 4 (e.g., "1   ", "12  ", "123 ", "1234")
* `center:5` - Centered numbers with width 5 (e.g., "  1  ", " 12 ", " 123")

### Redaction

Redaction is **enabled by default**. Every packed file is scanned and detected secrets are replaced with `***REDACTED***`.

To disable redaction and include raw file contents:

```shell
proj2file run --no-redact
```

#### What is detected:

| Category | Examples |
|---|---|
| Private keys | PEM-encoded RSA, EC, DSA, OpenSSH keys |
| Env-style secrets | `DB_PASSWORD=...`, `API_KEY=...`, `AUTH_TOKEN=...` (value is masked, key name is kept) |
| AWS access keys | `AKIA...` patterns |
| GitHub tokens | `ghp_...`, `ghs_...` |
| GitLab tokens | `glpat-...` |
| OpenAI keys | `sk-...` |
| Slack tokens | `xoxb-...`, `xoxp-...` |
| Bearer tokens | `Bearer eyJ...` and similar |
| URL credentials | `scheme://user:password@host` (credentials masked) |
| Connection strings | `password=...` / `pwd=...` parameters |
| Email addresses | `user@example.com` |
| IPv4 addresses | Non-loopback, non-broadcast IPs |
| JWT tokens | `eyJ...` three-segment tokens |
| Hex secrets | 32+ character hex strings in value positions |

The total number of redactions is printed after packing:

```
Redactions applied: 42
```

Combine with other options:

```shell
proj2file run -l
proj2file run ./path/to/dir --no-redact
```

