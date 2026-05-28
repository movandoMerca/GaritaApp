<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\User;
use App\Models\log;
use App\Models\config;
use App\Models\licencia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class superadminController extends Controller
{


        public function config()
        {
                $page_title = 'Configuracion';
                $page_description = 'Configuracion de la pagina';
                $config = config::find(1);

                return view('superadmin.configuracion', compact('page_title', 'page_description','config'));
        }

        public function save(Request $request)
        {

                $request->validate([
                        'path_brand' => 'mimes:png',
                        'path_logo' => 'mimes:png',
                ]);


                $config = config::find(1);

                $img1 = $request->file('path_brand');
                $img2 = $request->file('path_logo');


                if ($img1) {
                        $img1_name = time() . $img1->getClientOriginalName();
                        Storage::disk('public')->put($img1_name, File::get($img1));
                        $config->path_brand = $img1_name;
                        $config->save();
                }
                if ($img2) {
                        $img2_name = time() . $img2->getClientOriginalName();
                        Storage::disk('public')->put($img2_name, File::get($img2));
                        $config->path_logo = $img2_name;
                        $config->save();
                }


                if ($request->input('enable_fotolicencia') == 'on') {
                        $config->enable_fotolicencia = 1;
                } else {
                        $config->enable_fotolicencia = 0;
                }

                if ($request->input('enable_fotovisitante') == 'on') {
                        $config->enable_fotovisitante = 1;
                } else {
                        $config->enable_fotovisitante = 0;
                }

                if ($request->input('enable_fotoplaca') == 'on') {
                        $config->enable_fotoplaca = 1;
                } else {
                        $config->enable_fotoplaca = 0;
                }

                if ($request->input('enable_accesotel') == 'on') {
                        $config->enable_accesotel = 1;
                } else {
                        $config->enable_accesotel = 0;
                }

                if ($request->input('enable_tel') == 'on') {
                        $config->enable_tel = 1;
                } else {
                        $config->enable_tel = 0;
                }

                if ($request->input('enable_egreso') == 'on') {
                        $config->enable_egreso = 1;
                } else {
                        $config->enable_egreso = 0;
                }
                if ($request->input('enable_webcam') == 'on') {
                        $config->enable_webcam = 1;
                } else {
                        $config->enable_webcam = 0;
                }



                if($config->save()){

                        $log = new log;
                        $log->user_id = auth()->user()->id;
                        $log->accion = 'Realizo Cambios en la Configuracion de la Pagina ';
                        $log->save();    
                    }

                return redirect()->route('index.config');
        }

        public function get_brand()
        {
                $config = config::find(1);
                return $this->imageResponse($config ? $config->path_brand : null);
        }

        public function get_login()
        {
                $config = config::find(1);
                return $this->imageResponse($config ? $config->path_logo : null);
        }

        private function imageResponse($path)
        {
                if ($path && Storage::disk('public')->exists($path)) {
                        $file = Storage::disk('public')->get($path);
                } else {
                        $file = File::get(public_path('media/logos/mardysa.png'));
                }

                return new Response($file, 200, ['Content-Type' => 'image/png']);
        }


         public function licencia()
         {
                
                $numero =mt_rand(1,100);

                $token = licencia::find($numero);
                $config = config::find(1);


                 return view('superadmin.licence',compact('token','config'));
         }       

        public function validacion(Request $request)
        {
                $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';


                $token = licencia::find($request->input('id'));

                $pass = $request->input('pass');

                if( Hash::check($pass, $token->pass))
                {
                        Storage::disk('config')->put('licencia.ptdo', substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100).substr(str_shuffle($permitted_chars), 0, 100)); 
                        return redirect()->route('home');
                } 

        } 
         
        public function trial()
        {
                $config = config::find(1);
                $newDate = strtotime('+15 days',strtotime(date('Y-m-d')));
                $date = date('Y-m-d',$newDate);
                $config->path_config = $date;
                $config->save();
                return redirect()->route('home');

        }
         



}
