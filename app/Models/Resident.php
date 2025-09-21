<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Visit;

class Resident extends Model
{
    use HasFactory;

    protected $table = 'residentes';

    public function visitas()
    {        
        return $this->hasMany(Visit::class);

    }

    public function fullname($code)
    {
        $fullname = $this->Nombres." ".$this->Nombres2." ".$this->Apellidos." ".$this->Apellidos2;

        if($code){
            $fullname = $this->Codigo." | ".$fullname;
        }



        return $fullname;
    }

     



}
