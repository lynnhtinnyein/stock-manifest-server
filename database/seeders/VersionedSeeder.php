<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

abstract class VersionedSeeder extends Seeder
{
    abstract public function version(): int;

    abstract public function runSeeds(): void;

    final public function run(): void
    {
        $this->runSeeds();
    }
}