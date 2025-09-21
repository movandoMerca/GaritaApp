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


class VisitController extends Controller
{
    //

    public function index()
    {
        $page_title = 'Reporte por Fechas';
        $page_description = '';

        return view('reports.reportbydateform', compact('page_title', 'page_description'));
    }

    public function table($from, $to)
    {

        $to = $to . ' 23:59';

        $visits = visit::whereBetween('fechaingreso', [$from, $to])->get();
        $page_title = 'Visitas';
        $page_description = 'Reporte por Fechas';

        $config = config::find(1);
        $image = base64_encode(Storage::disk('public')->get($config->path_brand));

        $log = new log;
        $log->user_id = auth()->user()->id;
        $log->accion = 'Genero Reporte de visitantes por fecha ';
        $log->save();

        return view('reports.reportbydate', compact('page_title', 'page_description', 'visits', 'config', 'image'));
    }


    public function tablebyresident($from, $to, $id)
    {
        $to = $to . ' 23:59';
        $visits = visit::whereBetween('fechaingreso', [$from, $to])->where('residente_id', $id)->get();
        $config = config::find(1);
        $image = base64_encode(Storage::disk('public')->get($config->path_brand));
        $page_title = 'Visitas';
        $page_description = 'Reporte por Residente';
        $config = config::find(1);
        $log = new log;
        $log->user_id = auth()->user()->id;
        $log->accion = 'Genero Reporte de visitantes por Residente ';
        $log->save();

        return view('reports.reportbyresident', compact('page_title', 'page_description', 'visits', 'config', 'image'));
    }

    public function detail()
    {
        $visits = visit::whereNull('fechaegreso')->get();
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
        if ($user->camara_id_licencia == null || $user->camara_id_placa == null || $user->camara_id_visitante == null) {
            return redirect()->route('camaras.user');
        }


        $residents = Resident::where('estado', '1')->get();
        $config = config::find(1);


        return view('visits.visit', compact('page_title', 'page_description', 'residents', 'config'));
    }

    public function save(Request $request)
    {

        $request->validate(
            [
                'placa' => 'required',
                'cono' => 'required',
                'tokens' => 'required',
                'residente' => 'required',
                'photo1' => 'required'

            ],
            [
                'placa.required' => 'Ingrese la placa del vehiculo',
                'cono.required' => 'Ingrese el numero de cono ',
                'tokens.required' => 'No escaneo la licencia del visitante',
                'residente.required' => 'Seleccione el numero de casa',
                'photo1.required' => 'Porfavor Tomar la foto',
            ]
        );


        $tokens = $request->input('tokens');
        $id_residente = $request->input('residente');
        $placa = $request->input('placa');
        $cono = $request->input('cono');
        $nombreManual = $request->input('nombreManual');

        if ($nombreManual != '') {
            $visit = new Visit;
            $visit->cono = $cono;
            $visit->residente_id = $id_residente;
            $visit->Placa = $placa;
            $visit->Primer_Nombre = $nombreManual;
            $visit->fechaingreso = date("Y-m-d H:i:s");
            $visit->created_by = auth()->user()->id;
        } else {

            $visit = new Visit;
            $visit->cono = $cono;
            $visit->residente_id = $id_residente;
            $visit->Placa = $placa;
            $visit->numeroDocumento = $tokens[0];
            $visit->tipoLicencia = $tokens[1];
            $visit->Primer_Nombre = $tokens[2];
            $visit->Segundo_Nombre = $tokens[3];
            $visit->Primer_Apellido = $tokens[4];
            $visit->Segundo_Apellido = $tokens[5];
            $visit->Fecha_nac = $tokens[6];
            $visit->Fecha_vencimiento = $tokens[7];
            $visit->cui = $tokens[8];
            $visit->tel_emergencia = $tokens[9];
            $visit->fechaingreso = date("Y-m-d H:i:s");
            $visit->created_by = auth()->user()->id;

        }

        if ($visit->save()) {

            $log = new log;
            $log->user_id = auth()->user()->id;
            $log->accion = 'Ingreso una nueva Visita de ' . $visit->Primer_Nombre . ' ' . $visit->Primer_Apellido . ' al residente ' . $visit->residente->fullname(false);
            $log->save();

        }

        $photo1_name = uniqid('photo1_') . '.png';
        $photo2_name = uniqid('photo2_') . '.png';
        $photo3_name = uniqid('photo3_') . '.png';

        $photo1 = $request->input('photo1');
        $photo2 = $request->input('photo2');
        $photo3 = $request->input('photo3');

        $photo1_file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo1));
        $photo2_file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo2));
        $photo3_file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $photo3));

        Storage::disk('visits')->put($photo1_name, $photo1_file);
        Storage::disk('visits')->put($photo2_name, $photo2_file);
        Storage::disk('visits')->put($photo3_name, $photo3_file);

        $visit->path_licencia = $photo1_name;
        $visit->path_placa = $photo2_name;
        $visit->path_visitante = $photo3_name;

        $visit->save();


        return response()->json(json_encode($visit));
    }

    public function reportebydate(Request $request)
    {
        $request->validate([
            'from' => 'required',
            'to' => 'required',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        return redirect()->route('table.visits', ['from' => $from, 'to' => $to]);
    }

    public function reportebyresi(Request $request)
    {


        $request->validate([
            'from' => 'required',
            'to' => 'required',
            'selectResidente' => 'required'
        ]);


        $from = $request->input('from');
        $to = $request->input('to');
        $id = $request->input('selectResidente');

        return redirect()->route('tablebyresident.visits', ['from' => $from, 'to' => $to, 'id' => $id]);
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


    public function get_img($filename)
    {
        $file = Storage::disk('visits')->get($filename);
        return new Response($file, 200);
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

        return view('visits.visit_fotoPlaca', compact('page_title', 'page_description', 'visit'));
    }

    public function egreso()
    {
        $visit = Visit::find($_POST['id']);

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
