<?php

namespace App\Http\Controllers;

use App\Models\config;
use Illuminate\Http\Request;
use App\Models\log;
use App\Models\Visit;
use App\Models\Resident;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;


class VisitController extends Controller
{
    //

    public function index()
    {
        $page_title = 'Reporte por Fechas';
        $page_description = '';

        return view('reports.reportbydateform', compact('page_title', 'page_description'));
    }

    public function table(Request $request, $from, $to)
    {

        $filters = Visit::validatedFilters($request);
        $to = $to . ' 23:59:59';

        $visits = Visit::with('residente')->filterVisits($filters)->whereBetween('fechaingreso', [$from, $to])->get();
        $page_title = 'Visitas';
        $page_description = 'Reporte por Fechas';

        $config = config::find(1);
        $image = $this->base64BrandImage($config ? $config->path_brand : null);

        $log = new log;
        $log->user_id = auth()->user()->id;
        $log->accion = 'Genero Reporte de visitantes por fecha ';
        $log->save();

        return view('reports.reportbydate', compact('page_title', 'page_description', 'visits', 'config', 'image'));
    }

    public function tablePdf(Request $request, $from, $to)
    {
        $filters = Visit::validatedFilters($request);
        $toDateTime = $to . ' 23:59:59';

        $visits = Visit::with('residente')
            ->filterVisits($filters)
            ->whereBetween('fechaingreso', [$from, $toDateTime])
            ->orderBy('fechaingreso', 'asc')
            ->get();

        $config = config::find(1);
        $image = $this->base64BrandImage($config ? $config->path_brand : null);
        $photos = [];

        foreach ($visits as $visit) {
            $photos[$visit->id] = [
                'licencia' => $this->visitImageDataUri($visit->path_licencia),
                'placa' => $this->visitImageDataUri($visit->path_placa),
                'visitante' => $this->visitImageDataUri($visit->path_visitante),
            ];
        }

        $html = view('reports.reportbydate_pdf', compact('visits', 'config', 'image', 'photos', 'from', 'to'))->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $filename = 'reporte-visitas-' . $from . '-' . $to . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function visitImageDataUri($path)
    {
        if (!$path) {
            return null;
        }

        try {
            if (!Storage::disk('visits')->exists($path)) {
                return null;
            }

            $contents = Storage::disk('visits')->get($path);
            $imageInfo = @getimagesizefromstring($contents);
            $mime = $imageInfo['mime'] ?? 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }


    public function tablebyresident(Request $request, $from, $to, $id)
    {
        $filters = Visit::validatedFilters($request);
        $to = $to . ' 23:59:59';
        $visits = Visit::with('residente')->filterVisits($filters)->whereBetween('fechaingreso', [$from, $to])->where('residente_id', $id)->get();
        $config = config::find(1);
        $image = $this->base64BrandImage($config ? $config->path_brand : null);
        $page_title = 'Visitas';
        $page_description = 'Reporte por Residente';
        $config = config::find(1);
        $log = new log;
        $log->user_id = auth()->user()->id;
        $log->accion = 'Genero Reporte de visitantes por Residente ';
        $log->save();

        return view('reports.reportbyresident', compact('page_title', 'page_description', 'visits', 'config', 'image'));
    }

    public function detail(Request $request)
    {
        $filters = Visit::validatedFilters($request);
        $visits = Visit::with('residente')->filterVisits($filters)->whereNull('fechaegreso')->get();
        $config = config::find(1);
        $page_title = 'Visitas';
        $page_description = 'Vistantes activos';

        return view('visits.visit_table', compact('page_title', 'page_description', 'visits', 'config'));
    }

    public function Create()
    {
        $page_title = 'Visitas';
        $page_description = 'Nueva Visita';

        $user = Auth()->user();
        $config = config::find(1);

        if (
            (($config->enable_fotolicencia ?? 0) == 1 && $user->camara_id_licencia == null) ||
            (($config->enable_fotoplaca ?? 1) == 1 && $user->camara_id_placa == null) ||
            (($config->enable_fotovisitante ?? 0) == 1 && $user->camara_id_visitante == null)
        ) {
            return redirect()->route('camaras.user');
        }


        return view('visits.visit', compact('page_title', 'page_description'));
    }

    public function sessionState(Request $request)
    {
        return response()->json([
            'user_id' => $request->user()->getAuthIdentifier(),
            'csrf_token' => csrf_token(),
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function save(Request $request)
    {
        $result = app(\App\Services\VehicleVisitRegistration::class)->register(
            $request->all(),
            (int) $request->user()->getAuthIdentifier()
        );

        return response()->json($result['body'], $result['status']);
    }

    public function reportebydate(Request $request)
    {
        $filters = Visit::validatedFilters($request);
        $request->validate([
            'from' => 'required',
            'to' => 'required',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        return redirect()->route('table.visits', array_merge(['from' => $from, 'to' => $to], $filters));
    }

    public function reportebyresi(Request $request)
    {
        $filters = Visit::validatedFilters($request);


        $request->validate([
            'from' => 'required',
            'to' => 'required',
            'selectResidente' => 'required'
        ]);


        $from = $request->input('from');
        $to = $request->input('to');
        $id = $request->input('selectResidente');

        return redirect()->route('tablebyresident.visits', array_merge(['from' => $from, 'to' => $to, 'id' => $id], $filters));
    }

    public function reportbyresidentform()
    {
        $page_title = 'Reporte por Residente';
        $page_description = '';
        $residents = Resident::where('estado', '1')->get();

        return view('reports.reportbyresidentform', compact('page_title', 'page_description', 'residents'));
    }

    public function saveimg(Request $request)
    {

        $config = Config::find(1);
        if ($config->enable_fotolicencia == 1) {
            $request->validate([
                'licencia' => 'required',
            ]);
        }
        if ($config->enable_fotovisitante == 1) {
            $request->validate([
                'visitante' => 'required',
            ]);
        }




        $img1 = $request->file('licencia');
        $img2 = $request->file('visitante');

        $visit = Visit::find($request->input('idv'));

        if ($img1) {
            $img1_name = time() . $img1->getClientOriginalName();
            Storage::disk('visits')->put($img1_name, File::get($img1));
            $visit->path_licencia = $img1_name;
            if ($visit->save()) {

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cargo Foto de Licencia del visitante ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
                $log->save();

            }
        }
        if ($img2) {
            $img2_name = time() . $img2->getClientOriginalName();
            Storage::disk('visits')->put($img2_name, File::get($img2));
            $visit->path_visitante = $img2_name;
            if ($visit->save()) {

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cargo Foto del visitante ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
                $log->save();

            }
        }

        return redirect()->route('detailv.visits', ['id' => $visit->id]);
    }


    public function detailvisit($id)
    {
        $visits = visit::find($id);

        $page_title = 'Visitas';
        $page_description = 'Detalle de Visitas';
        $config = config::find(1);

        return view('visits.visit_detail', compact('page_title', 'page_description', 'visits', 'config'));
    }

    public function detailegreso($id)
    {
        $visits = visit::find($id);

        $page_title = 'Visitas';
        $page_description = 'Detalle de Visitas';
        $config = config::find(1);

        return view('visits.visit_detail_egreso', compact('page_title', 'page_description', 'visits', 'config'));
    }


    public function get_img(Request $request, $filename = null)
    {
        $filename = $filename ?? $request->query('filename');

        if (!is_string($filename) || trim($filename) === '' ||
            str_starts_with($filename, '/') || str_contains($filename, '\\') ||
            preg_match('/[\x00-\x1F\x7F]/', $filename)) {
            abort(404);
        }

        foreach (explode('/', $filename) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                abort(404);
            }
        }

        $disk = Storage::disk('visits');
        if (!$disk->exists($filename)) {
            abort(404);
        }

        $file = $disk->get($filename);
        $imageInfo = @getimagesizefromstring($file);
        if (!$imageInfo || empty($imageInfo['mime'])) {
            abort(404);
        }

        return new Response($file, 200, [
            'Content-Type' => $imageInfo['mime'],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function img_camara(Request $request)
    {
        $request->validate([
            'image' => ['required']
        ]);

        $config = config::find(1);


        $visit = Visit::find($request->input('id'));
        $img = $request->input('image');
        $image_parts = explode(";base64,", $img);

        $image_base64 = base64_decode($image_parts[1]);
        $fileName = uniqid() . '_foto.png';

        Storage::disk('visits')->put($fileName, $image_base64);

        if ($request->input('tipo') == 'Licencia') {
            $visit->path_licencia = $fileName;

            if ($visit->save()) {

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cargo Foto de Licencia del visitante ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
                $log->save();
            }


        } else if ($request->input('tipo') == 'Visitante') {
            $visit->path_visitante = $fileName;
            if ($visit->save()) {

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cargo Foto del visitante ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
                $log->save();

            }
        } else if ($request->input('tipo') == 'Placa') {
            $visit->path_placa = $fileName;
            if ($visit->save()) {

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cargo Foto del placa ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
                $log->save();

            }
        }

        $routes = [
            'Licencia' => 'takephotoV.visits',
            'Visitante' => 'takephotoP.visits',
        ];

        if (array_key_exists($request->input('tipo'), $routes)) {
            $route = $routes[$request->input('tipo')];
            return redirect()->route($route, ['id' => $visit->id]);
        } else {
            return redirect()->route('detailv.visits', ['id' => $visit->id]);
        }
    }

    public function take_picture_L($id)
    {
        $page_title = 'Visitas';
        $page_description = 'Detalle de Visitas';
        $visit = Visit::find($id);
        $config = config::find(1);

        if ($config->enable_fotolicencia == 0 && $config->enable_fotovisitante == 1) {

            return redirect()->route('takephotoV.visits', ['id' => $id]);
        } else if ($config->enable_fotolicencia == 1) {

            return view('visits.visit_fotoLicencia', compact('page_title', 'page_description', 'visit'));
        }
    }

    public function take_picture_V($id)
    {
        $page_title = 'Visitas';
        $page_description = 'Detalle de Visitas';
        $visit = Visit::find($id);

        return view('visits.visit_fotovisitante', compact('page_title', 'page_description', 'visit'));
    }

    public function take_picture_P($id)
    {
        $page_title = 'Visitas';
        $page_description = 'Detalle de Visitas';
        $visit = Visit::find($id);
        $config = config::find(1);

        if (($config->enable_fotoplaca ?? 1) != 1) {
            return redirect()->route('detailv.visits', ['id' => $visit->id]);
        }

        return view('visits.visit_fotoPlaca', compact('page_title', 'page_description', 'visit'));
    }

    public function egreso(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:visitas,id']);
        $visit = Visit::findOrFail($request->input('id'));

        if ($visit->fechaegreso !== null) {
            return redirect()->route('detailegreso.visits', ['id' => $visit->id]);
        }

        $visit->fechaegreso = date("Y-m-d H:i:s");
        $visit->updated_by = auth()->user()->id;
        if ($visit->save()) {
            $log = new log;
            $log->user_id = auth()->user()->id;
            $log->accion = 'Marco Egreso al visitante ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido;
            $log->save();
        }

        return redirect()->route('detailegreso.visits', ['id' => $visit->id]);
    }
}
