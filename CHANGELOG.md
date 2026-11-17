# Changelog

## [1.0.0] - 2026-11-17

### Added
- Vendor-specific order attribution from source Eloquent orders.
- Polymorphic source order, vendor, and source item references.
- Vendor order item quantity and price snapshots.
- Idempotent allocation recording per source-order/vendor pair.
- Vendor order lifecycle with validated status transitions.
- Query APIs for source orders and vendors.
- Configurable table prefix.
- PHPUnit suite and PHP 8.3/8.4 GitHub Actions matrix.

### Quality
- Use fixed-scale integer arithmetic when calculating item line totals.
