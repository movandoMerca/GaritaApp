<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class is_admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $user=  [
            'items' => [
                // Dashboard
                [
                    'title' => 'Visitas',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Barcode-scan.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => 'visit/create',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas del día',
                    'root' => true,
                    'icon' => 'media/svg/icons/Home/Clock.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => 'visit/detail',
                    'new-tab' => false,
                ],
            
            ]
        
        ];

        $admin =  [

            'items' => [
                // Dashboard
                [
                    'title' => 'Dashboard',
                    'root' => true,
                    'icon' => 'media/svg/icons/Design/Layers.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => '/',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas del día',
                    'root' => true,
                    'icon' => 'media/svg/icons/Home/Clock.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => 'visit/detail',
                    'new-tab' => false,
                ],
        
                //General
                [
                    'section' => 'General',
                ],
                [
                    'title' => 'Residentes',
                    'root' => true,
                    'icon' => 'media/svg/icons/Home/Building.svg',
                    'page' => 'resident/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Crear Residente',
                    'root' => true,
                    'icon' => 'media/svg/icons/Code/Plus.svg',
                    'page' => 'resident/create',
                    'new-tab' => false,
                ],
                
                //Reports
                [
                    'section' => 'Reportes',
                ],
                [
                    'title' => 'Visitas por fecha',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Chart-pie.svg',
                    'page' => 'visit/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas por residente',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Chart-bar1.svg',
                    'page' => 'visit/reportbyresident',
                    'new-tab' => false,
                ],
                //Admin
                [
                    'section' => 'Administración',
                    'display' => false
                ],
                [
                    'title' => 'Usuarios',
                    'root' => true,
                    'icon' => 'media/svg/icons/General/User.svg',
                    'page' => 'users/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Crear Usuario',
                    'root' => true,
                    'icon' => 'media/svg/icons/Communication/Add-user.svg',
                    'page' => 'users/create',
                    'new-tab' => false,
                ],
                    [
                        'title' => 'Carga de Residentes',
                        'root' => true,
                        'icon' => 'media/svg/icons/Files/Upload.svg', // or can be 'flaticon-home' or any flaticon-*
                        'page' => 'resident/indexcsv',
                        'new-tab' => false,
                    ],
            
         
            ]
        
        ];

      
        $superuser =  [

            'items' => [
                // Dashboard
                [
                    'title' => 'Dashboard',
                    'root' => true,
                    'icon' => 'media/svg/icons/Design/Layers.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => '/',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Barcode-scan.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => 'visit/create',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas del día',
                    'root' => true,
                    'icon' => 'media/svg/icons/Home/Clock.svg', // or can be 'flaticon-home' or any flaticon-*
                    'page' => 'visit/detail',
                    'new-tab' => false,
                ],
        
        
                //General
                [
                    'section' => 'General',
                ],
                [
                    'title' => 'Residentes',
                    'root' => true,
                    'icon' => 'media/svg/icons/Home/Building.svg',
                    'page' => 'resident/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Crear Residente',
                    'root' => true,
                    'icon' => 'media/svg/icons/Code/Plus.svg',
                    'page' => 'resident/create',
                    'new-tab' => false,
                ],
                //Reports
                [
                    'section' => 'Reportes',
                ],
                [
                    'title' => 'Visitas por fecha',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Chart-pie.svg',
                    'page' => 'visit/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Visitas por residente',
                    'root' => true,
                    'icon' => 'media/svg/icons/Shopping/Chart-bar1.svg',
                    'page' => 'visit/reportbyresident',
                    'new-tab' => false,
                ],
                //Admin
                [
                    'section' => 'Administración',
                    'display' => false
                ],
                [
                    'title' => 'Usuarios',
                    'root' => true,
                    'icon' => 'media/svg/icons/General/User.svg',
                    'page' => 'users/index',
                    'new-tab' => false,
                ],
                [
                    'title' => 'Crear Usuario',
                    'root' => true,
                    'icon' => 'media/svg/icons/Communication/Add-user.svg',
                    'page' => 'users/create',
                    'new-tab' => false,
                ],
                    [
                        'section' => 'Super Usuario',
                        'display' => false
                    ],
                    [
                        'title' => 'Configuracion',
                        'root' => true,
                        'icon' => 'fa fa-cog',
                        'page' => 'config/index',
                        'new-tab' => false,
                    ],
                    [
                        'title' => 'Carga de Residentes',
                        'root' => true,
                        'icon' => 'media/svg/icons/Files/Upload.svg',
                        'page' => 'resident/indexcsv',
                        'new-tab' => false,
                    ],
                    [
                        'title' => 'Log del Sistema',
                        'root' => true,
                        'icon' => 'media/svg/icons/Text/Bullet-list.svg',
                        'page' => 'log/index',
                        'new-tab' => false,
                    ]
            
         
            ]
        
        ];
        
        
        
            if(auth()->check() && auth()->user()->is_admin == 1){

                config(['menu_aside' => $admin]);

            } else{

                config(['menu_aside' => $user]);
            } 

            if(auth()->check() && auth()->user()->is_superuser == 1){

                config(['menu_aside' => $superuser]);

            }
        
        
        return $next($request);
    }
}
