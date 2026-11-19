<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employment_type',
        'hired_on',
        'status',
    ];

    protected function casts(): array
    {
        return ['hired_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(TeacherQualification::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
