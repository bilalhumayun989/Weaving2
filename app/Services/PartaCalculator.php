<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Fabric Costing worksheet (COSTING!B11:B14,E5:H12).
 * Fixed 100 lb per bag, regardless of cone quantity.
 * Calculation order intentionally mirrors Excel TRUNC and ROUND.
 * No yarn cost is posted to COGS or financial ledgers.
 */
final class PartaCalculator
{
    public const BAG_WEIGHT_LBS = 100;
    public const FORMULA_VERSION = 'fabric-costing-sheet-v1';

    private static function truncateFour(float $value): float
    {
        // Excel TRUNC truncates towards zero. Epsilon absorbs binary floating noise
        // at exact four-decimal boundaries without changing meaningful precision.
        return (int) ($value * 10000 + ($value >= 0 ? 0.00000001 : -0.00000001)) / 10000;
    }

    /**
     * Input names: quantity_metres, reed, picks, warp_count, weft_count, width,
     * rate_per_pick; optional warp_yarn_rate and weft_yarn_rate are per lb.
     */
    public static function calculate(array $input): array
    {
        foreach (['quantity_metres','reed','picks','warp_count','weft_count','width'] as $field) {
            if (!isset($input[$field]) || !is_numeric($input[$field]) || !is_finite((float)$input[$field]) || (float) $input[$field] <= 0) {
                throw new InvalidArgumentException("{$field} must be greater than zero.");
            }
        }
        if (!isset($input['rate_per_pick']) || !is_numeric($input['rate_per_pick']) || !is_finite((float)$input['rate_per_pick']) || (float)$input['rate_per_pick'] < 0) {
            throw new InvalidArgumentException('rate_per_pick must be zero or greater.');
        }
        $quantity=(float)$input['quantity_metres'];
        $reed=(float)$input['reed'];
        $picks=(float)$input['picks'];
        $warpCount=(float)$input['warp_count'];
        $weftCount=(float)$input['weft_count'];
        $width=(float)$input['width'];
        $conversionRate=(float)$input['rate_per_pick'];

        // Exact source formula: =TRUNC(B2*B6/B4/20*1.0936/40,4)
        $warpLbsPerMetre=self::truncateFour($reed*$width/$warpCount/20*1.0936/40);
        // Exact source formula: =TRUNC(B3*B6/B5/20*1.0936/40,4)
        $weftLbsPerMetre=self::truncateFour($picks*$width/$weftCount/20*1.0936/40);
        // Excel =ROUND(E5/40*B1/100,2) where E5 = B11*40
        $warpBags=round($warpLbsPerMetre*$quantity/self::BAG_WEIGHT_LBS,2,PHP_ROUND_HALF_UP);
        $weftBags=round($weftLbsPerMetre*$quantity/self::BAG_WEIGHT_LBS,2,PHP_ROUND_HALF_UP);
        $conversionPerMetre=self::truncateFour($conversionRate*$picks);

        $warpRate=self::optionalNonnegative($input,'warp_yarn_rate');
        $weftRate=self::optionalNonnegative($input,'weft_yarn_rate');
        $warpCost=$warpRate!==null ? self::truncateFour($warpLbsPerMetre*$warpRate) : null;
        $weftCost=$weftRate!==null ? self::truncateFour($weftLbsPerMetre*$weftRate) : null;
        $yarnCost=($warpCost!==null && $weftCost!==null) ? $warpCost+$weftCost : null;
        $fabricRate=$yarnCost!==null ? round($yarnCost+$conversionPerMetre,4) : null;

        return [
            'formula_version'=>self::FORMULA_VERSION,
            'bag_weight_lbs'=>self::BAG_WEIGHT_LBS,
            'inputs'=>[
                'quantity_metres'=>$quantity,'reed'=>$reed,'picks'=>$picks,
                'warp_count'=>$warpCount,'weft_count'=>$weftCount,'width'=>$width,
                'rate_per_pick'=>$conversionRate,'warp_yarn_rate'=>$warpRate,'weft_yarn_rate'=>$weftRate,
            ],
            'results'=>[
                'warp_lbs_per_metre'=>$warpLbsPerMetre,
                'weft_lbs_per_metre'=>$weftLbsPerMetre,
                'total_lbs_per_metre'=>round($warpLbsPerMetre+$weftLbsPerMetre,4),
                'warp_bags'=>$warpBags,'weft_bags'=>$weftBags,
                'total_required_bags'=>round($warpBags+$weftBags,2),
                'conversion_per_metre'=>$conversionPerMetre,
                'warp_yarn_cost_per_metre'=>$warpCost,
                'weft_yarn_cost_per_metre'=>$weftCost,
                'total_yarn_cost_per_metre'=>$yarnCost,
                'estimated_fabric_rate_per_metre'=>$fabricRate,
                'estimated_fabric_value'=>$fabricRate!==null ? round($quantity*$fabricRate,2,PHP_ROUND_HALF_UP) : null,
                'estimated_conversion_value'=>round($quantity*$conversionPerMetre,2,PHP_ROUND_HALF_UP),
            ],
        ];
    }

    private static function optionalNonnegative(array $input, string $field): ?float
    {
        if (!isset($input[$field]) || $input[$field] === '') return null;
        if (!is_numeric($input[$field]) || !is_finite((float)$input[$field]) || (float)$input[$field] < 0) {
            throw new InvalidArgumentException("{$field} must be zero or greater.");
        }
        return (float)$input[$field];
    }
}
