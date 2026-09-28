<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    public const ENCRYPTED_FIELDS = [
        'recipient_encrypted',
        'postal_code_encrypted',
        'street_encrypted',
        'number_encrypted',
        'complement_encrypted',
        'district_encrypted',
        'city_encrypted',
        'state_encrypted',
    ];

    protected $fillable = [
        'label',
        ...self::ENCRYPTED_FIELDS,
        'is_primary',
    ];

    protected $hidden = self::ENCRYPTED_FIELDS;

    protected function casts(): array
    {
        return [
            'recipient_encrypted' => 'encrypted',
            'postal_code_encrypted' => 'encrypted',
            'street_encrypted' => 'encrypted',
            'number_encrypted' => 'encrypted',
            'complement_encrypted' => 'encrypted',
            'district_encrypted' => 'encrypted',
            'city_encrypted' => 'encrypted',
            'state_encrypted' => 'encrypted',
            'is_primary' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
