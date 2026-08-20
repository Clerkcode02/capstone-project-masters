<?php

namespace App\Models;

use Database\Factories\DesignationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    /** @use HasFactory<DesignationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'utilization_target',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'utilization_target' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
