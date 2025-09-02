<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Appointments module coming soon',
            'data' => []
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Appointment creation coming soon'
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Appointment details coming soon',
            'data' => ['id' => $id]
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Appointment update coming soon'
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Appointment deletion coming soon'
        ]);
    }
}

