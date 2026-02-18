<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'description',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }

    public function topTenAnswers(): HasMany
    {
        return $this->answers()->where('is_tension', false);
    }

    public function tensionAnswers(): HasMany
    {
        return $this->answers()->where('is_tension', true);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function isComplete(): bool
    {
        // A category is complete if it has at least 10 answers (the top 10)
        return $this->answers()->count() >= 10;
    }

    public function tensionCount(): int
    {
        return $this->tensionAnswers()->count();
    }
}
