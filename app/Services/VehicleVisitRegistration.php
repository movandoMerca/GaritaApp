<?php

namespace App\Services;

use App\Models\config;
use App\Models\log;
use App\Models\Visit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;

/** Shared by the Livewire component and the existing HTTP registration endpoint. */
class VehicleVisitRegistration
{
    public function register(array $data, int $userId): array
    {
        try {
            $submissionId = $data['submission_id'] ?? null;
            if (is_string($submissionId) && Str::isUuid($submissionId)) {
                $existing = Visit::where('submission_id', $submissionId)->first();
                if ($existing) {
                    return $this->registeredVisit($existing, $userId, false);
                }
            }

            [$validated, $photos] = $this->validate($data);

            return $this->persist($validated, $photos, $userId);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Configuration, validation DB queries and the initial UUID lookup
            // must also return the same recoverable contract, without SQL/PII.
            Logger::error('No se pudo verificar un envío vehicular.', [
                'exception_type' => get_class($exception),
            ]);

            return $this->result([
                'message' => 'No se pudo verificar el guardado. Sus datos se conservaron; puede reintentar el mismo envío.',
                'outcome' => 'unknown',
            ], 503);
        }
    }

    public function validate(array $data): array
    {
        if (isset($data['tokens']) && is_array($data['tokens']) && array_key_exists(10, $data['tokens'])) {
            $data['tokens'][10] = null;
        }
        $config = config::find(1);
        $enableFotoLicencia = ($config->enable_fotolicencia ?? 0) == 1;
        $enableFotoPlaca = ($config->enable_fotoplaca ?? 1) == 1;
        $enableFotoVisitante = ($config->enable_fotovisitante ?? 0) == 1;
        $nombreManual = data_get($data, 'nombreManual');
        $esIngresoManual = is_string($nombreManual) && trim($nombreManual) !== '';
        $photoBytes = [];
        $validatePhoto = function ($attribute, $value, $fail) use (&$photoBytes) {
            if (!is_string($value) || !preg_match('#\Adata:image/(png|jpe?g);base64,([A-Za-z0-9+/]+={0,2})\z#iD', $value, $matches)) {
                $fail('La foto no es válida. Tómela nuevamente antes de guardar.');

                return;
            }

            $bytes = base64_decode($matches[2], true);
            $image = $bytes === false ? false : @getimagesizefromstring($bytes);
            $expectedMime = strtolower($matches[1]) === 'png' ? 'image/png' : 'image/jpeg';

            if ($image === false || !isset($image['mime']) || $image['mime'] !== $expectedMime) {
                $fail('La foto no es válida. Tómela nuevamente antes de guardar.');

                return;
            }

            $photoBytes[$attribute] = [
                'bytes' => $bytes,
                'extension' => $expectedMime === 'image/png' ? 'png' : 'jpg',
            ];
        };

        $tokenRules = $esIngresoManual ? [] : [
            'tokens.0' => 'required|string|max:8',
            'tokens.1' => 'required|string|max:2',
            'tokens.2' => 'required|string|max:30',
            'tokens.3' => 'nullable|string|max:70',
            'tokens.4' => 'required|string|max:45',
            'tokens.5' => 'nullable|string|max:70',
            'tokens.6' => 'required|string|max:20',
            'tokens.7' => 'required|string|max:20',
            'tokens.8' => 'required|string|max:13',
            'tokens.9' => 'nullable|string|max:8',
            'tokens.10' => 'nullable',
        ];

        $rules = [
            'submission_id' => 'sometimes|required|uuid',
            'placa' => 'required|string|max:8',
            'cono' => 'required|string|max:20',
            'tokens' => $esIngresoManual ? 'nullable|array' : 'required|array|size:11',
            'residente' => ['required', 'integer', Rule::exists('residentes', 'id')->where('estado', '1')],
            'nombreManual' => 'nullable|string|max:30',
            'es_repartidor' => 'sometimes|boolean',
        ];

        $rules += $tokenRules;

        if ($enableFotoLicencia && !$esIngresoManual) {
            $rules['photo1'] = ['required', 'string', 'max:6000000', $validatePhoto];
        }

        if ($enableFotoPlaca) {
            $rules['photo2'] = ['required', 'string', 'max:6000000', $validatePhoto];
        }

        if ($enableFotoVisitante) {
            $rules['photo3'] = ['required', 'string', 'max:6000000', $validatePhoto];
        }

        $validated = Validator::make(
            $data,
            $rules,
            [
                'placa.required' => 'Ingrese la placa del vehículo',
                'placa.max' => 'La placa no puede superar los 8 caracteres.',
                'cono.required' => 'Ingrese el número de cono',
                'cono.max' => 'El número de cono no puede superar los 20 caracteres.',
                'tokens.required' => 'No escaneó la licencia del visitante',
                'tokens.size' => 'Vuelva a escanear la licencia antes de guardar.',
                'residente.required' => 'Seleccione el número de casa',
                'residente.exists' => 'Seleccione una casa activa.',
                'photo1.required' => 'Por favor tomar la foto de la licencia',
                'photo2.required' => 'Por favor tomar la foto de la placa',
                'photo3.required' => 'Por favor tomar la foto del visitante',
                'required' => 'Complete :attribute antes de guardar.',
                'string' => 'El dato de :attribute no es válido.',
                'max' => ':attribute no puede superar los :max caracteres.',
                'boolean' => 'La selección de :attribute no es válida.',
                'uuid' => 'No se pudo verificar el envío. Recargue el formulario.',
            ],
            array_merge(['placa' => 'la placa', 'cono' => 'el cono', 'residente' => 'la casa', 'nombreManual' => 'el nombre del visitante'],
                array_combine(array_map(function ($index) { return 'tokens.'.$index; }, range(0, 9)), array_values(VehicleLicense::labels())))
        )->validate();

        $validated['nombreManual'] = $esIngresoManual ? trim($nombreManual) : '';

        return [$validated, $photoBytes];
    }

    private function persist(array $data, array $photoBytes, int $userId): array
    {
        $submissionId = $data['submission_id'] ?? null;
        $disk = null;
        $storedFiles = [];
        $transactionStarted = false;
        $commitAttempted = false;
        $visit = null;

        try {
            $disk = Storage::disk('visits');
            $photoPaths = [];
            foreach ($photoBytes as $field => $photo) {
                $filename = str_replace('photo', 'visit', $field) . '_' . Str::uuid() . '.' . $photo['extension'];
                $storedFiles[] = $filename;
                if (!$disk->put($filename, $photo['bytes'])) {
                    throw new \RuntimeException('No se pudo guardar una fotografía de la visita.');
                }

                $photoPaths[['photo1' => 'path_licencia', 'photo2' => 'path_placa', 'photo3' => 'path_visitante'][$field]] = $filename;
            }

            DB::beginTransaction();
            $transactionStarted = true;
            $visit = $this->newVisit($data, $userId);
            foreach ($photoPaths as $column => $path) {
                $visit->$column = $path;
            }
            if (!$visit->save()) {
                throw new \RuntimeException('No se pudo registrar la visita.');
            }

            $log = new log;
            $log->user_id = $userId;
            $log->accion = Str::limit('Ingreso una nueva Visita de ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido
                . ' al residente ' . $visit->residente->fullname(false), 200, '');
            if (!$log->save()) {
                throw new \RuntimeException('No se pudo registrar la bitácora.');
            }

            $commitAttempted = true;
            DB::commit();
        } catch (Throwable $exception) {
            $notSaved = !$transactionStarted;
            if ($transactionStarted && !$commitAttempted) {
                try {
                    DB::rollBack();
                    $notSaved = true;
                } catch (Throwable $rollbackException) {
                    $notSaved = false;
                }
            }

            $existingVisit = null;
            if ($transactionStarted && !$notSaved) {
                // A COMMIT may have succeeded even when its acknowledgement was
                // lost. Verify over a fresh connection, outside the old snapshot.
                DB::purge();
            }
            if ($submissionId || ($commitAttempted && $visit && $visit->id)) {
                try {
                    $existingVisit = $submissionId
                        ? Visit::where('submission_id', $submissionId)->first()
                        : Visit::find($visit->id);
                } catch (Throwable $lookupException) {
                    Logger::error('No se pudo verificar el resultado de un envío vehicular.', [
                        'exception_type' => get_class($lookupException),
                    ]);
                }
            }

            // Never remove files that may belong to a committed visit. An
            // ambiguous result retains its files until it can be reconciled.
            $referencedFiles = $existingVisit ? [
                $existingVisit->path_licencia, $existingVisit->path_placa, $existingVisit->path_visitante,
            ] : [];
            if ($notSaved || $existingVisit) {
                foreach (array_diff($storedFiles, $referencedFiles) as $filename) {
                    try {
                        if ($disk && !$disk->delete($filename)) {
                            Logger::error('No se pudo limpiar una foto de una visita vehicular fallida.');
                        }
                    } catch (Throwable $cleanupException) {
                        Logger::error('No se pudo limpiar una foto de una visita vehicular fallida.', [
                            'exception_type' => get_class($cleanupException),
                        ]);
                    }
                }
            }
            if ($existingVisit) {
                return $this->registeredVisit($existingVisit, $userId, !$submissionId);
            }

            $context = [
                'exception_type' => get_class($exception),
                'outcome' => $notSaved ? 'not_saved' : 'unknown',
                'submission_id' => $submissionId,
            ];
            if ($exception instanceof QueryException && $exception->errorInfo) {
                $context['sql_state'] = $exception->errorInfo[0] ?? null;
                $context['database_code'] = $exception->errorInfo[1] ?? null;
            }
            Logger::error('No se pudo guardar una visita vehicular.', $context);

            return $this->result([
                'message' => $notSaved
                    ? 'No se guardó la visita. Se conservaron los datos y puede reintentar.'
                    : 'No se pudo confirmar el guardado. Se conservaron los datos; puede reintentar sin duplicar la visita.',
                'outcome' => $notSaved ? 'not_saved' : 'unknown',
            ], 503);
        }

        return $this->vehicleVisitResponse($visit, !$submissionId);
    }

    private function newVisit(array $data, int $userId): Visit
    {
        $tokens = $data['tokens'] ?? [];
        $nombreManual = $data['nombreManual'];
        $visit = new Visit;
        $visit->cono = $data['cono'];
        $visit->residente_id = $data['residente'];
        $visit->Placa = $data['placa'];

        if ($nombreManual !== '') {
            $visit->Primer_Nombre = $nombreManual;
            $visit->Primer_Apellido = '';
            $visit->cui = '';
            $visit->Fecha_nac = '';
        } else {
            $visit->numeroDocumento = $tokens[0];
            $visit->tipoLicencia = $tokens[1];
            $visit->Primer_Nombre = $tokens[2];
            $visit->Segundo_Nombre = $tokens[3] ?? null;
            $visit->Primer_Apellido = $tokens[4];
            $visit->Segundo_Apellido = $tokens[5] ?? null;
            $visit->Fecha_nac = $tokens[6];
            $visit->Fecha_vencimiento = $tokens[7];
            $visit->cui = $tokens[8];
            $visit->tel_emergencia = $tokens[9] ?? null;
        }

        $visit->tipo_visita = 'vehicular';
        $visit->es_repartidor = filter_var(data_get($data, 'es_repartidor', false), FILTER_VALIDATE_BOOLEAN);
        $visit->fechaingreso = now();
        $visit->created_by = $userId;
        $visit->submission_id = $data['submission_id'] ?? null;

        return $visit;
    }

    private function registeredVisit(Visit $visit, int $userId, bool $legacy): array
    {
        if ((int) $visit->created_by !== $userId) {
            return $this->result(['message' => 'No se pudo verificar este envío. Inicie un nuevo registro.'], 409);
        }

        return $this->vehicleVisitResponse($visit, $legacy);
    }

    private function vehicleVisitResponse(Visit $visit, bool $legacy)
    {
        if ($legacy) {
            return $this->result(json_encode($visit));
        }

        return $this->result([
            'id' => $visit->id,
            'redirect_url' => route('detailv.visits', ['id' => $visit->id]),
        ]);
    }

    private function result($body, int $status = 200): array
    {
        return ['status' => $status, 'body' => $body];
    }
}
