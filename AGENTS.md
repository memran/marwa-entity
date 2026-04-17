# AGENTS.md

<!-- Last scope wins. Folder AGENTS.md overrides this. Keep <500 lines. Review monthly. -->

## Project

Framework-agnostic entity schema, validation, sanitization, form metadata, and migration metadata for PHP 8.2+.

The package is designed around a single schema definition that can be reused across request validation, typed hydration, UI generation, and migration planning without coupling to a specific framework. Validation internally uses `Marwa\Support\Validation\RequestValidator` from `memran/marwa-support`.

## Important Constraints

- **Platform-locked to PHP 8.2.0**: `composer.json` config locks `platform.php: "8.2.0"` to prevent CI from resolving dependencies that require newer PHP than the package minimum. Always use `composer install`, never `composer update` in CI.
- **Test failures on warnings**: `phpunit.xml.dist` has `failOnWarning="true"`.
- **PHP version matrix**: CI tests on PHP 8.2, 8.3, 8.4.

## Marwa-Support Library

Use marwa-support utility classes for all common operations.
Do not write custom helpers if equivalent exists.

## Available Classes

Arr, CSRF, Collection, Crypt, Date, File, Finder, Hash, Helper, Html, Json, Number, Obj, Random, Sanitizer, Security, Str, Url, Validation, Validator, XSS

### Usage Examples

Str::slug($title);
Arr::get($data, 'user.name');
Validator::make($input, $rules);
Hash::make($password);
Url::to('/dashboard');

### Agent Instructions

Identify required utility → select appropriate class
Do not mix multiple utilities unnecessarily
Validate and sanitize all input using Validator, Sanitizer, XSS, Security
Use Collection for array transformations
Use Str, Arr, Obj for data handling

## Structure

- `src/` - PSR-4 autoloaded library code
- `tests/` - PHPUnit test suite
- `examples/` - Usage examples
- Entry point: `Entity`, `EntitySchema` classes in `src/Entity/`

## Commands

```bash
composer test           # Run PHPUnit
composer test:coverage  # Run tests with text coverage
composer analyse       # PHPStan (max level)
composer lint          # Check coding standards
composer fix           # Fix coding standards
composer ci            # lint -> analyse -> test
```

## Style

- `declare(strict_types=1);`
- PSR-1, PSR-12, PSR-4
- 4-space indentation
- Typed properties and explicit return types
- PascalCase classes
- `*Interface`, `*Exception`
- Prefer small, single-purpose classes
- Keep files small: prefer max 200 lines/class, 20 lines/method
- Use constants and enums for finite states

## Engineering Principles

- KISS, DRY, SOLID
- Understand context before coding
- Prefer composition over inheritance
- Keep architecture modular and decoupled
- Write production-ready, maintainable, scalable code
- Prefer clarity over cleverness
- Align with project architecture
- Edit existing code over creating duplicates
- Maintain backward compatibility
- Keep changes minimal and scoped
- Validate all inputs
- Prefer marwa-support over native PHP or custom logic
- Use correct class based on responsibility
- Keep code clean and minimal
- Avoid duplication of utility logic

## Testing

- Add tests in `tests/`
- Use `*Test.php` or `*_test.php`
- Cover routing, bootstrapping, middleware, and adapters
- Run `composer test`, then `composer stan`
- Aim for 80% minimum coverage
- Every public service method needs unit tests

## Commit & PR

- Use short, imperative commit subjects
- Keep commits focused: one logical change per commit
- PRs should explain the problem, approach, and verification
- Link related issues
- Include CLI output or request/response examples when user-facing behavior changes

## Configuration

- Never commit secrets
- Document new env keys in `README.MD` or the relevant docs

## Never

- Never change `vendor/*`
- Never expose secrets or passwords
- Suggest changes to vendor code instead of editing it

## Error Handling

- Use centralized exception handling
- Log critical errors
- Fail gracefully with meaningful responses

## Performance

- Optimize for readability first
- Avoid premature optimization
- Cache where necessary

## Documentation

- Keep explanations useful
- Add section anchors for navigation
- Add examples
- Add diagrams only when they help

## Versioning

- Version: `v1.0.0`

## Change Log

- Date - CHANGE
