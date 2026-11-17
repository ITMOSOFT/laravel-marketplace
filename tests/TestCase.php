<?php
namespace ItmoSoft\Marketplace\Tests;
use Illuminate\Support\Facades\Schema;
use ItmoSoft\Marketplace\MarketplaceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array { return [MarketplaceServiceProvider::class]; }
    protected function defineEnvironment($app): void {
        $app['config']->set('database.default','testing');
        $app['config']->set('database.connections.testing',['driver'=>'sqlite','database'=>':memory:','prefix'=>'','foreign_key_constraints'=>true]);
        $app['config']->set('itmosoft-marketplace.table_prefix','itmosoft_');
    }
    protected function setUp(): void {
        parent::setUp();
        foreach (['test_orders','test_vendors','test_order_items'] as $name) Schema::create($name,function($table): void {$table->id();$table->string('name');$table->timestamps();});
        $this->artisan('migrate',['--database'=>'testing'])->run();
        $this->assertTrue(Schema::hasTable('itmosoft_marketplace_vendor_orders'));
        $this->assertTrue(Schema::hasTable('itmosoft_marketplace_vendor_order_items'));
    }
    protected function fixture(): array {
        return [TestOrder::create(['name'=>'Order']),TestVendor::create(['name'=>'Vendor']),TestOrderItem::create(['name'=>'Item'])];
    }
}
