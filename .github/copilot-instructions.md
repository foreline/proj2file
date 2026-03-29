# Project: Proj2File

A PHP console application that packs entire projects into a single markdown file with statistics and optional redaction.

## Tech Stack

- **PHP**: 8.1+ with strict types
- **CLI Framework**: Symfony Console 6.4
- **File I/O**: Symfony Finder, Filesystem
- **Config**: Symfony YAML, Config
- **Testing**: PHPUnit 10
- **Static Analysis**: PHPStan 1.10 (level 8)

## Coding Standards

- **PSR-4 Autoloading**: `Foreline\` namespace (src/) and `Foreline\Tests\` (tests/)
- **Strict Types**: Every PHP file must declare `strict_types=1`
- **Type Hints**: All method parameters and return types must be declared
- **PHPDoc**: Every class and public method requires PHPDoc with @param, @return (or @throws)
- **Formatting**: PSR-12 style with 4-space indentation
- **Constants**: UPPER_SNAKE_CASE for class constants
- **Visibility**: Always explicitly declare public/private/protected

## Architecture

- **Single Responsibility**: Each class handles one concern (packing, redaction, counting, CLI)
- **Dependency Injection**: Constructor injection preferred
- **Error Handling**: Type-safe exceptions with specific exception classes
- **Configuration**: YAML-based configuration with defaults in code

## Key Classes

- `ProjectPacker`: Orchestrates the packing workflow
- `Redactor`: Handles sensitive data masking
- `TokenCounter`: Simulates OpenAI tokenization
- `RunCommand`: Symfony console command entry point

## Testing

- **Framework**: PHPUnit 10
- **Location**: `tests/` mirroring `src/` structure
- **Naming**: `{ClassName}Test` extends `PHPUnit\Framework\TestCase`
- **Coverage**: Aim for high coverage of public APIs and edge cases
- **Mocking**: Use when testing integrations; prefer real objects for unit tests

## Build & Run

- **Test**: `vendor/bin/phpunit`
- **Lint**: `composer phpstan` (strict level 8, excludes vendor/)
- **Run CLI**: `bin/proj2file --help` (or `php bin/proj2file`)

## Security

- Prevent exposure of credentials, API keys, and sensitive paths
- Redactor class masks common credential patterns
- Always validate file paths and user input
- Use Symfony Filesystem for safe path operations

## Dependencies

Prefer Symfony components for consistency. Avoid adding unnecessary dependencies; justify new deps in commit messages.
