<?php
namespace ItmoSoft\Marketplace\Tests;
use ItmoSoft\Marketplace\MarketplaceManager;
class MarketplaceManagerTest extends TestCase
{
    public function test_it_records_vendor_allocation(): void {
        [$order,$vendor,$item]=$this->fixture();
        $result=app(MarketplaceManager::class)->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'20','items'=>[['source_item'=>$item,'quantity'=>2,'unit_price'=>'10']]]]);
        $this->assertCount(1,$result); $this->assertSame('pending',$result[0]->status); $this->assertCount(1,$result[0]->items);
    }
    public function test_it_records_multiple_vendors(): void {
        [$order,$vendor,$item]=$this->fixture(); $vendor2=TestVendor::create(['name'=>'Vendor 2']); $item2=TestOrderItem::create(['name'=>'Item 2']);
        $result=app(MarketplaceManager::class)->recordOrder($order,[
            ['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]],
            ['vendor'=>$vendor2,'subtotal'=>'20','items'=>[['source_item'=>$item2,'quantity'=>1,'unit_price'=>'20']]],
        ]);
        $this->assertCount(2,$result); $this->assertDatabaseCount('itmosoft_marketplace_vendor_orders',2);
    }
    public function test_it_is_idempotent_for_repeated_vendor_allocation(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $data=[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]];
        $manager->recordOrder($order,$data); $manager->recordOrder($order,$data);
        $this->assertDatabaseCount('itmosoft_marketplace_vendor_orders',1); $this->assertDatabaseCount('itmosoft_marketplace_vendor_order_items',1);
    }
    public function test_it_refreshes_item_snapshots_on_retry(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]]);
        $manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'24','items'=>[['source_item'=>$item,'quantity'=>2,'unit_price'=>'12']]]]);
        $this->assertDatabaseHas('itmosoft_marketplace_vendor_order_items',['quantity'=>2,'unit_price'=>'12.0000','line_total'=>'24.0000']);
    }
    public function test_it_rejects_empty_allocations(): void {
        [$order]=$this->fixture(); $this->expectException(\InvalidArgumentException::class); app(MarketplaceManager::class)->recordOrder($order,[]);
    }
    public function test_it_rejects_invalid_currency(): void {
        [$order,$vendor,$item]=$this->fixture(); $this->expectException(\InvalidArgumentException::class);
        app(MarketplaceManager::class)->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','currency'=>'US','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]]);
    }
    public function test_it_rejects_invalid_quantity(): void {
        [$order,$vendor,$item]=$this->fixture(); $this->expectException(\InvalidArgumentException::class);
        app(MarketplaceManager::class)->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>0,'unit_price'=>'10']]]]);
    }
    public function test_it_rejects_negative_subtotal(): void {
        [$order,$vendor,$item]=$this->fixture(); $this->expectException(\InvalidArgumentException::class);
        app(MarketplaceManager::class)->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'-1','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]]);
    }
    public function test_it_transitions_vendor_order_status(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $record=$manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]])->first();
        $manager->updateStatus($record,'accepted'); $manager->updateStatus($record,'processing');
        $this->assertSame('completed',$manager->updateStatus($record,'completed')->status);
    }
    public function test_it_rejects_invalid_status_transition(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $record=$manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]])->first();
        $this->expectException(\InvalidArgumentException::class); $manager->updateStatus($record,'completed');
    }
    public function test_it_queries_orders_by_vendor_and_source_order(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]]);
        $this->assertCount(1,$manager->vendorOrdersFor($order)); $this->assertCount(1,$manager->ordersForVendor($vendor));
    }
    public function test_it_finds_vendor_order_by_id(): void {
        [$order,$vendor,$item]=$this->fixture(); $manager=app(MarketplaceManager::class);
        $record=$manager->recordOrder($order,[['vendor'=>$vendor,'subtotal'=>'10','items'=>[['source_item'=>$item,'quantity'=>1,'unit_price'=>'10']]]])->first();
        $this->assertSame($record->id,$manager->findVendorOrder($record->id)->id);
    }
}
