# Laravel Marketplace

Marketplace order orchestration and vendor order attribution for Laravel applications.

## Requirements
- PHP 8.3+
- Laravel 12 or 13

## Installation
```bash
composer require itmosoft/laravel-marketplace
php artisan vendor:publish --tag=itmosoft-marketplace-config
php artisan migrate
```

## Record vendor allocations
The package does not assume the schema of your order, vendor, or catalog packages. Pass Eloquent models explicitly.

```php
use ItmoSoft\Marketplace\MarketplaceManager;

$marketplace = app(MarketplaceManager::class);

$vendorOrders = $marketplace->recordOrder($order, [
    [
        'vendor' => $vendorA,
        'subtotal' => '125.5000',
        'currency' => 'USD',
        'items' => [
            ['source_item' => $orderItemA, 'quantity' => 2, 'unit_price' => '50.0000'],
            ['source_item' => $orderItemB, 'quantity' => 1, 'unit_price' => '25.5000'],
        ],
    ],
]);
```

Each source-order/vendor pair is unique. Repeating the same allocation refreshes item snapshots instead of duplicating them.

## Vendor order lifecycle
Allowed transitions:
- pending → accepted or cancelled
- accepted → processing or cancelled
- processing → completed or cancelled

```php
$marketplace->updateStatus($vendorOrders->first(), 'accepted');
$marketplace->updateStatus($vendorOrders->first(), 'processing');
$marketplace->updateStatus($vendorOrders->first(), 'completed');
```

## Queries
```php
$ordersForVendor = $marketplace->ordersForVendor($vendor);
$vendorSplits = $marketplace->vendorOrdersFor($order);
$vendorOrder = $marketplace->findVendorOrder($id);
```

## Tables
- `itmosoft_marketplace_vendor_orders`
- `itmosoft_marketplace_vendor_order_items`

Source order, vendor, and source item are polymorphic references. No foreign keys are created to tables owned by other packages.

## Integration boundaries
This package owns vendor order attribution and item snapshots. It does not own vendor profiles, catalog products, inventory reservation, commission calculations, payouts, payment capture, shipping, or customer order creation. The host application coordinates those packages and validates allocated quantities and subtotals against its source order.

## Testing
```bash
composer install
composer test
```

MIT License.
