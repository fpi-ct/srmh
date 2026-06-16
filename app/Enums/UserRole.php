<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Lecturer = 'lecturer';
    case StudentAffairs = 'student_affairs';
    case DepartmentHead = 'department_head';
}
