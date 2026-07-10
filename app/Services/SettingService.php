<?php

namespace App\Services;

use App\Models\Setting;
use DB;

class SettingService
{
    public function get(): Setting
    {
        return Setting::firstOrCreate(
            ['id' => 1],
            ['exchange_rate' => 0]
        );
    }

    public function update(array $data): Setting
    {
        return DB::transaction(function () use ($data) {
            $setting = $this->get();

            $mapped = ['exchangeRate' => 'exchange_rate'];

            $update = [];
            foreach ($mapped as $key => $column) {
                if (array_key_exists($key, $data)) {
                    $update[$column] = $data[$key];
                }
            }

            $setting->update($update);

            return $setting->fresh();
        });
    }
}
