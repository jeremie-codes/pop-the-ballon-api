<?php

namespace App\Models;

use App\Enums\PopChoiceCategory;
use App\Enums\PopChoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PopChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'option_a',
        'option_b',
        'type',
        'category',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'type' => PopChoiceType::class,
        'category' => PopChoiceCategory::class,
        'is_active' => 'boolean',
        'weight' => 'integer',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(PopChoiceAnswer::class);
    }
}
