<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VehicleLicense
{
    const FIELDS = ['numeroDocumento', 'tipoLicencia', 'Primer_Nombre', 'Segundo_Nombre',
        'Primer_Apellido', 'Segundo_Apellido', 'Fecha_nac', 'Fecha_vencimiento', 'cui', 'tel_emergencia'];

    public function parse(string $code): array
    {
        $parts = explode('|', str_replace(']', '|', trim($code)));
        if (isset($parts[0]) && mb_strlen($parts[0]) > 10) {
            $parts[0] = mb_substr($parts[0], -8);
            array_splice($parts, 6, 1);
        }
        if (count($parts) < 11) {
            throw ValidationException::withMessages(['codigoLicencia' => 'No se pudo leer la licencia. Escanéela nuevamente o utilice el ingreso manual.']);
        }

        return $this->fromTokens($parts);
    }

    public function fromTokens(array $tokens): array
    {
        if (count($tokens) < 10) {
            throw ValidationException::withMessages(['codigoLicencia' => 'La licencia no contiene todos los datos necesarios. Vuelva a escanearla.']);
        }
        $visitor = [];
        foreach (self::FIELDS as $index => $field) {
            $value = $tokens[$index] ?? null;
            $visitor[$field] = is_string($value) ? trim($value) : $value;
        }
        $rules = [];
        foreach ([8, 2, 30, 70, 45, 70, 20, 20, 13, 8] as $index => $max) {
            $rules[self::FIELDS[$index]] = (in_array($index, [3, 5, 9]) ? 'nullable' : 'required')."|string|max:$max";
        }
        Validator::make($visitor, $rules, [
            'required' => 'La licencia no contiene :attribute. Vuelva a escanearla.',
            'string' => 'El dato de :attribute de la licencia no es válido.',
            'max' => 'El dato de :attribute de la licencia supera los :max caracteres permitidos.',
        ], self::labels())->validate();

        return $visitor;
    }

    public function tokens(array $visitor): array
    {
        $tokens = array_map(function ($field) use ($visitor) { return $visitor[$field] ?? null; }, self::FIELDS);
        // The trailing scanner field is not a visit column. Never validate its
        // length or serialize its (potentially large) contents into Livewire.
        $tokens[] = null;
        return $tokens;
    }

    public static function labels(): array
    {
        return array_combine(self::FIELDS, ['número de documento', 'tipo de licencia', 'primer nombre',
            'segundo nombre', 'primer apellido', 'segundo apellido', 'fecha de nacimiento',
            'fecha de vencimiento', 'CUI', 'teléfono de emergencia']);
    }
}
