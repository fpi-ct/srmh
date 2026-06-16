<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

class UserImportService
{
    public function importFromFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException("Cannot open file: {$path}");
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            throw new \RuntimeException('CSV file is empty');
        }

        $header = array_map(function ($col) {
            $col = trim($col);
            if (str_starts_with($col, "\xEF\xBB\xBF")) {
                $col = substr($col, 3);
            }

            return strtolower($col);
        }, $header);

        $col = array_flip($header);
        $imported = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data)) === 0) {
                continue;
            }

            $accessCode = trim($data[$col['code']]);
            if ($accessCode === '') {
                continue;
            }

            User::updateOrCreate(
                ['access_code' => $accessCode],
                [
                    'full_name' => trim($data[$col['name']]),
                    'role_label' => trim($data[$col['role']]),
                    'role' => $this->mapRole(trim($data[$col['raw_role']])),
                    'faculties' => trim($data[$col['major']] ?? '') ?: null,
                    'is_active' => true,
                ]
            );

            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported];
    }

    private function mapRole(string $rawRole): UserRole
    {
        return match (strtoupper($rawRole)) {
            'ADMIN' => UserRole::Admin,
            'CTSV' => UserRole::StudentAffairs,
            'CNBM' => UserRole::DepartmentHead,
            default => UserRole::Lecturer,
        };
    }
}
