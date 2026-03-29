---
description: Perform a comprehensive code review focusing on bugs, security, style, and test coverage
tools:
  - read_file
  - grep_search
---

# Code Review

Perform a thorough code review of the selected files, checking for bugs, security issues, style violations, and test gaps.

## Review Checklist

### Correctness & Logic
- [ ] Logic is sound and handles edge cases (null, empty, boundary values)
- [ ] Type hints are correct and exhaustive
- [ ] Error handling is specific and informative
- [ ] Dependencies are properly injected, not created inline
- [ ] No dead code, unreachable branches, or commented-out code

### Security
- [ ] No hardcoded credentials, API keys, or secrets
- [ ] User input is validated and sanitized
- [ ] File paths are canonicalized (use `Symfony\Component\Filesystem\Path`)
- [ ] Sensitive data is logged safely (no passwords, tokens)
- [ ] Exception messages don't leak implementation details

### Code Standards (PSR-12, Strict Types)
- [ ] `declare(strict_types=1);` at top of file
- [ ] All method parameters have type hints
- [ ] All methods have return type declarations
- [ ] PHPDoc is present on classes and public methods
- [ ] Indentation is 4 spaces
- [ ] Visibility (public/private/protected) is explicit
- [ ] Constants are UPPER_SNAKE_CASE
- [ ] No `@` (error suppression) operator

### Documentation & Clarity
- [ ] Class and method PHPDoc describes purpose and behavior
- [ ] Complex logic has inline comments
- [ ] Parameter descriptions explain expected format
- [ ] Return types match documented @return

### Testing
- [ ] Public APIs have test coverage (aim for 80%+)
- [ ] Edge cases are tested (empty, null, boundary values)
- [ ] Error conditions are tested (exceptions thrown)
- [ ] Tests are isolated and not order-dependent
- [ ] Mocks are used appropriately (not over-mocked)

### Performance & Maintainability
- [ ] No obvious performance issues (n² loops, unnecessary copying)
- [ ] No Symfony instantiation inside loops
- [ ] Collection operations use efficient methods
- [ ] Code is readable and self-documenting

## Report Template

**File**: `{path/to/file.php}`

**Summary**: One-sentence assessment

**Issues Found**:
1. **Type Error** (line X): Description
2. **Security** (line Y): Description
3. **Style** (line Z): Description

**Recommendations**:
- Fix X
- Add test for Y
- Consider refactoring Z

**Test Coverage**:
- [ ] Public methods tested: {list}
- [ ] Missing coverage: {list}

**Overall**: ✓ Approved / ⚠ Needs Changes / ✗ Reject

## Example Review Output

**File**: `src/Proj2File/TokenCounter.php`

**Summary**: Solid implementation with good type safety and clear logic.

**Issues Found**:
1. *(None)* 

**Recommendations**:
- Add test for non-ASCII Unicode characters in tokenization
- Document the OpenAI tokenizer simulation behavior in class PHPDoc

**Test Coverage**:
- ✓ Public static method `getCount()` has good coverage
- ✓ Edge cases (empty, multiline) tested

**Overall**: ✓ Approved

## Notes

- Focus on objective criteria (types, standards, security)
- Be constructive; suggest fixes, not just complaints
- Reference line numbers and code snippets
- Run `composer phpstan` before review to identify type issues automatically
