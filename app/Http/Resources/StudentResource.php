<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $classes = $this->studentClasses;

        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'full_name' => $this->full_name,
            'care_status' => $this->care_status->value,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'class_names' => $classes->pluck('class_name')->unique()->sort()->implode(', '),
            'subject_codes' => $classes->pluck('subject_code')->unique()->sort()->implode(', '),
            'instructor_names' => $classes->map(fn ($c) => $c->lecturer?->full_name ?? '')->filter()->unique()->sort()->implode(', '),
            'faculty' => $classes->pluck('faculty')->unique()->sort()->implode(', '),
            'max_absence_rate' => $classes->max('absence_rate'),
        ];
    }
}
