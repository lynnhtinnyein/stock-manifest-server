<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\CreateItemRequest;
use App\Http\Requests\Item\IndexItemRequest;
use App\Http\Requests\Item\SyncItemImageRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Auth;
use DB;
use Log;

class ItemController extends Controller
{
    public function __construct(
        private ItemService $service,
    ) {}

    public function index(IndexItemRequest $request): JsonResponse
    {
        try {
            $items = $this->service->getAll(
                $this->service->buildIndexItemQueryParams($request, false)
            );

            return response()->json(
                ItemResource::collection($items),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching items', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching items.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Item $item): JsonResponse
    {
        try {
            $result = $this->service->getById($item->id);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Item not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json(new ItemResource($result), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error fetching item', [
                'item_id' => $item->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching item details.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateItemRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $created = $this->service->create($request->validated());
            DB::commit();

            $result = $this->service->getById($created->id);

            return response()->json(new ItemResource($result), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Item creation error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'name' => $request->input('name', 'N/A'),
                'category_id' => $request->input('categoryId', 'N/A'),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the item. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = $this->service->update($item, $request->validated());

            DB::commit();

            $result = $this->service->getById($updated->id);

            return response()->json(new ItemResource($result), Response::HTTP_OK);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Item update error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'item_id' => $item->id,
                'name' => $request->input('name', 'N/A'),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the item. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Item $item): JsonResponse
    {
        try {
            DB::beginTransaction();
            $this->service->delete($item);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Item deleted successfully.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Item deletion error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'item_id' => $item->id,
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the item. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function restore(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $restored = $this->service->restore($id);
            DB::commit();

            $result = $this->service->getById($restored->id);

            return response()->json(new ItemResource($result), Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Item restore error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'item_id' => $id,
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while restoring the item. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function syncImage(SyncItemImageRequest $request, Item $item): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = $this->service->syncImage($item, $request->validated());

            DB::commit();

            return response()->json(new ItemResource($updated), Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Item image sync error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'item_id' => $item->id,
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while syncing the item image.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
