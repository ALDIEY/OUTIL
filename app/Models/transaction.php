<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    use HasFactory;
    protected $fillable = ['client_id', 'compte_id', 'montant', 'statut','frais'];
    public function compte(){
      return $this->belongsTo(Compte::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
