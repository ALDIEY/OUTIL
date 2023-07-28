<?php
namespace Database\Factories;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition()
    {
        $client = Client::inRandomOrder()->first();
        $typeCompte = $this->faker->randomElement(['Orange Money', 'Wave', 'Wari', 'CB']);

        // Vérifie si le client a déjà un compte du même type
        $hasDuplicateType = $client->comptes->contains('typeCompte', $typeCompte);

        // Si le client a déjà un compte du même type, on réutilise le type de compte existant
        if ($hasDuplicateType) {
            $compte = $client->comptes->where('typeCompte', $typeCompte)->first();
            return [
                'client_id' => $client->id,
                'solde' => $compte->solde, // Réutilise le solde du compte existant
                'typeCompte' => $typeCompte,
                'numeroCompte' => $compte->numeroCompte, // Réutilise le numéro de compte existant
            ];
        }

        // Si le client n'a pas de compte du même type, on crée un nouveau compte
        return [
            'client_id' => $client->id,
            'solde' => $this->faker->numberBetween(100, 10000000),
            'typeCompte' => $typeCompte,
            'numeroCompte' => $this->codeCompte($typeCompte, $client->numero),
        ];
    }

    public function codeCompte($typeCompte, $numero)
    {
        switch ($typeCompte) {
            case 'Orange Money':
                $code = 'OM';
                break;
            case 'Wave':
                $code = 'WV';
                break;
            case 'Wari':
                $code = 'WR';
                break;
            case 'CB':
                $code = 'CB';
                break;
            default:
                $code = 'ND'; // Gérer le cas par défaut si nécessaire
                break;
        }
        return $code . '-' . $numero;
    }
}

