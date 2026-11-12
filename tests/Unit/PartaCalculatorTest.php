<?php

namespace Tests\Unit;

use App\Services\PartaCalculator;
use PHPUnit\Framework\TestCase;

class PartaCalculatorTest extends TestCase
{
    private function worksheetInput(): array
    {
        return [
            'quantity_metres'=>66260,'reed'=>90,'picks'=>66,
            'warp_count'=>58.5,'weft_count'=>58.5,'width'=>63.5,
            'rate_per_pick'=>0.53,'warp_yarn_rate'=>600,'weft_yarn_rate'=>600,
        ];
    }
    public function test_matches_uploaded_fabric_costing_worksheet(): void
    {
        $s=PartaCalculator::calculate($this->worksheetInput());
        $v=$s['results'];
        $this->assertSame(100,$s['bag_weight_lbs']);
        $this->assertEqualsWithDelta(.1335,$v['warp_lbs_per_metre'],0.000001);
        $this->assertEqualsWithDelta(.0979,$v['weft_lbs_per_metre'],0.000001);
        $this->assertEqualsWithDelta(88.46,$v['warp_bags'],0.000001);
        $this->assertEqualsWithDelta(64.87,$v['weft_bags'],0.000001);
        $this->assertEqualsWithDelta(153.33,$v['total_required_bags'],0.000001);
        $this->assertEqualsWithDelta(80.10,$v['warp_yarn_cost_per_metre'],0.000001);
        $this->assertEqualsWithDelta(58.74,$v['weft_yarn_cost_per_metre'],0.000001);
        $this->assertEqualsWithDelta(34.98,$v['conversion_per_metre'],0.000001);
        $this->assertEqualsWithDelta(173.82,$v['estimated_fabric_rate_per_metre'],0.000001);
        $this->assertEqualsWithDelta(11517313.20,$v['estimated_fabric_value'],0.01);
    }
    public function test_yarn_prices_are_optional_and_do_not_change_required_bags(): void
    {
        $a=$this->worksheetInput();
        $expected=PartaCalculator::calculate($a)['results'];
        unset($a['warp_yarn_rate'],$a['weft_yarn_rate']);
        $actual=PartaCalculator::calculate($a)['results'];
        $this->assertSame($expected['total_required_bags'],$actual['total_required_bags']);
        $this->assertNull($actual['estimated_fabric_rate_per_metre']);
        $this->assertSame(34.98,$actual['conversion_per_metre']);
    }
    public function test_cannot_calculate_zero_count(): void
    {
        $a=$this->worksheetInput();$a['warp_count']=0;
        $this->expectException(\InvalidArgumentException::class);
        PartaCalculator::calculate($a);
    }
}
