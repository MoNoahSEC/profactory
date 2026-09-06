<?php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductionOrder;

class CostCalculationService
{
    /**
     * حساب تكلفة تصنيع منتج واحد
     */
    public function calculateProductCost(Product $product): array
    {
        // تكلفة الخامات
        $materialCost = $product->materials->sum(function ($material) {
            return $material->pivot->quantity_needed * $material->unit_cost;
        });

        // تكاليف الموظفينة والمصاريف اليدوية المحددة للمنتج
        $laborCost    = $product->labor_cost ?? 0;
        $overheadCost = $product->overhead_cost ?? 0;

        $totalCost    = $materialCost + $laborCost + $overheadCost;

        $profitAmount = $product->selling_price - $totalCost;
        $profitMargin = $product->selling_price > 0
            ? ($profitAmount / $product->selling_price) * 100
            : 0;

        return [
            'material_cost'  => round($materialCost, 2),
            'labor_cost'     => round($laborCost, 2),
            'overhead_cost'  => round($overheadCost, 2),
            'total_cost'     => round($totalCost, 2),
            'selling_price'  => $product->selling_price,
            'profit_amount'  => round($profitAmount, 2),
            'profit_margin'  => round($profitMargin, 2),
        ];
    }

    /**
     * حساب تكلفة أمر إنتاج كامل
     */
    public function calculateOrderCost(ProductionOrder $order): array
    {
        $unitCost     = $this->calculateProductCost($order->product);
        $quantity     = $order->quantity_ordered;

        return [
            'material_cost'   => $unitCost['material_cost'] * $quantity,
            'labor_cost'      => $unitCost['labor_cost'] * $quantity,
            'overhead_cost'   => $unitCost['overhead_cost'] * $quantity,
            'total_cost'      => $unitCost['total_cost'] * $quantity,
            'expected_revenue'=> $unitCost['selling_price'] * $quantity,
            'expected_profit' => $unitCost['profit_amount'] * $quantity,
            'cost_per_unit'   => $unitCost['total_cost'],
        ];
    }
}

