<?php

namespace App\Services;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FapImportService
{
    private const REQUIRED_HEADERS = [
        'MSSV', 'HoTen', 'Member', 'GhiChu', 'TyLeVang',
        'Lop', 'GroupId', 'Mon', 'MonChiTiet', 'GV', 'Khoa', 'HocKy',
    ];

    public function importFromFile(string $path, bool $replaceSemester = true): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException("Cannot open file: {$path}");
        }

        $header = $this->readHeader($handle);
        $col = array_flip($header);
        $rows = [];
        $semester = null;

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data)) === 0) {
                continue;
            }

            $semester = trim($data[$col['HocKy']]);
            $rows[] = $data;
        }

        fclose($handle);

        if (empty($rows)) {
            throw new \RuntimeException('No data rows found in CSV');
        }

        return DB::transaction(function () use ($rows, $col, $semester, $replaceSemester) {
            if ($replaceSemester) {
                StudentClass::query()->where('semester', $semester)->delete();
            }

            $classRows = 0;
            $studentCodes = [];

            foreach ($rows as $data) {
                $studentCode = trim($data[$col['MSSV']]);
                $studentCodes[$studentCode] = trim($data[$col['HoTen']]);

                $student = Student::firstOrNew(['student_code' => $studentCode]);
                $student->full_name = trim($data[$col['HoTen']]);
                if (! $student->exists) {
                    $student->care_status = CareStatus::Stable;
                }
                $student->save();

                $instructors = $this->parseInstructors(trim($data[$col['GV']] ?? ''));
                $classRows += $this->createClassRows($student, $data, $col, $instructors);
            }

            return [
                'semester' => $semester,
                'students' => count($studentCodes),
                'student_classes' => $classRows,
            ];
        });
    }

    private function readHeader($handle): array
    {
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

            return $col;
        }, $header);

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $header, true)) {
                fclose($handle);
                throw new \RuntimeException("Missing required column: {$required}");
            }
        }

        return $header;
    }

    private function parseInstructors(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return collect(explode(';', $raw))
            ->map(fn ($code) => trim($code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function createClassRows(Student $student, array $data, array $col, array $instructors): int
    {
        $absenceRaw = trim($data[$col['TyLeVang']] ?? '');
        $absenceRate = $absenceRaw !== '' ? (float) str_replace('%', '', $absenceRaw) : null;

        $groupRaw = trim($data[$col['GroupId']] ?? '');
        $groupId = $groupRaw !== '' ? (int) $groupRaw : null;

        $base = [
            'class_name' => trim($data[$col['Lop']]),
            'subject_code' => trim($data[$col['Mon']]),
            'subject_name' => trim($data[$col['MonChiTiet']] ?? '') ?: null,
            'group_id' => $groupId,
            'faculty' => trim($data[$col['Khoa']]),
            'semester' => trim($data[$col['HocKy']]),
            'absence_rate' => $absenceRate,
            'note' => trim($data[$col['GhiChu']] ?? '') ?: null,
            'member_code' => trim($data[$col['Member']] ?? '') ?: null,
        ];

        if ($instructors === []) {
            return 0;
        }

        $count = 0;
        foreach ($instructors as $lecturerCode) {
            $lecturer = User::firstOrCreate(
                ['access_code' => $lecturerCode],
                [
                    'full_name' => $lecturerCode,
                    'role_label' => 'Giảng viên',
                    'role' => UserRole::Lecturer,
                    'is_active' => true,
                ]
            );

            StudentClass::create(array_merge($base, [
                'student_id' => $student->id,
                'lecturer_id' => $lecturer->id,
            ]));
            $count++;
        }

        return $count;
    }
}
