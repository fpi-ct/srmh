<?php

namespace Database\Seeders;

use App\Services\UserImportService;
use Illuminate\Database\Seeder;

class LecturerUserSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('../docs/lecturer.csv');

        if (! file_exists($path)) {
            throw new \RuntimeException("lecturer.csv not found at {$path}");
        }

        app(UserImportService::class)->importFromFile($path);
    }
}
