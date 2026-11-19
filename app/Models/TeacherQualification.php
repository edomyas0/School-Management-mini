<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherQualification extends Model
{
    protected $fillable = ['teacher_id', 'title', 'institution', 'awarded_year', 'is_primary'];

    protected function casts(): array
    {
        return ['awarded_year' => 'integer', 'is_primary' => 'boolean'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
