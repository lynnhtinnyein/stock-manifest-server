<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $permissions = Permission::with('dependencies')
                ->orderBy('risk_level')
                ->orderBy('id')
                ->get();

            return response()->json(
                PermissionResource::collection($permissions),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching permissions', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to retrieve permissions. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
