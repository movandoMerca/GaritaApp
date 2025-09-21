<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resident;
use App\Models\config;
use App\Models\log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;



class ResidentController extends Controller
{
    


    public function index()
     {
         $config = config::find(1);
        $page_title = 'Residentes';
        $page_description = 'Lista de todos los residentes del condominio';

        $residents = Resident::where('estado','1')->get();    
        $image = base64_encode(Storage::disk('public')->get($config->path_brand));     

           return view('residents.resident_crud',compact('residents','page_title','page_description','image'));

     }   

     public function Create()
     {
        $page_title = 'Residentes';
        $page_description = 'Crear residente';
        $config = config::find(1);

         return view('residents.resident',compact('page_title','page_description','config'));
     }

     public function edit($id)
     {
         $resident = Resident::find($id);
         $page_title = 'Residentes';
         $page_description = 'Editar datos del residente';
         $config = config::find(1);

        return view('residents.resident_edit',compact('resident','page_title','page_description','config'));

     }

     public function detail($id)
     {
         $resident = Resident::find($id);
         $page_title = 'Residentes';
         $page_description = 'Detalle del residente';
         $config = config::find(1);
        return view('residents.residents_detail',compact('resident','page_title','page_description','config'));

     }

     public function save(Request $request)
     {   

        $request->validate([
            'Nombres' => 'required|max:150',
            'Apellidos' => 'max:50|required',
            'Nombres2' => 'nullable|max:100',
            'Apellidos2' => 'max:45|nullable',
            'Telefono' => 'nullable',
            'Direccion' => 'required|max:100', 
            'Codigo' => 'required|max:20|unique:residentes', 
            'accesotel' => 'nullable'               
        ]);



            $resident = new Resident;
            $resident->Codigo = $request->input('Codigo');
            $resident->Nombres = $request->input('Nombres');
            $resident->Apellidos = $request->input('Apellidos');
            $resident->Telefono = $request->input('Telefono');
            $resident->Direccion = $request->input('Direccion');
            $resident->Nombres2 = $request->input('Nombres2');
            $resident->Apellidos2 = $request->input('Apellidos2');
            $resident->accesotel = $request->input('accesotel');

            if ($request->input('tipo') == 'on'){
                $resident->tipoResidente = 1;
            } else {
                $resident->tipoResidente = 0;
            }


            $resident->estado = 1;
           

            if($resident->save()){

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Creo un nuevo residente';
                $log->save();    

            }

          



         return redirect()->route('index.resident');

     }

     public function saveEdit(Request $request)
     {   

        $resident = Resident::find($request->input('id'));

        $request->validate([
            'Nombres' => 'required|max:150',
            'Apellidos' => 'max:50|required',
            'Nombres2' => 'nullable|max:100',
            'Apellidos2' => 'max:45|nullable',
            'Telefono' => 'nullable',
            'Direccion' => 'required|max:100',  
            'Codigo' => 'required|max:20|unique:residentes,Codigo,'.$resident->id,   
            'accesotel' => 'nullable'          
        ]);

            
            $resident->Codigo = $request->input('Codigo');
            $resident->Nombres = $request->input('Nombres');
            $resident->Apellidos = $request->input('Apellidos');
            $resident->Nombres2 = $request->input('Nombres2');
            $resident->Apellidos2 = $request->input('Apellidos2');
            $resident->Telefono = $request->input('Telefono');
            $resident->Direccion = $request->input('Direccion');
            $resident->accesotel = $request->input('accesotel');
            $resident->save();

            if ($request->input('tipo') == 'on'){
                $resident->tipoResidente = 1;
            } else {
                $resident->tipoResidente = 0;
            }

            if ($request->input('estado') == 'on'){
                $resident->estado = 1;
            } else {
                $resident->estado = 0;
            }

           
            if($resident->save()){

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Edito al residente '.$resident->fullname(false);
                $log->save();    

            }


         return redirect()->route('detail.resident',['id'=>$resident->id]);
         
     }


     public function delete($id)    
     {
         $resident = Resident::find($id);
         $resident->estado = 2;
         if($resident->save()){

            $log = new log;
            $log->user_id = auth()->user()->id;
            $log->accion = 'Elimino al residente '.$resident->fullname(false);
            $log->save();    

        }

         return redirect()->route('index.resident');

     }


     public function detail_ajax()
     {
        $resident = Resident::find($_GET['id']);

        return response()->json(json_encode($resident));

     }

     public function carga_csv()
     {

            $filas = $_POST["rows"];

            $i = 0;

            $fila1 = explode(';',$filas[0]);
            if( is_numeric($fila1[0])){
                
            } else{
                $i = 1;
            }

            for ($i; $i < count($filas) ; $i++   ) { 
                if($filas[$i] != ""){
                $fila = explode(';',$filas[$i]);
                if(count($fila) > 7){
                    $resident = new Resident;
                    $resident->Codigo = $fila[0];
                    $resident->Nombres = $fila[1]; 
                    $resident->Nombres2 = $fila[2]; 
                    $resident->Apellidos = $fila[3]; 
                    $resident->Apellidos2 = $fila[4]; 
                    $resident->accesotel = $fila[5]; 
                    $resident->Direccion = $fila[6]; 
                    $resident->Telefono = $fila[7]; 
                    $resident->tipoResidente = $fila[8]; 
                    $resident->estado = 1;                     
                    $resident->save();               

                }
            }
            }

            $residents = Resident::latest()
            ->take($i-1)
            ->get();

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Realizo una Carga Masiva de datos';
                $log->save();    
          
            
         
            
            return response()->json(json_encode($residents));


     }

     public function indexcsv()
     {       
        $page_title = 'Residentes';
        $page_description = 'Carga de Residentes por csv';

       return view('residents.resident_csv',compact('page_title','page_description'));
     }


}
