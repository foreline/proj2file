# Usage

Use `proj2file` for global install. If running from source, replace it with `php bin/proj2file`.

## Basic

Packs project from current working directory to default output directory `./.proj2file`.

```shell
proj2file run
```

Packs specific directory only:

```shell
proj2file run ./path/to/dir
```

## Line numbering

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

### Format options

* `4d` — right-aligned 4-digit numbers (default): `   1`, `  12`, ` 123`, `1234`
* `03d` — zero-padded 3-digit numbers: `001`, `012`, `123`
* `left:4` — left-aligned numbers with width 4: `1   `, `12  `, `123 `, `1234`
* `center:5` — centered numbers with width 5: `  1  `, ` 12 `, ` 123`

## Extra paths

Include files or directories from other locations with `--include` (or `-i`):

```shell
proj2file run --include /etc/nginx/nginx.conf --include /etc/php/8.2/fpm/pool.d
```

Multiple `--include` flags can be used. Each can point to a single file or an entire directory.

## Command output capture

Capture the output of shell commands with `--exec` (or `-x`):

```shell
proj2file run --exec "systemctl status nginx" --exec "df -h" --exec "free -h"
```

Each command runs via the system shell. Output is included at the end of the packed file. Non-zero exit codes are shown in the header. Redaction applies to command output too.

## Log truncation

Limit each file and command output to the last N lines with `--tail` (or `-t`):

```shell
proj2file run --tail 200
```

Truncated files show a notice like `... (1800 lines truncated, showing last 200 lines)`.

## Strip comments

Remove comment lines and blank lines from packed files with `--strip-comments` (or `-s`):

```shell
proj2file run --strip-comments
```

Comment syntax is detected automatically by file extension:

| Extensions | Comment prefixes removed |
|---|---|
| `.conf`, `.cfg`, `.ini`, `.sh`, `.yml`, `.yaml`, `.py`, `.rb`, `.env` | `#` |
| `.php`, `.js`, `.ts`, `.c`, `.go`, `.rs`, `.java`, `.css` | `//`, `/*`, `*/`, `*` (docblocks) |
| `.sql`, `.lua` | `--` |
| `.bat`, `.cmd` | `REM`, `::` |
| `.xml`, `.html` | `<!--` |

## Deduplication

Deduplicate consecutive repeated lines and truncate long lines with `--dedup` (or `-d`):

```shell
# Default: truncate lines longer than 500 characters
proj2file run --dedup

# Custom max line length (e.g., 300 characters)
proj2file run --dedup 300
```

Consecutive identical lines are collapsed into a single occurrence with a repeat count:

```
ERROR: duplicate key value violates unique constraint... (500 chars, truncated)
... (repeated 47 more times)
```

## Gzip support

Gzipped files (`.gz`) are transparently decompressed before packing:

```shell
proj2file run --include /var/log/syslog.2.gz --tail 300
```
