<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vital;
use Illuminate\Support\Facades\Validator;

class VitalController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'systolic_bp' => 'nullable|integer|min:50|max:300',
            'diastolic_bp' => 'nullable|integer|min:30|max:200',
            'heart_rate' => 'nullable|integer|min:30|max:250',
            'respiratory_rate' => 'nullable|integer|min:5|max:60',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:300',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $data = $validator->validated();
        $data['recorded_by'] = $request->user()?->id;
        $v = Vital::create($data);

        return response()->json(['status'=>'success','data'=>$v], 201);
    }
}
