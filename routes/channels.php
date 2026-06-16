<?php

use App\Models\Student;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('user.{accessCode}', function ($user, string $accessCode) {
    return $user->access_code === $accessCode;
});

Broadcast::channel('student.{id}', function ($user, int $id) {
    $student = Student::find($id);

    return $student && Gate::forUser($user)->allows('view', $student);
});

Broadcast::channel('dashboard', function ($user) {
    return [
        'access_code' => $user->access_code,
        'full_name' => $user->full_name,
        'role' => $user->role->value,
    ];
});
