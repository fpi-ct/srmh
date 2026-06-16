<?php

namespace App\Console\Commands;

use App\Services\UserImportService;
use Illuminate\Console\Command;

class ImportUsersFromCsv extends Command
{
    protected $signature = 'users:import-csv {path : Path to users CSV file}';

    protected $description = 'Import users from CSV (code, name, role, raw_role, major)';

    public function handle(UserImportService $importService): int
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

        $this->info("Users imported: {$result['imported']}");

        return self::SUCCESS;
    }
}
