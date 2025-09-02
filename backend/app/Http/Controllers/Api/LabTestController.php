<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LabTestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $query = LabTest::with('patient');
    if (request()->has('patient_id')) $query->where('patient_id', request('patient_id'));
    if (request()->has('status')) $query->where('status', request('status'));
    $tests = $query->orderBy('requested_at','desc')->paginate(50);
    return response()->json(['status'=>'success','data'=>$tests]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'test_type' => 'required|string',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()],422);

        $labTest = null;
        DB::transaction(function () use ($request, &$labTest) {
            $labTest = LabTest::create([
                'order_number' => strtoupper('LT'.time()),
                'patient_id' => $request->patient_id,
                'test_type' => $request->test_type,
                'priority' => $request->priority ?? 'normal',
                'status' => 'requested',
                'requested_at' => now(),
                'requested_by' => $request->user()->id,
            ]);
        });

        return response()->json(['status'=>'success','data'=>$labTest],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $test = LabTest::with('patient')->findOrFail($id);
    return response()->json(['status'=>'success','data'=>$test]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $test = LabTest::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'result' => 'nullable|string',
            'report_notes' => 'nullable|string',
            'status' => 'nullable|in:requested,collected,completed,reported,cancelled',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()],422);

        $test->update($validator->validated());
        return response()->json(['status'=>'success','data'=>$test]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $test = LabTest::findOrFail($id);
        $test->delete();
        return response()->json(['status'=>'success','message'=>'Deleted']);
    }

    public function collect(LabTest $labTest)
    {
        $labTest->update(['status'=>'collected','collected_at'=>now()]);
        return response()->json(['status'=>'success','data'=>$labTest]);
    }

    public function complete(LabTest $labTest)
    {
        $labTest->update(['status'=>'completed','completed_at'=>now()]);
        return response()->json(['status'=>'success','data'=>$labTest]);
    }

    public function report(Request $request, LabTest $labTest)
    {
        $validator = Validator::make($request->all(), [
            'result' => 'required|string',
            'report_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()],422);

        $labTest->update(['status'=>'reported','result'=>$request->result,'report_notes'=>$request->report_notes,'reported_by'=>$request->user()->id]);
        return response()->json(['status'=>'success','data'=>$labTest]);
    }
}
