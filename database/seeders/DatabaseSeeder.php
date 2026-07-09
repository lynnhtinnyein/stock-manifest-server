<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureVersionTableExists();

        $seeders = $this->discoverVersionedSeeders();

        $ranVersions = DB::table('seeder_versions')
            ->pluck('version')
            ->flip()
            ->all();

        foreach ($seeders as $seeder) {
            $version = $seeder->version();

            if (isset($ranVersions[$version])) {
                $this->command->line("  Skipping v{$version} — already ran.");
                continue;
            }

            $this->command->info("  Running v{$version}: " . $seeder::class);

            $seeder->setCommand($this->command);
            $seeder->run();

            DB::table('seeder_versions')->insert([
                'version'      => $version,
                'seeder_class' => $seeder::class,
                'ran_at'       => now(),
            ]);

            $this->command->info("  ✓ v{$version} complete.");
        }
    }

    private function discoverVersionedSeeders(): array
    {
        $baseDir = database_path('seeders/Versions');
        $seeders = [];

        foreach (File::directories($baseDir) as $versionDir) {
            $indexFile = $versionDir . '/Index.php';

            if (!File::exists($indexFile)) {
                continue;
            }

            $folderName = basename($versionDir);
            $namespace  = 'Database\\Seeders\\Versions\\' . $folderName . '\\';
            $class      = $namespace . 'Index';

            if (!class_exists($class)) {
                require_once $indexFile;
            }

            if (
                is_subclass_of($class, VersionedSeeder::class) &&
                !(new \ReflectionClass($class))->isAbstract()
            ) {
                $seeders[] = new $class();
            }
        }

        usort($seeders, fn($a, $b) => $a->version() <=> $b->version());

        $this->assertNoDuplicateVersions($seeders);

        return $seeders;
    }

    private function assertNoDuplicateVersions(array $seeders): void
    {
        $versions = array_map(fn($s) => $s->version(), $seeders);
        $duplicates = array_diff_assoc($versions, array_unique($versions));

        if (!empty($duplicates)) {
            foreach ($duplicates as $dupe) {
                $this->command->error("  Duplicate seed version detected: v{$dupe}");
            }
            exit(1);
        }
    }

    private function ensureVersionTableExists(): void
    {
        if (!Schema::hasTable('seeder_versions')) {
            $this->command->warn('seeder_versions table missing — run migrations first.');
            exit(1);
        }
    }
}