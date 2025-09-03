<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NursingAssessment;
use Illuminate\Support\Facades\Validator;

class NursingAssessmentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'assessment' => 'nullable|string',
            'care_plan' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $data = $validator->validated();
        $data['nurse_id'] = $request->user()?->id;
        $n = NursingAssessment::create($data);

        return response()->json(['status'=>'success','data'=>$n], 201);
    }
}
