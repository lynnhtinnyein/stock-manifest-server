<?php

namespace App\Services;

use App\Http\Requests\Category\IndexCategoryRequest;
use App\Models\Category;
use Auth;
use DB;
use Illuminate\Support\Collection;

class CategoryService
{
    public function buildIndexQueryParams(IndexCategoryRequest $request, bool $only_trashed): array
    {
        return [
            'filters' => [
                'onlyTrashed' => $only_trashed,
            ],
        ];
    }

    public function getAll(array $params): Collection
    {
        $filters = $params['filters'] ?? [];

        $query = ($filters['onlyTrashed'] ?? false) ? Category::onlyTrashed() : Category::query();

        return $query->orderBy('id', 'ASC')->get();
    }

    public function getById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::create([
                'name' => $data['name'],
                'color' => $data['color'] ?? null,
            ]);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $mapped = ['name' => 'name', 'color' => 'color'];

            $update = [];
            foreach ($mapped as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $update[$column] = $data[$key];
                }
            }

            $category->update($update);

            return $category;
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            $category->delete();
        });
    }

    public function restore(int $id): Category
    {
        return DB::transaction(function () use ($id) {
            $category = Category::onlyTrashed()->findOrFail($id);
            $category->restore();

            return $category;
        });
    }
}
