<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $beds = Bed::with('ward','currentPatient')->orderBy('ward_id')->get();
    return response()->json(['status' => 'success', 'data' => $beds]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ward_id' => 'required|exists:wards,id',
            'bed_number' => 'required|string|max:50',
            'bed_type' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $bed = Bed::create($validator->validated());
        return response()->json(['status' => 'success', 'data' => $bed], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $bed = Bed::with('ward','currentPatient')->findOrFail($id);
    return response()->json(['status' => 'success', 'data' => $bed]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bed = Bed::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'bed_number' => 'nullable|string|max:50',
            'bed_type' => 'nullable|string',
            'is_occupied' => 'nullable|boolean',
            'current_patient_id' => 'nullable|exists:patients,id',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $bed->update($validator->validated());
        return response()->json(['status' => 'success', 'data' => $bed]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bed = Bed::findOrFail($id);
        $bed->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }

    public function assign(Request $request, Bed $bed)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        if ($bed->is_occupied) {
            return response()->json(['status' => 'error', 'message' => 'Bed already occupied'], 400);
        }

        DB::transaction(function () use ($bed, $request) {
            $bed->update(['current_patient_id' => $request->patient_id, 'is_occupied' => true]);
            $ward = $bed->ward;
            if ($ward) {
                $ward->decrement('available_beds');
            }
        });

        return response()->json(['status' => 'success', 'data' => $bed->fresh()]);
    }

    public function vacate(Request $request, Bed $bed)
    {
        if (!$bed->is_occupied) return response()->json(['status'=>'error','message'=>'Bed not occupied'], 400);

        DB::transaction(function () use ($bed) {
            $bed->update(['current_patient_id' => null, 'is_occupied' => false]);
            $ward = $bed->ward;
            if ($ward) {
                $ward->increment('available_beds');
            }
        });

        return response()->json(['status' => 'success', 'data' => $bed->fresh()]);
    }
}
