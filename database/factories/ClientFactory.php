<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use Faker\Generator as Faker;

class ClientFactory extends Factory
{
    // protected $model = Client::class;
    public function definition()
    {
        return [
            'nom' => $this->faker->firstName(),
            'prenom' =>  $this->faker->lastName(),
            'numero' => $this->faker->unique()->numerify('77#######'),
        ];
    }
}