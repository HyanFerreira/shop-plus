<?php
namespace Database\Factories;
use App\Domain\PersonalData\BlindIndex; use App\Models\Supplier; use Illuminate\Database\Eloquent\Factories\Factory;
class SupplierFactory extends Factory { protected $model=Supplier::class; public function definition():array{$doc='99'.str_pad((string)$this->faker->unique()->numberBetween(0,999999999999),12,'0',STR_PAD_LEFT);return ['name_encrypted'=>'Fornecedor Fictício '.$this->faker->unique()->numerify('####'),'document_encrypted'=>$doc,'document_hash'=>(new BlindIndex)->for($doc),'email_encrypted'=>'fornecedor'.$this->faker->unique()->numerify('####').'@example.test','phone_encrypted'=>'(11) 3333-0000','address_encrypted'=>'Rua Comercial Fictícia, 100','active'=>true];} }
