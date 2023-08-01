<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Compte;
use App\Models\transaction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function depot(Request $request) {
        $montant=$request->input('montant');
        $numero=$request->input('numero');
        $numeroClie=Client::where('clients.numero',$numero)->first()->id;

        $fournisseur = Compte::
        where('comptes.client_id', $numeroClie)->first()->typeCompte;
        $montantMinimum = 0;
    
        switch ($fournisseur) {
            case 'Orange Money':
            case 'Wave':
                $montantMinimum = 500;
                break;
            case 'Wari':
                $montantMinimum = 1000;
                break;
            case 'CB':
                $montantMinimum = 10000;
                break;
        }
    
        if ($montant < $montantMinimum) {
            return response()->json(['message' =>
             'Le montant est inférieur au montant minimum
              autorisé pour ce fournisseur.'], 422);
        }
    
        $montantMaximum = 1000000;
        if ($montant > $montantMaximum) {
            return response()->json(['message' => 'Le montant dépasse le montant maximum autorisé.'], 422);
        }
    
 try {
    DB::beginTransaction();

    $numeroClient=Client::where('clients.numero',$numero)->first()->id;
$compte=Compte::where('comptes.client_id',$numeroClient)->first();
$compteId=$compte->id;
$compte->increment('solde',$montant);
DB::commit();
 } catch (\Throwable $th) {
    
    DB::rollBack();
    throw $th;

   }
   $transaction=transaction::create([
    'client_id'=> $numeroClient,
    'compte_id'=>$compteId,
    'montant'=>$montant,
    'statut'=>'depot',
    'frait'=>0
    ]);
   return response()->json($transaction);
    }



    //retrait
    public function retrait(Request $request)
{
    $fournisseur = $request->input('fournisseur');
    $montant = $request->input('montant');
    $numero = $request->input('expediteur');
   
    $client = Client::where('clients.numero', $numero)->first();
    if (!$client) {
        return response()->json(['message' => 'Le client avec ce numéro n\'existe pas.'], 404);
    }

    // Vérifier si le compte avec le fournisseur spécifié existe pour le client
    $typeCompte = Compte::where(['client_id' => $client->id, 'typeCompte' => $fournisseur])->first();
    if (!$typeCompte) {
        return response()->json(['message' => 'Le client n\'a pas de compte chez ce fournisseur.'], 422);
    }

    // Récupérer le compte du client
    $compte = Compte::where('client_id', $client->id)->first();

    $montantMinimum = 0;
    switch ($typeCompte) {
        case 'Orange Money':
        case 'Wave':
            $montantMinimum = 500;
            break;
        case 'Wari':
            $montantMinimum = 1000;
            break;
        case 'CB':
            $montantMinimum = 10000;
            break;
    }
    if ($montant < $montantMinimum) {
        return response()->json(['message'
         => 'Le montant est inférieur au montant minimum autorisé pour ce fournisseur.'], 422);
    }

    if ($compte->solde < $montant) {
        return response()->json(['message' => 'Solde insuffisant pour effectuer ce retrait.'], 422);
    }

    $codeRetrait = null;
    if ($typeCompte !== 'Wari') {
        $codeRetrait = random_int(pow(10, 9 - 1), pow(10, 10) - 1);
    }

    $codeRetraitImmediat = null;
    $dateLimiteRetraitImmediat = null;
    if ($typeCompte === 'CB') {
        $codeRetraitImmediat = random_int(pow(10, 9 - 1), pow(10, 10) - 1);
        $dateLimiteRetraitImmediat = now()->addHours(24);
    }

    $compte->decrement('solde', $montant);

    $transaction = Transaction::create([
        'client_id' => $client->id,
        'compte_id' => $compte->id,
        'montant' => $montant,
        'statut' => 'retrait',
        'code_retrait' => $codeRetrait,
        'code_retrait_immediat' => $codeRetraitImmediat,
        'date_limite_retrait_immediat' => $dateLimiteRetraitImmediat,
    ]);

    return response()->json($transaction);
}

//clacul des frait

public function calculFrais($typeCompteSource,$montant){
    switch ($typeCompteSource) {
      case 'Wari':
          $frais = $montant * 0.02;
          break;
      case 'Wave':
      case 'Orange Money':
           $frais = $montant * 0.01;
           break;
      case 'CB':
           $frais = $montant * 0.1;
           break;  
      default:
           $frais = 0;
           break;
   }
   return $frais + $montant;
}

   //depot
  
   public function transfert(Request $request)
   {
    $montant=$request->input('montant');
    $numeroSource=$request->input('numeroSource');
    $numeroDestinataire=$request->input('numeroDestinataire');

       // Vérifier si le montant respecte le montant minimum autorisé pour le fournisseur du compte source
       $clientSource = Client::where('clients.numero', $numeroSource)->first();
       $typeCompteSource = $clientSource->typeCompte;
       $montantMinimum = 0;
   
       switch ($typeCompteSource) {
           case 'Orange Money':
           case 'Wave':
               $montantMinimum = 500;
               break;
           case 'Wari':
               $montantMinimum = 1000;
               break;
           case 'CB':
               $montantMinimum = 10000;
               break;
       }
       if ($montant < $montantMinimum) {
           return response()->json(['message' =>
           'Le montant est inférieur au montant minimum autorisé pour ce fournisseur.'], 422);
       }
   
       // Vérifier si le montant de transfert ne dépasse pas le montant maximum autorisé
       $montantMaximum = 1000000;
       if ($montant > $montantMaximum) {
           return response()->json(['message' => 'Le montant dépasse le montant maximum autorisé.'], 422);
       }
   
       // Vérifier si le compte source a un solde suffisant pour effectuer le transfert
       $compteSource = Compte::where('client_id', $clientSource->id)->first();
       if ($compteSource->solde < $montant) {
           return response()->json(['message' => 'Solde insuffisant pour effectuer ce transfert.'], 422);
       }
   
       // Vérifier si le compte source et le compte destinataire appartiennent au même fournisseur
       $clientDestinataire = Client::where('clients.numero', $numeroDestinataire)->first();
       $typeCompteDestinataire = $clientDestinataire->typeCompte;
       if ($typeCompteSource !== $typeCompteDestinataire) {
           return response()->json(['message' =>
           'Les comptes source et destinataire n\'appartiennent pas au même fournisseur.'], 422);
       }
   

$fraits=$this->calculFrais($montant,$typeCompteSource );


       try {
           DB::beginTransaction();
   
           // Effectuer le transfert en décrémentant le solde du compte source
           $compteSource->decrement('solde',$fraits );
   
           // Incrémenter le solde du compte destinataire
           $compteDestinataire = Compte::where('client_id', $clientDestinataire->id)->first();
           
           $compteDestinataire->increment('solde', $montant);
   
           // Créer une nouvelle entrée dans la table Transaction pour le compte source
           $transactionSource = Transaction::create([
               'client_id' => $clientSource->id,
               'compte_id' => $compteSource->id,
               'montant' => $fraits,
               'statut' => 'compte_compte',
               'frais'=>$fraits-$montant
           ]);
   
           // Créer une nouvelle entrée dans la table Transaction pour le compte destinataire
           $transactionDestinataire = Transaction::create([
               'client_id' => $clientDestinataire->id,
               'compte_id' => $compteDestinataire->id,
               'montant' => $montant,
               'statut' => 'compte_compte',
               
           ]);
   
           DB::commit();
       } catch (\Throwable $th) {
           DB::rollBack();
           throw $th;
       }
   
       return response()->json(['message' => 'Transfert réussi.']);
   }
   
}   