<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\MedicationStockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class MedicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Medication::query();
        if (request()->has('search')) {
            $q = request('search');
            $query->where('name', 'like', "%{$q}%")->orWhere('generic_name', 'like', "%{$q}%");
        }
        $medications = $query->orderBy('name')->paginate(50);
        return response()->json(['status' => 'success', 'data' => $medications]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'current_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $medication = Medication::create($validator->validated());
        return response()->json(['status' => 'success', 'data' => $medication], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $medication = Medication::findOrFail($id);
    return response()->json(['status' => 'success', 'data' => $medication]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $medication = Medication::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'current_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $medication->update($validator->validated());
        return response()->json(['status' => 'success', 'data' => $medication]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medication = Medication::findOrFail($id);
        $medication->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }

    public function search($query)
    {
        $medications = Medication::where('name', 'like', "%{$query}%")
            ->orWhere('generic_name', 'like', "%{$query}%")
            ->take(20)
            ->get();
        return response()->json(['status' => 'success', 'data' => $medications]);
    }

    public function lowStock()
    {
        $medications = Medication::lowStock()->orderBy('current_stock')->get();
        return response()->json(['status' => 'success', 'data' => $medications]);
    }

    public function expiring()
    {
        $medications = Medication::expiringSoon(30)->orderBy('expiry_date')->get();
        return response()->json(['status' => 'success', 'data' => $medications]);
    }

    /**
     * Adjust medication stock (increase or decrease)
     */
    public function adjustStock(Request $request, Medication $medication)
    {
        $validator = Validator::make($request->all(), [
            'adjustment' => 'required|integer', // positive to add, negative to remove
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $stockBefore = $medication->current_stock ?? 0;
        $newStock = max(0, $stockBefore + (int)$data['adjustment']);

        $medication->update(['current_stock' => $newStock]);

        // Persist structured stock adjustment record
        MedicationStockAdjustment::create([
            'medication_id' => $medication->id,
            'user_id' => $request->user()?->id,
            'stock_before' => $stockBefore,
            'adjustment' => (int)$data['adjustment'],
            'stock_after' => $newStock,
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'medication' => $medication,
                'stock_before' => $stockBefore,
                'stock_after' => $newStock,
            ]
        ]);
    }
}
