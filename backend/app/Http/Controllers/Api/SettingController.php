<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(['status' => 'success', 'data' => []]);
    }

    public function update(Request $request)
    {
        // Minimal stub - implement saving logic as needed
        return response()->json(['status' => 'success', 'message' => 'Settings updated']);
    }
}
