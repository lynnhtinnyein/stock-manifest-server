<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setting\CreateSettingRequest;
use App\Http\Requests\Setting\IndexSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
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

    public function index(IndexSettingRequest $request): JsonResponse
    {
        try {
            $settings = $this->service->getAll();

            return response()->json(
                SettingResource::collection($settings),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching settings', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching settings.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Setting $setting): JsonResponse
    {
        try {
            $result = $this->service->getById($setting->id);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Setting not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json(new SettingResource($result), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error fetching setting', [
                'setting_id' => $setting->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching setting details.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateSettingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $created = $this->service->create($request->validated());
            DB::commit();

            $result = $this->service->getById($created->id);

            return response()->json(new SettingResource($result), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Setting creation error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the setting. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateSettingRequest $request, Setting $setting): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = $this->service->update($setting, $request->validated());

            DB::commit();

            $result = $this->service->getById($updated->id);

            return response()->json(new SettingResource($result), Response::HTTP_OK);
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
                'setting_id' => $setting->id,
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the setting. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Setting $setting): JsonResponse
    {
        try {
            DB::beginTransaction();
            $this->service->delete($setting);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Setting deleted successfully.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Setting deletion error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'setting_id' => $setting->id,
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the setting. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
