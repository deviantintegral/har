# Plan to Fix PHPStan Issues

## Overview
Fix all PHPStan issues in test files related to type resolution, redundant assertions, and missing type hints.

## Issues and Proposed Fixes

### 1. SplitCommandTest.php:63 - Template Type Resolution
**Issue**: Unable to resolve template type T in `array_values()` call
**Fix**: Add PHPStan type annotation to help resolve the template type
```php
/** @var list<string> $files */
$files = array_values($files);
```

### 2. ResponseTest.php:84,96,139 - Redundant Assertions
**Issue**: Assertions that always evaluate to true due to PHPDoc types
**Lines**: 84, 96, 139
**Fix**: Remove redundant `assertIsArray()` and `assertIsString()` assertions since types are already guaranteed

### 3. ServerRequestTest.php:139 - Intentional Invalid Type
**Issue**: Test intentionally passes string to verify exception, but PHPStan flags it
**Fix**: Add PHPStan ignore comment above line 138:
```php
/** @phpstan-ignore argument.type */
$this->expectException(\InvalidArgumentException::class);
```

### 4. EntryTest.php:20 - Missing Property Type
**Issue**: Property `$repository` has no type specified
**Fix**: Add type hint:
```php
private HarFileRepository $repository;
```

### 5. DateFormatInterfaceHandlerTest.php:27 - Redundant Assertion
**Issue**: `assertIsArray()` with already-narrowed type
**Fix**: Remove the redundant assertion, keep only the meaningful assertions

### 6. TruncatingDateTimeHandlerTest.php:25 - Redundant Assertion
**Issue**: `assertIsArray()` with already-narrowed type
**Fix**: Remove the redundant assertion, keep only the meaningful assertions

### 7. HarTest.php - Missing Type Specifications
**Issues**:
- Line 34: Method `fixtureDataProvider()` has no return type
- Line 121: Parameter `$a` has no iterable value type
- Line 134: Parameter `$a` has no iterable value type

**Fixes**:
- Add return type: `public static function fixtureDataProvider(): \Generator`
- Add parameter type: `private function removeCustomFields(array &$a): void` → type hint array as `@param array<mixed> $a`
- Add parameter type: `private function normalizeDateTime(array &$a): void` → type hint array as `@param array<mixed> $a`

### 8. LogTest.php:31,68 - Wrong Type Passed
**Issue**: Test passes `Creator` object to `setBrowser()` which expects `Browser`
**Lines**: 31, 68
**Fix**: Change from `->setBrowser($creator)` to `->setBrowser($browser)` to use the correct object

### 9. HarFileRepositoryTest.php:27,33,43,91 - Redundant Assertions
**Issue**: Assertions that always evaluate to true
**Fix**: Remove redundant assertions at these lines

## Implementation Order

1. Fix type hints and annotations (quick wins)
2. Fix wrong object usage in LogTest.php (bug fix)
3. Remove redundant assertions (cleanup)
4. Add PHPStan ignore for intentional test case

## Verification

After all fixes, run:
```bash
vendor/bin/phpstan analyse
```

Should complete with no errors.
