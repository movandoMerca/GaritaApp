<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resident;

class Visit extends Model
{
    use HasFactory;

    protected $table = 'visitas';


    public function residente()
    {
        return $this->belongsTo(Resident::class);
    }




}
