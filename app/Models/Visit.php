<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resident;

class Visit extends Model
{
    use HasFactory;

    protected $table = 'visitas';

    protected $casts = ['es_repartidor' => 'boolean'];

    protected static function booted()
    {
        static::saving(function (Visit $visit) {
            if ($visit->isPedestrian()) {
                return;
            }

            foreach ([
                'numeroDocumento', 'cui', 'Placa', 'Primer_Nombre', 'Segundo_Nombre',
                'Primer_Apellido', 'Segundo_Apellido', 'Fecha_nac', 'Fecha_vencimiento',
                'cono', 'tipoLicencia', 'tel_emergencia',
            ] as $field) {
                $value = $visit->getAttribute($field);

                if (is_string($value)) {
                    $visit->setAttribute($field, mb_strtoupper($value, 'UTF-8'));
                }
            }
        });
    }

    public function scopeFilterVisits($query, array $filters)
    {
        if (!empty($filters['tipo_visita'])) {
            $query->where('tipo_visita', $filters['tipo_visita']);
        }

        if (isset($filters['es_repartidor']) && $filters['es_repartidor'] !== '') {
            $query->where('es_repartidor', $filters['es_repartidor']);
        }

        return $query;
    }

    public static function validatedFilters(\Illuminate\Http\Request $request)
    {
        return $request->validate([
            'tipo_visita' => 'nullable|in:vehicular,peatonal',
            'es_repartidor' => 'nullable|in:0,1',
        ]);
    }

    public function isPedestrian()
    {
        return $this->tipo_visita === 'peatonal';
    }


    public function residente()
    {
        return $this->belongsTo(Resident::class);
    }




}
