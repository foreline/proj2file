---
name: PHP Standards & Conventions
description: Enforce strict PHP 8.1+ standards, type safety, and PSR-12 formatting for Proj2File
applyTo: '**/*.php'
---

# PHP Coding Standards

## Strict Types & Type Safety

- Every file must start with `declare(strict_types=1);`
- All method parameters must have type hints (use union types if needed)
- All methods must declare return types (use `void` if no return)
- Use nullable types `?string` sparingly; prefer default values or typed collections
- No untyped arrays; use `array<string, string>` or `string[]` syntax

## Naming Conventions

- **Classes**: PascalCase (`ProjectPacker`, `TokenCounter`)
- **Methods**: camelCase (`getCount()`, `loadExclusions()`)
- **Properties**: camelCase with visibility prefix (`private string $path`)
- **Constants**: UPPER_SNAKE_CASE (`OUTPUT_DIR`, `CONFIG_FILE`)
- **Namespaces**: PascalCase (`Foreline\Proj2File\Command`)

## PHPDoc Requirements

- **Classes**: Brief description + @package + copyright if applicable
- **Properties**: `/** @var Type $name Description */` on the line above
- **Methods**: @param for each parameter, @return, @throws for exceptions
- **Generic Arrays**: Use square bracket syntax `string[]` or angle bracket `array<key, value>`

Example:
```php
/**
 * Counts tokens in the given text.
 *
 * @param string $text The text to tokenize
 * @return int Number of tokens
 * @throws InvalidArgumentException If text contains invalid encoding
 */
public function tokenize(string $text): int
```

## Visibility & Encapsulation

- Default to `private`; make methods/properties public only if part of the stable API
- Use `protected` for extension points in base classes
- Static methods should be used for pure functions (e.g., `TokenCounter::getCount()`)

## Error Handling

- Throw typed exceptions: `\InvalidArgumentException`, `\RuntimeException`, custom exceptions
- Include descriptive messages: `throw new InvalidArgumentException("Path must be absolute: {$path}")`
- Catch specific exceptions; avoid bare `catch (\Throwable)`
- Never suppress errors with `@`

## Code Style

- **Indentation**: 4 spaces (no tabs)
- **Line Length**: Aim for 120 characters; break long lines after commas/operators
- **Use Statements**: Group and sort: native types, then vendor packages, then local
- **Ternary**: Use only for simple assignments; prefer explicit if/else for complex logic
- **Closures**: Use arrow functions (`fn()`) for simple callbacks

## Symfony-Specific

- Use `Symfony\Component\Filesystem\Path` for path operations
- Use `Symfony\Component\Finder\Finder` for file traversal
- Use `Symfony\Component\Yaml\Yaml` for configuration
- Inject dependencies via constructor; avoid global state

## Forbidden Patterns

- No `var_dump()`, `print_r()` in production code (use logging)
- No magic methods unless explicitly needed
- No dynamic property access on typed objects
- No silenced errors (`@` operator)
