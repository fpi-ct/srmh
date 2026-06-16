<?php

namespace App\Console\Commands;

use App\Services\FapImportService;
use Illuminate\Console\Command;

class ImportFapCsv extends Command
{
    protected $signature = 'fap:import {path : Path to FAP CSV file}';

    protected $description = 'Import FAP CSV into students and student_classes';

    public function handle(FapImportService $importService): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        try {
            $result = $importService->importFromFile($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Semester: {$result['semester']}");
        $this->info("Students: {$result['students']}");
        $this->info("Student classes: {$result['student_classes']}");

        return self::SUCCESS;
    }
}
