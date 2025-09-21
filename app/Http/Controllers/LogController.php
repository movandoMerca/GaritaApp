<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\log;

class LogController extends Controller
{
    public function index()
    {
        $page_title = 'Log';
        $page_description = 'Del Sistema';
        $logs = log::all();
        return view('Logs.logs', compact('page_title', 'page_description','logs'));
    }
}
