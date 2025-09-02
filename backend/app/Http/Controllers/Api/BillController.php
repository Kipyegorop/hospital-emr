<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $query = Bill::with(['patient', 'creator'])->latest();
    $bills = $query->paginate(20);
    return response()->json($bills);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'bill_type' => 'required|string',
            'bill_date' => 'required|date',
            'subtotal' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'payment_method' => 'nullable|string',
            'payment_status' => 'nullable|string',
            'billable_items' => 'nullable|array',
        ]);

        $data['bill_number'] = 'BILL-'.Str::upper(Str::random(8));
        $data['created_by'] = $request->user()->id;
        $data['amount_paid'] = $data['amount_paid'] ?? 0.00;
        $data['discount_amount'] = $data['discount_amount'] ?? 0.00;
        $data['tax_amount'] = $data['tax_amount'] ?? 0.00;
        $data['balance_due'] = ($data['total_amount'] ?? 0) - ($data['amount_paid'] ?? 0);
        $data['status'] = $data['status'] ?? 'active';

        $bill = Bill::create($data);

        return response()->json($bill, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $bill = Bill::with(['patient', 'creator'])->findOrFail($id);
    return response()->json($bill);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bill = Bill::findOrFail($id);
        $data = $request->validate([
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'payment_notes' => 'nullable|string',
        ]);

        $bill->update($data);
        return response()->json($bill);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bill = Bill::findOrFail($id);
        $bill->status = 'cancelled';
        $bill->save();
        return response()->json(['message' => 'Bill cancelled.']);
    }

    public function patientBills($patient): JsonResponse
    {
        $bills = Bill::where('patient_id', $patient)->latest()->get();
        return response()->json($bills);
    }

    public function unpaid(): JsonResponse
    {
        $bills = Bill::where('payment_status', '!=', 'paid')->with('patient')->latest()->get();
        return response()->json($bills);
    }

    public function pay(Request $request, Bill $bill): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_reference' => 'nullable|string'
        ]);

        $bill->amount_paid += $data['amount'];
        $bill->balance_due = max(0, $bill->total_amount - $bill->amount_paid);
        $bill->payment_method = $data['payment_method'];
        $bill->transaction_reference = $data['transaction_reference'] ?? $bill->transaction_reference;
        $bill->paid_at = $bill->balance_due <= 0 ? now() : null;
        $bill->payment_status = $bill->balance_due <= 0 ? 'paid' : 'partial';
        $bill->save();

        return response()->json($bill);
    }

    public function waive(Request $request, Bill $bill): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.00',
            'reason' => 'nullable|string'
        ]);

        $bill->discount_amount += $data['amount'];
        $bill->balance_due = max(0, $bill->total_amount - $bill->amount_paid - $bill->discount_amount);
        if ($bill->balance_due <= 0) {
            $bill->payment_status = 'paid';
        }
        $bill->save();

        return response()->json($bill);
    }

    public function receipt(Bill $bill): JsonResponse
    {
        // Minimal JSON receipt for now
        $receipt = [
            'bill_number' => $bill->bill_number,
            'patient' => $bill->patient->only(['id', 'full_name', 'patient_number']),
            'total' => $bill->total_amount,
            'amount_paid' => $bill->amount_paid,
            'balance_due' => $bill->balance_due,
            'paid_at' => $bill->paid_at,
        ];

        return response()->json($receipt);
    }
}
