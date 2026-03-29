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

## Quick start

Requirements: PHP 8.1+, Composer.

```shell
composer global require foreline/proj2file
proj2file run
```

See [docs/installation.md](docs/installation.md) for all installation methods.

## Usage

```shell
# Pack current directory
proj2file run

# Pack specific directory
proj2file run ./path/to/dir

# Include external files and capture command output
proj2file run --include /etc/nginx --exec "systemctl status nginx" --tail 300
```

See [docs/usage.md](docs/usage.md) for all options: line numbering, log truncation, comment stripping, deduplication, gzip support.

## Redaction

Redaction is enabled by default. Detected secrets (credentials, API keys, tokens, private keys, IP addresses) are replaced with `***REDACTED***`.

```shell
proj2file run --no-redact
```

See [docs/redaction.md](docs/redaction.md) for the full list of detected patterns.

## Examples

See [docs/examples.md](docs/examples.md) for real-world usage:

* Security incident response
* Service troubleshooting (Nginx, PHP-FPM, PostgreSQL, MySQL)
* Docker / container diagnostics

