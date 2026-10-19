<?php

return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'directory' => 'livewire-tmp',
        'rules' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:4395'],
    ],
];
