<?php

namespace Tests\Feature\PersonalData;

use App\Domain\PersonalData\Cpf;
use App\Enums\PhoneType;
use App\Models\Address;
use App\Models\CustomerProfile;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptedPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_is_related_encrypted_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $profile = CustomerProfile::factory()->for($user)->create();
        $raw = DB::table('customer_profiles')->find($profile->id);

        $this->assertTrue(Cpf::isValid($profile->cpf_encrypted));
        $this->assertSame($profile->id, $user->customerProfile->id);
        $this->assertNotSame($profile->cpf_encrypted, $raw->cpf_encrypted);
        $this->assertStringNotContainsString(Cpf::from($profile->cpf_encrypted)->digits(), $raw->cpf_encrypted);
        $this->assertArrayNotHasKey('cpf_encrypted', $profile->toArray());
        $this->assertArrayNotHasKey('cpf_hash', $profile->toArray());
    }

    public function test_cpf_blind_index_is_globally_unique(): void
    {
        $profile = CustomerProfile::factory()->create();

        $this->expectException(QueryException::class);

        CustomerProfile::factory()->create([
            'cpf_encrypted' => $profile->cpf_encrypted,
            'cpf_hash' => $profile->cpf_hash,
        ]);
    }

    public function test_phone_is_typed_related_encrypted_and_unique_per_owner(): void
    {
        $user = User::factory()->create();
        $phone = Phone::factory()->for($user)->create();
        $raw = DB::table('phones')->find($phone->id);

        $this->assertInstanceOf(PhoneType::class, $phone->type);
        $this->assertTrue($phone->is_primary);
        $this->assertTrue($user->phones->contains($phone));
        $this->assertNotSame($phone->number_encrypted, $raw->number_encrypted);
        $this->assertArrayNotHasKey('number_encrypted', $phone->toArray());
        $this->assertArrayNotHasKey('number_hash', $phone->toArray());

        $this->expectException(QueryException::class);

        Phone::factory()->for($user)->create([
            'number_encrypted' => $phone->number_encrypted,
            'number_hash' => $phone->number_hash,
        ]);
    }

    public function test_address_content_is_related_encrypted_and_hidden_from_serialization(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $raw = DB::table('addresses')->find($address->id);

        $this->assertTrue($address->is_primary);
        $this->assertTrue($user->addresses->contains($address));

        foreach (Address::ENCRYPTED_FIELDS as $field) {
            $this->assertNotSame($address->{$field}, $raw->{$field});
            $this->assertArrayNotHasKey($field, $address->toArray());
        }
    }

    public function test_profile_birth_date_is_cast_to_an_immutable_date(): void
    {
        $profile = CustomerProfile::factory()->create(['birth_date' => '1990-05-20']);

        $this->assertSame('1990-05-20', $profile->birth_date->format('Y-m-d'));
    }
}
