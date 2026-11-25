<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSequentialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use GeneratesSequentialNumber;

    protected $fillable = ['course_id', 'member_id', 'enrolled_at', 'completed_at', 'certificate_number'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** Format: CRT-2026-00001 */
    public static function generateCertificateNumber(): string
    {
        return static::nextSequentialNumber('certificate_number', 'CRT-'.now()->format('Y').'-', 5);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }
}
