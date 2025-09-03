<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicationAdministration;
use Illuminate\Support\Facades\Validator;

class MedicationAdministrationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'medication_name' => 'required|string',
            'dose' => 'nullable|string',
            'route' => 'nullable|string',
            'frequency' => 'nullable|string',
            'administration_time' => 'nullable|date',
            'given' => 'nullable|boolean',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $data = $validator->validated();
        $data['administered_by'] = $request->user()?->id;
        $ma = MedicationAdministration::create($data);

        return response()->json(['status'=>'success','data'=>$ma], 201);
    }
}
