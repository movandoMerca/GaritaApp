<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Visit;
use App\Models\Resident;

class ReportController extends Controller
{
    
    public function detail_ajax()
    {

        $datos = array();

        $domingo = date( 'Y-m-d', strtotime( 'sunday this week' ) );
        $lunes = date( 'Y-m-d', strtotime( 'monday this week' ) );
        $martes = date( 'Y-m-d', strtotime( 'tuesday this week' ) );
        $miercoles = date( 'Y-m-d', strtotime( 'wednesday this week' ) );
        $jueves = date( 'Y-m-d', strtotime( 'thursday this week' ) );
        $viernes = date( 'Y-m-d', strtotime( 'friday this week' ) );
        $sabado = date( 'Y-m-d', strtotime( 'saturday this week' ) );




        $visits = visit::whereDate('fechaingreso','=',$viernes)->get();  
        $datos[4] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$lunes)->get();  
        $datos[0] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$martes)->get();  
        $datos[1] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$miercoles)->get();  
        $datos[2] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$jueves)->get();  
        $datos[3] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$sabado)->get();  
        $datos[5] = $visits->count();

        $visits = visit::whereDate('fechaingreso','=',$domingo)->get();  
        $datos[6] = $visits->count();

        



        return response()->json(json_encode($datos));






    }




}
