<?php

namespace App\Services;

use App\Http\Requests\Item\IndexItemRequest;
use App\Models\Item;
use App\Services\Attachment\AttachmentService;
use App\Services\Attachment\OneToOneAttachmentSyncService;
use Auth;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ItemService
{
    public function __construct(
        protected OneToOneAttachmentSyncService $attachmentSyncService
    ) {}

    private const SORT_COLUMN_MAP = [
        'id' => 'id',
        'price' => 'price_in_thb',
        'category' => 'category_id',
        'weight' => 'weight_in_grams',
        'sellingPrice' => 'selling_price_in_mmk',
        'cargoPrice' => 'cargo_price_in_mmk',
        'createdAt' => 'created_at',
    ];

    public function relations(): array
    {
        return [
            'category',
            'createdBy',
            'updatedBy',
            'image'
        ];
    }

    public function buildIndexItemQueryParams(IndexItemRequest $request, bool $only_trashed): array
    {
        return [
            'filters' => [
                'name' => $request->get('name'),
                'categoryId' => $request->get('categoryId'),
                'onlyTrashed' => $only_trashed,
            ],
            'sorting' => [
                'sortBy' => $request->get('sortBy', 'price'),
                'sortOrder' => strtoupper($request->get('sortOrder', 'DESC')),
            ],
            'perPage' => (int) $request->get('perPage', 9),
            'page' => (int) $request->get('page', 1),
        ];
    }

    public function getAll(array $params): LengthAwarePaginator
    {
        $filters = $params['filters'] ?? [];
        $sorting = $params['sorting'] ?? [];
        $perPage = $params['perPage'] ?? 15;
        $page = $params['page'] ?? 1;

        $query = ($filters['onlyTrashed'] ?? false) ? Item::onlyTrashed() : Item::query();

        $query->with($this->relations());
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $sorting);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function applyFilters(mixed $query, array $filters): void
    {
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['categoryId'])) {
            $query->where('category_id', $filters['categoryId']);
        }
    }

    private function applySorting(mixed $query, array $sorting): void
    {
        $sortBy = $sorting['sortBy'] ?? 'price';
        $sortOrder = $sorting['sortOrder'] ?? 'DESC';

        if ($sortBy === 'stockStatus') {
            $query->orderByRaw('(stock_in - stock_out) > 0 ' . $sortOrder);
            return;
        }

        $column = self::SORT_COLUMN_MAP[$sortBy] ?? 'price_in_thb';
        $query->orderBy($column, $sortOrder);
    }

    public function getById(int $id): ?Item
    {
        return Item::with($this->relations())->find($id);
    }

    public function create(array $data): Item
    {
        return DB::transaction(function () use ($data) {
            return Item::create([
                'name' => $data['name'],
                'category_id' => $data['categoryId'],
                'price_in_thb' => $data['priceInThb'],
                'weight_in_grams' => $data['weightInGrams'] ?? 0,
                'cargo_price_in_thb' => $data['cargoPriceInThb'] ?? 0,
                'cargo_price_in_mmk' => $data['cargoPriceInMmk'] ?? 0,
                'selling_price_in_mmk' => $data['sellingPriceInMmk'] ?? 0,
                'stock_in' => $data['stockIn'] ?? 0,
                'stock_out' => $data['stockOut'] ?? 0,
                'remark' => $data['remark'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });
    }

    public function update(Item $item, array $data): Item
    {
        return DB::transaction(function () use ($item, $data) {
            $mapped = [
                'name' => 'name',
                'categoryId' => 'category_id',
                'priceInThb' => 'price_in_thb',
                'weightInGrams' => 'weight_in_grams',
                'cargoPriceInThb' => 'cargo_price_in_thb',
                'cargoPriceInMmk' => 'cargo_price_in_mmk',
                'sellingPriceInMmk' => 'selling_price_in_mmk',
                'stockIn' => 'stock_in',
                'stockOut' => 'stock_out',
                'remark' => 'remark',
            ];

            $update = [];
            foreach ($mapped as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $update[$column] = $data[$key];
                }
            }
            $update['updated_by'] = Auth::id();

            $item->update($update);

            return $item;
        });
    }

    public function delete(Item $item): void
    {
        DB::transaction(function () use ($item) {
            $item->update(['updated_by' => Auth::id()]);
            $item->delete();
        });
    }

    public function restore(int $id): Item
    {
        return DB::transaction(function () use ($id) {
            $item = Item::onlyTrashed()->findOrFail($id);
            $item->restore();
            $item->update(['updated_by' => Auth::id()]);

            return $item;
        });
    }

    public function syncImage(Item $item, array $data): Item
    {
        return DB::transaction(function () use ($item, $data) {
            $storagePath = 'items/' . $item->id;
            $documents = [
                [
                    'data' => $data['image'] ?? null,
                    'type' => 'ITEM_IMAGE',
                    'path' => $storagePath,
                ],
            ];
            $this->attachmentSyncService->syncDocuments($item, $documents);
            return $item->fresh($this->relations());
        });
    }
}
