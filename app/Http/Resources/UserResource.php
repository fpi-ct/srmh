<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_code' => $this->access_code,
            'full_name' => $this->full_name,
            'role_label' => $this->role_label,
            'role' => $this->role->value,
            'faculties' => $this->faculties,
            'is_active' => $this->is_active,
        ];
    }
}
