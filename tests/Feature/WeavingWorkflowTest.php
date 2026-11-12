<?php

namespace Tests\Feature;

use App\Models\{Bill, Contract, Factory, FabricQuality, Party, Thaan, KachiParchi};
use App\Services\LedgerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeavingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $this->actingAs(User::factory()->create());
        $f1=Factory::create(['name'=>'Factory A']);
        $f2=Factory::create(['name'=>'Factory B']);
        $party=Party::create(['name'=>'Customer One','type'=>'both']);
        $quality=FabricQuality::create(['name'=>'Grey 60 Picks','picks'=>60]);
        return compact('f1','f2','party','quality');
    }

    public function test_job_contract_calculates_parta_bags_server_side_and_ignores_forged_manual_bags(): void
    {
        extract($this->fixtures());
        $quality->update(['picks'=>66]);
        $this->post(route('contracts.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,
            'fabric_quality_id'=>$quality->id,'type'=>'job_work',
            'contract_date'=>'2026-11-11','target_metres'=>66260,
            'rate_per_pick'=>0.53,'parta_reed'=>90,
            'parta_warp_count'=>58.5,'parta_weft_count'=>58.5,'parta_width'=>63.5,
            'parta_warp_yarn_rate'=>600,'parta_weft_yarn_rate'=>600,
            'required_bags'=>999999, // Must never override verified server output.
        ])->assertSessionHasNoErrors();
        $c=Contract::latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(153.33,(float)$c->required_bags,0.0001);
        $this->assertEqualsWithDelta(88.46,$c->parta_snapshot['results']['warp_bags'],0.0001);
        $this->assertEqualsWithDelta(64.87,$c->parta_snapshot['results']['weft_bags'],0.0001);
        $this->assertEqualsWithDelta(173.82,$c->parta_snapshot['results']['estimated_fabric_rate_per_metre'],0.0001);
        $quality->update(['picks'=>100]);
        $this->assertEqualsWithDelta(66,(float)$c->fresh()->picks_at_contract,0.0001);
        $this->post(route('contracts.bags.store',$c),[
            'received_on'=>'2026-11-12','bags'=>24,'cones_per_bag'=>24,
        ])->assertSessionHasNoErrors();
        $this->post(route('contracts.bags.store',$c),[
            'received_on'=>'2026-11-13','bags'=>40,'cones_per_bag'=>40,
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(64.0,(float)$c->receipts()->sum('bags'),0.0001);
        $this->assertEqualsWithDelta(89.33,(float)$c->required_bags-(float)$c->receipts()->sum('bags'),0.0001);
    }

    public function test_job_contract_requires_parta_inputs_and_target_quantity(): void
    {
        extract($this->fixtures());
        $this->post(route('contracts.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,
            'fabric_quality_id'=>$quality->id,'type'=>'job_work',
            'contract_date'=>'2026-11-11','rate_per_pick'=>0.5,
        ])->assertSessionHasErrors(['target_metres','parta_reed','parta_warp_count','parta_weft_count','parta_width']);
    }

    public function test_own_stock_sells_only_on_pakki_accepted_metres(): void
    {
        extract($this->fixtures());
        $this->post(route('fabric.checking.store'), [
            'factory_id'=>$f1->id,'fabric_quality_id'=>$quality->id,'entry_type'=>'own_stock',
            'checked_on'=>'2026-11-11','thaans'=>"AA01, 50\nAA02, 70",
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('thaans',2);
        $ids=Thaan::orderBy('id')->pluck('id')->all();
        $this->post(route('dispatch.kachi.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,'kind'=>'fabric_sale',
            'dispatched_on'=>'2026-11-11','rate_per_metre'=>200,'thaan_ids'=>$ids,
        ])->assertSessionHasNoErrors();
        $k=KachiParchi::firstOrFail();
        $this->assertEquals(0, (new LedgerService())->balances($party)['receivable']);
        $this->post(route('dispatch.pakki.store',$k),[
            'accepted_on'=>'2026-11-12','accepted_metres'=>120,'rejected_metres'=>0,'accepted_thaan_count'=>2,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(24000, Bill::firstOrFail()->total);
        $this->assertEquals(24000, (new LedgerService())->balances($party)['receivable']);
    }

    public function test_complete_thaan_rejection_and_redispatch_without_double_counting(): void
    {
        extract($this->fixtures());
        $c=Contract::create([
            'reference'=>'TEST-JOB-001','factory_id'=>$f1->id,'party_id'=>$party->id,
            'fabric_quality_id'=>$quality->id,'type'=>'job_work','rate_per_pick'=>0.5,
            'picks_at_contract'=>60,'required_bags'=>100,'contract_date'=>'2026-11-11',
        ]);
        $this->post(route('fabric.checking.store'),[
            'factory_id'=>$f1->id,'fabric_quality_id'=>$quality->id,'contract_id'=>$c->id,
            'entry_type'=>'job_work','checked_on'=>'2026-11-11','thaans'=>"JW001, 40\nJW002, 60",
        ])->assertSessionHasNoErrors();
        $ids=Thaan::orderBy('id')->pluck('id')->all();
        $this->post(route('dispatch.kachi.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,'contract_id'=>$c->id,
            'kind'=>'job_work','dispatched_on'=>'2026-11-11','thaan_ids'=>$ids,
        ])->assertSessionHasNoErrors();
        $k=KachiParchi::firstOrFail();
        $this->post(route('dispatch.pakki.store',$k),[
            'accepted_on'=>'2026-11-12','accepted_metres'=>60,'rejected_metres'=>40,'accepted_thaan_count'=>1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(1800, Bill::firstOrFail()->total);
        $this->post(route('dispatch.returns.store',$k),['thaan_ids'=>[$ids[0]]])->assertSessionHasNoErrors();
        $this->assertEquals('available',Thaan::findOrFail($ids[0])->status);
        $this->post(route('dispatch.kachi.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,'contract_id'=>$c->id,
            'kind'=>'job_work','dispatched_on'=>'2026-11-13','thaan_ids'=>[$ids[0]],
        ])->assertSessionHasNoErrors();
        $new=KachiParchi::latest('id')->firstOrFail();
        $this->post(route('dispatch.pakki.store',$new),[
            'accepted_on'=>'2026-11-14','accepted_metres'=>40,'rejected_metres'=>0,'accepted_thaan_count'=>1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(3000, (new LedgerService())->balances($party)['receivable']);
    }

    public function test_combined_party_ledger_across_factory_names(): void
    {
        extract($this->fixtures());
        $ledger=new LedgerService();
        $ledger->post($party->id,$f1->id,'receivable','2026-11-10','Factory A sale','test',1,1000,0);
        $ledger->post($party->id,$f2->id,'receivable','2026-11-11','Factory B sale','test',2,2500,0);
        $this->assertEquals(3500,$ledger->balances($party)['receivable']);
        $this->assertCount(2,$ledger->timeline($party,'receivable')['rows']);
    }
    public function test_admin_can_reverse_payment_without_erasing_ledger_history(): void
    {
        extract($this->fixtures());
        auth()->user()->forceFill(['role'=>'admin'])->save();
        $ledger=new LedgerService();
        $ledger->post($party->id,$f1->id,'receivable','2026-11-11','Opening sale','test',301,10000,0);
        $this->post(route('accounts.payments.store'),[
            'party_id'=>$party->id,'factory_id'=>$f1->id,'kind'=>'received',
            'paid_on'=>'2026-11-12','amount'=>4000,'reference'=>'BANK-100',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(6000,$ledger->balances($party)['receivable']);
        $payment=\App\Models\Payment::firstOrFail();
        $this->post(route('corrections.payment',$payment),[
            'reason'=>'Entered duplicate receipt by mistake',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(10000,$ledger->balances($party)['receivable']);
        $this->assertDatabaseCount('ledger_entries',3);
        $this->assertNotNull($payment->fresh()->voided_at);
        $this->post(route('corrections.payment',$payment),[
            'reason'=>'Attempting to reverse twice',
        ])->assertSessionHasErrors();
    }

    public function test_only_admin_can_reverse_posted_purchase(): void
    {
        extract($this->fixtures());
        $this->post(route('yarn.purchases.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,'purchased_on'=>'2026-11-11',
            'yarn_description'=>'30s yarn','bags'=>10,'rate_per_bag'=>2000,
        ])->assertSessionHasNoErrors();
        $purchase=\App\Models\YarnPurchase::firstOrFail();
        $ledger=new LedgerService();
        $this->assertEquals(20000,$ledger->balances($party)['payable']);
        $this->post(route('corrections.purchase',$purchase),[
            'reason'=>'Duplicated supplier invoice',
        ])->assertForbidden();
        auth()->user()->forceFill(['role'=>'admin'])->save();
        $this->post(route('corrections.purchase',$purchase),[
            'reason'=>'Duplicated supplier invoice',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(0,$ledger->balances($party)['payable']);
    }

    public function test_new_fabric_rate_contract_can_sell_preexisting_own_stock(): void
    {
        extract($this->fixtures());
        $this->post(route('fabric.checking.store'),[
            'factory_id'=>$f1->id,'fabric_quality_id'=>$quality->id,
            'entry_type'=>'own_stock','checked_on'=>'2026-11-11','thaans'=>'A300, 80',
        ])->assertSessionHasNoErrors();
        $c=Contract::create([
            'reference'=>'SALE-LATER','factory_id'=>$f1->id,'party_id'=>$party->id,
            'fabric_quality_id'=>$quality->id,'type'=>'fabric_rate',
            'rate_per_metre'=>145,'contract_date'=>'2026-11-11',
        ]);
        $this->post(route('dispatch.kachi.store'),[
            'factory_id'=>$f1->id,'party_id'=>$party->id,'kind'=>'fabric_sale',
            'contract_id'=>$c->id,'dispatched_on'=>'2026-11-12',
            'thaan_ids'=>[Thaan::firstOrFail()->id],
        ])->assertSessionHasNoErrors();
        $k=KachiParchi::firstOrFail();
        $this->post(route('dispatch.pakki.store',$k),[
            'accepted_on'=>'2026-11-13','accepted_metres'=>80,
            'rejected_metres'=>0,'accepted_thaan_count'=>1,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(11600,Bill::firstOrFail()->total);
    }

    public function test_unsent_checking_can_be_cancelled_but_dispatched_checking_cannot(): void
    {
        extract($this->fixtures());
        auth()->user()->forceFill(['role'=>'admin'])->save();
        $this->post(route('fabric.checking.store'),[
            'factory_id'=>$f1->id,'fabric_quality_id'=>$quality->id,
            'entry_type'=>'own_stock','checked_on'=>'2026-11-11','thaans'=>'Q010, 99',
        ])->assertSessionHasNoErrors();
        $check=\App\Models\CheckingReport::firstOrFail();
        $this->post(route('corrections.checking',$check),[
            'reason'=>'Wrong Thaan length on checking',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('thaans',0);
        $this->assertNotNull($check->fresh()->voided_at);
    }

    public function test_production_recording_route_is_not_present(): void
    {
        extract($this->fixtures());
        $this->get('/production')->assertNotFound();
    }

}
