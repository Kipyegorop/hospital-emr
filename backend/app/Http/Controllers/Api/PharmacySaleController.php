<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmacySaleController extends Controller
{
    /**
     * Return a pharmacy sale (create a return record)
     */
    public function returnSale(Request $request, PharmacySale $pharmacySale)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Simple return handling: mark sale as returned and record returned amount
        $returnQty = min($data['quantity'], $pharmacySale->quantity_sold);
        $returnedAmount = ($pharmacySale->unit_price ?? 0) * $returnQty;

        $pharmacySale->update([
            'status' => 'returned',
            'returned_amount' => $returnedAmount,
            'return_reason' => $data['reason'],
        ]);

        // Replenish stock if tracked
        if ($pharmacySale->medication) {
            $med = $pharmacySale->medication;
            $med->increment('current_stock', $returnQty);
        }

        return response()->json(['status' => 'success', 'data' => ['pharmacy_sale' => $pharmacySale]]);
    }
}
