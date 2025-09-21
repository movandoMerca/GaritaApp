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
                    'icon' => 'media/svg/icons/Home/Building.svg',
                    'bullet' => 'line',
                    'root' => true,
                    'submenu' => [
                        [
                            [
                                'title' => 'Crear Residente',
                                'page' => 'resident/create',
                            ],
                            [
                                'title' => 'Lista de Residentes',
                                'page' => 'resident/index'
                            ],
        
                        ],
        
                    ]
                ],
                
                //Reports
                [
                    'section' => 'Reportes',
                ],
                [
                    'title' => 'Reportes',
                    'icon' => 'media/svg/icons/Shopping/Chart-pie.svg',
                    'bullet' => 'line',
                    'root' => true,
                    'submenu' => [
                        [
                            [
                                'title' => 'Visitas por fecha',
                                'page' => '/visit/index',
                            ],
                            [
                                'title' => 'Visitas por residente',
                                'page' => 'visit/reportbyresident'
                            ],
        
                        ],
        
                    ]
                ],
                //Admin
                [
                    'section' => 'Administración',
                    'display' => false
                ],
                [
                    'title' => 'Usuarios',
                    'icon' => 'media/svg/icons/General/User.svg',
                    'bullet' => 'line',
                    'display' => false,
                    'root' => false,
                    'submenu' => [
                        [
                            [
                                'title' => 'Crear Usuario',
                                'page' => 'users/create',
                            ],
                            [
                                'title' => 'Lista de Usuarios',
                                'page' => 'users/index'
                            ] 
        
                        ],
        
                    ]
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
                    'icon' => 'media/svg/icons/Home/Building.svg',
                    'bullet' => 'line',
                    'root' => true,
                    'submenu' => [
                        [
                            [
                                'title' => 'Crear Residente',
                                'page' => 'resident/create',
                            ],
                            [
                                'title' => 'Lista de Residentes',
                                'page' => 'resident/index'
                            ],
        
                        ],
        
                    ]
                ],
                //Reports
                [
                    'section' => 'Reportes',
                ],
                [
                    'title' => 'Reportes',
                    'icon' => 'media/svg/icons/Shopping/Chart-pie.svg',
                    'bullet' => 'line',
                    'root' => true,
                    'submenu' => [
                        [
                            [
                                'title' => 'Visitas por fecha',
                                'page' => '/visit/index',
                            ],
                            [
                                'title' => 'Visitas por residente',
                                'page' => 'visit/reportbyresident'
                            ],
        
                        ],
        
                    ]
                ],
                //Admin
                [
                    'section' => 'Administración',
                    'display' => false
                ],
                [
                    'title' => 'Usuarios',
                    'icon' => 'media/svg/icons/General/User.svg',
                    'bullet' => 'line',
                    'display' => false,
                    'root' => false,
                    'submenu' => [
                        [
                            [
                                'title' => 'Crear Usuario',
                                'page' => 'users/create',
                            ],
                            [
                                'title' => 'Lista de Usuarios',
                                'page' => 'users/index'
                            ],
        
                        ],
        
                    ]
                    ],
                    [
                        'section' => 'Super Usuario',
                        'display' => false
                    ],
                    [
                        'title' => 'Configuracion',
                        'icon' => 'fa fa-cog',
                        'bullet' => 'line',
                        'display' => false,
                        'root' => false,
                        'submenu' => [
                            [
                                [
                                    'title' => 'Configuracion de la pagina',
                                    'page' => 'config/index',
                                ]    ,
                                [
                                    'title' => 'Carga de Residentes',
                                    'page' => 'resident/indexcsv',
                                ] ,
                                [
                                    'title' => 'Log del Sistema',
                                    'page' => 'log/index',
                                ]                               
            
                            ],
            
                        ]
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
