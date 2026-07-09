<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\IndexCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Auth;
use DB;
use Log;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $service,
    ) {}

    public function index(IndexCategoryRequest $request): JsonResponse
    {
        try {
            $categories = $this->service->getAll(
                $this->service->buildIndexQueryParams($request, false)
            );

            return response()->json(
                CategoryResource::collection($categories),
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Log::error('Error fetching categories', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching categories.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Category $category): JsonResponse
    {
        try {
            $result = $this->service->getById($category->id);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json(new CategoryResource($result), Response::HTTP_OK);
        } catch (\Throwable $th) {
            Log::error('Error fetching category', [
                'category_id' => $category->id,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching category details.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $created = $this->service->create($request->validated());
            DB::commit();

            $result = $this->service->getById($created->id);

            return response()->json(new CategoryResource($result), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Category creation error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'name' => $request->input('name', 'N/A'),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the category. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = $this->service->update($category, $request->validated());

            DB::commit();

            $result = $this->service->getById($updated->id);

            return response()->json(new CategoryResource($result), Response::HTTP_OK);
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Category update error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'category_id' => $category->id,
                'name' => $request->input('name', 'N/A'),
                'admin_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the category. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        try {
            DB::beginTransaction();
            $this->service->delete($category);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully.',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Category deletion error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'category_id' => $category->id,
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the category. Please try again later.',
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

            return response()->json(new CategoryResource($result), Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::error('Category restore error occurred', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'category_id' => $id,
                'admin_id' => Auth::id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while restoring the category. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
