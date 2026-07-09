<?php

namespace App\Services;

use App\Models\Setting;
use DB;
use Illuminate\Support\Collection;

class SettingService
{
    public function getAll(): Collection
    {
        return Setting::orderBy('id', 'ASC')->get();
    }

    public function getById(int $id): ?Setting
    {
        return Setting::find($id);
    }

    public function create(array $data): Setting
    {
        return DB::transaction(function () use ($data) {
            return Setting::create([
                'exchange_rate' => $data['exchangeRate'],
            ]);
        });
    }

    public function update(Setting $setting, array $data): Setting
    {
        return DB::transaction(function () use ($setting, $data) {
            $mapped = ['exchangeRate' => 'exchange_rate'];

            $update = [];
            foreach ($mapped as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $update[$column] = $data[$key];
                }
            }

            $setting->update($update);

            return $setting;
        });
    }

    public function delete(Setting $setting): void
    {
        DB::transaction(function () use ($setting) {
            $setting->delete();
        });
    }
}
