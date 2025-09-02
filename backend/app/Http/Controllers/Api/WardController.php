<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class WardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $wards = Ward::with('beds')->orderBy('name')->get();
    return response()->json(['status' => 'success', 'data' => $wards]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:wards,code',
            'department_id' => 'required|exists:departments,id',
            'ward_type' => 'required',
            'total_beds' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $ward = Ward::create(array_merge($validator->validated(), ['available_beds' => $request->total_beds]));
        return response()->json(['status' => 'success', 'data' => $ward], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    $ward = Ward::with('beds')->findOrFail($id);
    return response()->json(['status' => 'success', 'data' => $ward]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ward = Ward::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:wards,code,'.$ward->id,
            'department_id' => 'nullable|exists:departments,id',
            'ward_type' => 'nullable',
            'total_beds' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) return response()->json(['status'=>'error','errors'=>$validator->errors()], 422);

        $ward->update($validator->validated());
        return response()->json(['status' => 'success', 'data' => $ward]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    $ward = Ward::findOrFail($id);
    $ward->delete();
    return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
