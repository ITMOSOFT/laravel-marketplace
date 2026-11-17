# Release instructions

## Repository
- Name: `laravel-marketplace`
- Organization: `ITMOSOFT`
- Description: Marketplace order orchestration and vendor order attribution for Laravel applications.
- Composer package: `itmosoft/laravel-marketplace`

## Validate locally
```bash
composer validate --strict
composer install
composer test
```
Do not publish until all local tests pass and all four GitHub Actions matrix jobs are green.

## Push
```bash
git init
git branch -M main
git add .
git commit -m "feat: add Laravel marketplace order orchestration"
git remote add origin https://github.com/ITMOSOFT/laravel-marketplace.git
git push -u origin main
```

## Tag after CI passes
```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

## GitHub Release
Title: `v1.0.0 - Initial Release`

Description:
- Vendor-specific allocations from source Eloquent orders.
- Polymorphic references to source orders, vendors, and source items.
- Vendor order and item snapshots.
- Idempotent allocation recording per source order/vendor.
- Validated vendor order status transitions.
- Query vendor orders by source order or vendor.
- Configurable database table prefix.

PHP 8.3+, Laravel 12 and 13. MIT License.

## Packagist
Submit `https://github.com/ITMOSOFT/laravel-marketplace` at https://packagist.org/packages/submit.
