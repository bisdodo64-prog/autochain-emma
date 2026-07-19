<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $drivers = User::role('driver')->with('vehicles')->get();
        
        return response()->json($drivers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string',
            'employee_id' => 'nullable|string|unique:users,employee_id',
            'wallet_address' => 'nullable|string|unique:users,wallet_address',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $driver = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'employee_id' => $request->employee_id,
            'wallet_address' => $request->wallet_address,
        ]);

        $driver->assignRole('driver');

        return response()->json([
            'message' => 'Driver created successfully',
            'driver' => $driver->load('roles'),
        ], 201);
    }

    public function show($id)
    {
        $driver = User::role('driver')->with('vehicles')->findOrFail($id);
        
        return response()->json($driver);
    }

    public function assignWallet(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string|unique:users,wallet_address',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $driver = User::role('driver')->findOrFail($id);
        $driver->wallet_address = $request->wallet_address;
        $driver->save();

        return response()->json([
            'message' => 'Wallet assigned successfully',
            'driver' => $driver,
        ]);
    }

    public function getAssignedVehicles($id)
    {
        $driver = User::role('driver')->findOrFail($id);
        $vehicles = Vehicle::where('driver_id', $id)->with('documents', 'maintenances')->get();
        
        return response()->json($vehicles);
    }
}
