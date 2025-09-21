<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\log;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Input\Input;

class UserController extends Controller
{


    public function index()
    {
        $users = User::all();

        $page_title = 'Usuarios';
        $page_description = 'Lista de todos los usuarios del sistema';


        return view('users.user_crud',compact('users','page_title','page_description'));


    }

    public function Create()
     {
        $page_title = 'Usuarios';
        $page_description = 'Crear usuario';

         return view('users.user',compact('page_title','page_description'));
     }

     public function edit($id)
     {
         $user = User::find($id);
         $page_title = 'Usuarios';
         $page_description = 'Editar datos del usuario';

        return view('users.user_edit',compact('user','page_title','page_description'));

     }
     public function editPassword($id)
     {
         $user = User::find($id);
         $page_title = 'Usuarios';
         $page_description = 'Editar datos del usuario';

        return view('users.password_reset',compact('user','page_title','page_description'));

     }

     public function detail($id)
     {
         $user = User::find($id);
         $page_title = 'Usuarios';
         $page_description = 'Detalle del usuario';

        return view('users.users_detail',compact('user','page_title','page_description'));

     }

     public function save(Request $request)
     {

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'username' => ['required', 'string', 'max:255','unique:users'],
        ]);


            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = Hash::make($request->input('password'));
            $user->username = $request->input('username');
            $request->input('admin') == 'on' ? $user->is_admin = 1 : $user->is_admin = 0;
            if($user->save()){

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Creo un Nuevo Usuario ';
                $log->save();
            }


         return redirect()->route('index.user');

     }
     public function savepw(Request $request)
     {
        $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'actual' => ['required','string','min:6']
        ]);


        if(Hash::check($request->input('actual'), auth()->user()->password))
        {
            $user = User::find(auth()->user()->id);
            $user->password = Hash::make($request->input('password'));
            if($user->save()){

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Cambio la Contraseña de su usuario ';
                $log->save();
            }

        } else {
            $request->validate([
                'actual' => ['required','string','min:150']
            ]);
        }
        return redirect()->route('home');

     }

     public function saveEdit(Request $request)
     {
        $user = User::find($request->input('id'));
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'min:6', 'confirmed'],
            'username' => ['required', 'string', 'max:255','unique:users,username,'.$user->id],
        ]);



            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $request->input('password') == "" ?   '' :  $user->password = Hash::make($request->input('password'));
            $user->username = $request->input('username');
            isset($_POST['admin'])  ? $user->is_admin = 1 : $user->is_admin = 0;
            isset($_POST['active']) ? $user->status = 1 : $user->status = 0;
            if($user->save()){

                $log = new log;
                $log->user_id = auth()->user()->id;
                $log->accion = 'Edito al Usuario '.$user->name;
                $log->save();
            }


         return redirect()->route('index.user');

     }

     public function configuracion_camaras()
     {
        return view('users.config_camera');
     }

     public function saveConfig( Request $request )
     {
        $user = Auth()->user();

        if ($request->tipo == 'placa'){
            $user->camara_id_placa = $request->input('camaraID');
        }
        if ($request->tipo == 'visitante'){
            $user->camara_id_visitante = $request->input('camaraID');
        }
        if ($request->tipo == 'licencia'){
            $user->camara_id_licencia = $request->input('camaraID');
        }

        $user->save();




     }




}
