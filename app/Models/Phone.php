<?php

namespace App\Models;

use App\Enums\PhoneType;
use Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
    /** @use HasFactory<PhoneFactory> */
    use HasFactory;

    protected $fillable = [
        'number_encrypted',
        'number_hash',
        'type',
        'is_primary',
    ];

    protected $hidden = [
        'number_encrypted',
        'number_hash',
    ];

    protected function casts(): array
    {
        return [
            'number_encrypted' => 'encrypted',
            'type' => PhoneType::class,
            'is_primary' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
