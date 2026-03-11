<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $topic_id
 * @property string $title
 * @property string|null $description
 * @property-read \App\Models\Topic $topic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers
 */
class Category extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'topic_id',
        'title',
        'description',
        'played_at',
        'is_starter',
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'is_starter' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }
}
