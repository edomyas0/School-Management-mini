<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSchool();
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->canManageSchool();
    }

    public function create(User $user): bool
    {
        return $user->canManageSchool();
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->canManageSchool();
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->role === 'administrator';
    }
}
