---
description: Generate comprehensive PHPUnit tests for a PHP class or method
tools:
  - read_file
  - create_file
  - replace_string_in_file
---

# Add Tests for a PHP Class/Method

You will write comprehensive PHPUnit tests for the selected class or method following Proj2File conventions.

## Process

1. **Understand the Code**: Read the target class/method to identify inputs, outputs, edge cases, and dependencies
2. **Identify Test Cases**: List all scenarios (happy path, edge cases, error conditions)
3. **Create Test File**: If it doesn't exist, create `tests/Proj2File/{ClassName}Test.php` mirroring the source structure
4. **Write Tests**: Use `test{Scenario}()` naming, AAA pattern, and specific assertions
5. **Handle Dependencies**: Mock external dependencies (file I/O, external APIs); use real objects for pure logic
6. **Verify Coverage**: Ensure tests cover public APIs and critical paths

## Test Structure Template

```php
<?php

declare(strict_types=1);

namespace Foreline\Tests\Proj2File;

use Foreline\Proj2File\{ClassName};
use PHPUnit\Framework\TestCase;

class {ClassName}Test extends TestCase
{
    private {ClassName} $subject;

    protected function setUp(): void
    {
        $this->subject = new {ClassName}();
    }

    public function testHappyPath(): void
    {
        // Arrange
        $input = 'expected input';
        
        // Act
        $result = $this->subject->method($input);
        
        // Assert
        $this->assertSame('expected output', $result);
    }

    public function testEdgeCaseEmptyInput(): void
    {
        // Arrange
        $input = '';
        
        // Act
        $result = $this->subject->method($input);
        
        // Assert
        $this->assertSame('expected output for empty', $result);
    }

    public function testThrowsExceptionOnInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->subject->method('invalid');
    }
}
```

## Key Guidelines

- **Coverage**: Aim for 80%+ on public methods
- **Naming**: `test{Scenario}` is clearer than test descriptions
- **Isolation**: Each test is independent; use `setUp()` for shared fixtures
- **Assertions**: Use specific assertions (`assertSame()`, `assertGreaterThan()`, etc.)
- **Mocking**: Mock only file I/O, external services; use real objects for domain logic
- **Data Providers**: Use for parametrized tests to avoid repetition

## Example Output

If testing `TokenCounter::getCount()`:

```php
public function testEmptyStringReturnsZero(): void
{
    $this->assertSame(0, TokenCounter::getCount(''));
}

public function testSingleWordReturnsAtLeastOne(): void
{
    $this->assertGreaterThan(0, TokenCounter::getCount('hello'));
}

public function testMultilineTextCountsAllTokens(): void
{
    $text = "hello world\nfoo bar";
    $count = TokenCounter::getCount($text);
    $this->assertGreaterThanOrEqual(4, $count);
}
```

Run tests with: `vendor/bin/phpunit tests/Proj2File/TokenCounterTest.php`
