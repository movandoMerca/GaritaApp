<?php

namespace App\Http\Controllers;

use App\Models\log;
use App\Models\Resident;
use App\Models\Visit;
use App\Models\config as AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class PedestrianVisitController extends Controller
{
    public function create()
    {
        abort_unless($this->pedestrianVisitsEnabled(), 404);

        $page_title = 'Visita peatonal';
        $page_description = 'Registro con DPI';
        $residents = Resident::where('estado', '1')->orderBy('Nombres')->get();

        return view('visits.pedestrian', compact('page_title', 'page_description', 'residents'));
    }

    public function save(Request $request)
    {
        abort_unless($this->pedestrianVisitsEnabled(), 404);

        if (is_string($request->input('cui'))) {
            $request->merge(['cui' => preg_replace('/[\s-]+/', '', $request->input('cui'))]);
        }
        $data = $request->validate([
            'Primer_Nombre' => 'required|string|max:30',
            'Segundo_Nombre' => 'nullable|string|max:70',
            'Primer_Apellido' => 'required|string|max:45',
            'Segundo_Apellido' => 'nullable|string|max:70',
            'cui' => 'required|string|regex:/^[0-9]{13}$/',
            'Fecha_nac' => 'required|date_format:Y-m-d|before_or_equal:today',
            'residente' => ['required', 'integer', Rule::exists('residentes', 'id')->where('estado', '1')],
            'photo_dpi' => 'required|image|mimes:png,jpg,jpeg|max:8192',
        ], [
            'cui.regex' => 'El CUI debe contener 13 dígitos.',
            'residente.exists' => 'Seleccione un residente activo.',
            'photo_dpi.required' => 'Capture el frente del DPI antes de guardar.',
        ]);

        $photo = $request->file('photo_dpi');
        $filename = 'dpi_' . Str::uuid() . '.' . $photo->extension();
        $disk = Storage::disk('visits');

        try {
            if (!$disk->put($filename, file_get_contents($photo->getRealPath()))) {
                throw new RuntimeException('No se pudo guardar la foto del DPI.');
            }

            $visit = DB::transaction(function () use ($data, $filename) {
                $visit = new Visit;
                foreach (['Primer_Nombre', 'Segundo_Nombre', 'Primer_Apellido', 'Segundo_Apellido', 'cui', 'Fecha_nac'] as $field) {
                    $visit->$field = $data[$field] ?? null;
                }
                $visit->residente_id = $data['residente'];
                $visit->tipo_visita = 'peatonal';
                $visit->es_repartidor = false;
                $visit->Placa = null;
                $visit->numeroDocumento = null;
                $visit->tipoLicencia = null;
                $visit->Fecha_vencimiento = null;
                $visit->cono = null;
                $visit->path_licencia = $filename;
                $visit->fechaingreso = now();
                $visit->created_by = auth()->id();
                $visit->saveOrFail();

                $log = new log;
                $log->user_id = auth()->id();
                $log->accion = Str::limit('Ingreso una visita peatonal #' . $visit->id . ' de ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido
                    . ' al residente ' . $visit->residente->fullname(false), 200, '');
                $log->saveOrFail();

                return $visit;
            });
        } catch (Throwable $exception) {
            report($exception);
            try {
                $disk->delete($filename);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            return response()->json(['message' => 'No se pudo guardar la visita. Sus datos siguen en el formulario; intente nuevamente.'], 503);
        }

        return response()->json([
            'id' => $visit->id,
            'redirect_url' => route('detailv.visits', ['id' => $visit->id]),
        ], 201);
    }

    private function pedestrianVisitsEnabled(): bool
    {
        $config = AppConfig::find(1);

        return $config === null || (int) ($config->enable_visita_peatonal ?? 1) === 1;
    }
}
