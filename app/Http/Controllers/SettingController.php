<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Auth;
use DB;
use Log;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $service,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $setting = $this->service->get();

            return response()->json(
                new SettingResource($setting),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching setting', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching the setting.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = $this->service->update($request->validated());

            DB::commit();

            return response()->json(new SettingResource($updated), Response::HTTP_OK);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Setting update error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the setting. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
