<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSchool();
    }

    public function view(User $user, Student $student): bool
    {
        return $user->canManageSchool();
    }

    public function create(User $user): bool
    {
        return $user->role === 'registrar';
    }

    public function update(User $user, Student $student): bool
    {
        return $user->canManageSchool();
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->role === 'administrator';
    }
}
