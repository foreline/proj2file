# Proj2File

A PHP console application designed to pack entire project into a single markdown file.

* The packed file is placed to `.proj2file` directory.
* The file contains project structure and project files content wrapping in triple backticks.
* Respects `.gitignore` file rules.
* Generates detailed statistics about the packed project including the number of lines of code and tokens.

The tool is particularly useful for:
* Asking AI for help with specific project related tasks.
* Creating project snapshots.

## Installation

```shell
composer require-dev foreline/proj2file
```

## Usage

Packs project from current working directory to default output directory `./.proj2file`.
```shell
# Run with default options.
php bin/proj2file run
```

Packs specific directory only:
```shell
php bin/proj2file run ./path/to/dir
```

### Formatting
```shell
# Default format (4 digits, right-aligned)
php bin/proj2file run -l

# Left-aligned numbers with width 4
php bin/proj2file run -l --number-format "left:4"

# Zero-padded 3 digits
php bin/proj2file run -l --number-format "03d"

# Centered numbers with width 5
php bin/proj2file run -l --number-format "center:5"
```

#### Format options:

* `4d` - Default: right-aligned 4-digit numbers (e.g., " 1", " 12", " 123")
* `03d` - Zero-padded 3-digit numbers (e.g., "001", "012", "123")
* `left:4` - Left-aligned numbers with width 4 (e.g., "1 ", "12 ", "123 ")
* `center:5` - Centered numbers with width 5 (e.g., " 1 ", " 12 ", " 123")

