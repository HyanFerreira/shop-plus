<?php

namespace App\Models;

use Database\Factories\CustomerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    /** @use HasFactory<CustomerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'cpf_encrypted',
        'cpf_hash',
        'birth_date',
    ];

    protected $hidden = [
        'cpf_encrypted',
        'cpf_hash',
    ];

    protected function casts(): array
    {
        return [
            'cpf_encrypted' => 'encrypted',
            'birth_date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
