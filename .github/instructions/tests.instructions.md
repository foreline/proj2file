---
name: PHPUnit Testing Standards
description: Guidelines for writing comprehensive, maintainable tests in Proj2File
applyTo: '**/tests/**/*.php'
---

# Testing Standards & Patterns

## Test Organization

- **Location**: Mirror source structure in `tests/` (e.g., `tests/Proj2File/TokenCounterTest.php` for `src/Proj2File/TokenCounter.php`)
- **Namespace**: `Foreline\Tests\` prefix (e.g., `Foreline\Tests\Proj2File\TokenCounterTest`)
- **Class Naming**: `{ClassName}Test` where ClassName is the class being tested
- **Bootstrap**: PHPUnit uses `vendor/autoload.php` via `phpunit.xml`

## Test Writing

- **Extend**: `PHPUnit\Framework\TestCase`
- **Method Names**: Descriptive, start with `test` (e.g., `testEmptyStringReturnsZero()`)
- **Arrange-Act-Assert**: Organize tests in AAA pattern
- **Focus**: Test public APIs; prefer integration tests over mocks for simple classes
- **Edge Cases**: Cover boundary conditions (empty, null, max values, invalid input)

Example:
```php
public function testTokenCountForMultilineText(): void
{
    // Arrange
    $text = "line one\nline two";
    
    // Act
    $count = TokenCounter::getCount($text);
    
    // Assert
    $this->assertGreaterThan(0, $count);
}
```

## Assertions & Expectations

- Use specific assertions: `assertSame()`, `assertEqualsCanonicalizing()`, `assertStringContainsString()`
- Avoid generic `assertTrue()` / `assertFalse()` when more specific assertions exist
- Test one logical concept per test method (but multiple assertions OK)
- Use data providers for parametrized tests (`@dataProvider methodName`)

## Mocking & Stubs

- Mock Symfony components that interact with file system or external services
- Use real objects for pure functions and domain logic
- Keep mocks close to reality; avoid over-mocking
- Verify return values, not call counts, for simple units

## Coverage Goals

- **Target**: 80%+ coverage on src/ (PHPUnit reports via coverage)
- **Public APIs**: Aim for 100% coverage
- **Coverage Exclusion**: Mark unreachable code with `@codeCoverageIgnore`

## Test Execution

- **Run All**: `vendor/bin/phpunit`
- **Run Single File**: `vendor/bin/phpunit tests/Proj2File/TokenCounterTest.php`
- **Run Single Test**: `vendor/bin/phpunit --filter testEmptyString`
- **With Coverage**: `vendor/bin/phpunit --coverage-text`

## Forbidden Patterns

- No direct file I/O in unit tests; mock or use temp directories
- No sleeps or time-dependent assertions
- No hardcoded paths; use `getcwd()` or fixtures
- No tests that depend on test execution order
