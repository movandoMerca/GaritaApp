<?php
// Aside menu

use Illuminate\Support\Facades\Auth;

$Menu =  [

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
            'title' => 'Visits',
            'root' => true,
            'icon' => 'media/svg/icons/Shopping/Barcode-scan.svg', // or can be 'flaticon-home' or any flaticon-*
            'page' => 'visit/create',
            'new-tab' => false,
        ],


        //General
        [
            'section' => 'General',
        ],
        [
            'title' => 'Residents',
            'icon' => 'media/svg/icons/Home/Building.svg',
            'bullet' => 'line',
            'root' => true,
            'submenu' => [
                [
                    [
                        'title' => 'Add Resident',
                        'page' => 'resident/create',
                    ],
                    [
                        'title' => 'List of Residents',
                        'page' => 'resident/index'
                    ],

                ],

            ]
        ],
        //Reports
        [
            'section' => 'Reports',
        ],
        [
            'title' => 'Reports',
            'icon' => 'media/svg/icons/Shopping/Chart-pie.svg',
            'bullet' => 'line',
            'root' => true,
            'submenu' => [
                [
                    [
                        'title' => 'Visit report by date',
                        'page' => 'custom/apps/profile/profile-1/overview',
                    ],
                    [
                        'title' => 'report of visits by resident',
                        'page' => 'custom/apps/profile/profile-1/personal-information'
                    ],

                ],

            ]
        ],
        //Admin
        [
            'section' => 'Admin',
            'display' => false
        ],
        [
            'title' => 'Users',
            'icon' => 'media/svg/icons/General/User.svg',
            'bullet' => 'line',
            'display' => false,
            'root' => false,
            'submenu' => [
                [
                    [
                        'title' => 'Add User',
                        'page' => 'users/create',
                    ],
                    [
                        'title' => 'List of Users',
                        'page' => 'users/index'
                    ],

                ],

            ]
            ]
    
 
    ]

];
    

    return $Menu;