<?php

use App\Http\Controllers\ResidentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\superadminController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;
use PhpParser\Node\Expr\PostDec;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/licencia',[superadminController::class,'licencia'])->name('licencia')->withoutMiddleware('web');
Route::post('/validar',[superadminController::class,'validacion'])->name('validar')->withoutMiddleware('web');
Route::post('/trial',[superadminController::class,'trial'])->name('trial')->withoutMiddleware('web');


Route::get('/', 'PagesController@index')->middleware('auth');

Route::get('/log/index',[LogController::class,'index'])->name('index.Logs')->middleware('auth');

Route::prefix('resident')->group(function () {

    Route::get('index', [ResidentController::class, 'index'])->name('index.resident')->middleware('auth');
    Route::get('create', [ResidentController::class, 'create'])->name('create.resident')->middleware('auth');
    Route::get('edit/{id}', [ResidentController::class, 'edit'])->name('edit.resident')->middleware('auth');
    route::get('detail/{id}', [ResidentController::class, 'detail'])->name('detail.resident')->middleware('auth');
    Route::post('save', [ResidentController::class, 'save'])->name('save.resident')->middleware('auth');
    Route::post('saveEdit', [ResidentController::class, 'saveEdit'])->name('saveEdit.resident')->middleware('auth');
    Route::get('delete/{id}', [ResidentController::class, 'delete'])->name('delete.resident')->middleware('auth');
    Route::get('ajax', [ResidentController::class, 'detail_ajax'])->name('detail_ajax.resident')->middleware('auth');
    Route::get('indexcsv', [ResidentController::class, 'indexcsv'])->name('indexcsv.resident')->middleware('auth');
    Route::post('savecsv', [ResidentController::class, 'carga_csv'])->name('savecsv.resident')->middleware('auth');
});

Route::prefix('users')->group(function () {

    Route::get('index', [UserController::class, 'index'])->name('index.user')->middleware(['auth', 'admin']);
    Route::get('create', [UserController::class, 'create'])->name('create.user')->middleware(['auth', 'admin']);
    Route::get('edit/{id}', [UserController::class, 'edit'])->name('edit.user')->middleware('auth');
    Route::get('edit/password/{id}', [UserController::class, 'editPassword'])->name('editPW.user')->middleware('auth');
    Route::post('/savepw', [UserController::class, 'savepw'])->name('savepw.user')->middleware('auth');
    route::get('detail/{id}', [UserController::class, 'detail'])->name('detail.user')->middleware('auth');
    Route::post('save', [UserController::class, 'save'])->name('save.user')->middleware('auth');
    Route::post('saveEdit', [UserController::class, 'saveEdit'])->name('saveEdit.user')->middleware('auth');
    Route::get('camaras', [UserController::class, 'configuracion_camaras'])->name('camaras.user')->middleware('auth');
    Route::post('save_camaras', [UserController::class, 'saveConfig'])->name('saveConfig.user')->middleware('auth');
});

Route::prefix('visit')->group(function () {

    Route::get('create', [VisitController::class, 'create'])->name('create.visits')->middleware('auth');
    Route::get('index', [VisitController::class, 'index'])->name('index.visits')->middleware('auth');
    Route::get('detail', [VisitController::class, 'detail'])->name('detail.visits')->middleware('auth');
    Route::get('detailegreso/{id}', [VisitController::class, 'detailegreso'])->name('detailegreso.visits')->middleware('auth');
    Route::post('save', [VisitController::class, 'save'])->name('save.visits')->middleware('auth');
    Route::get('table/{from}/{to}', [VisitController::class, 'table'])->name('table.visits')->middleware('auth');
    Route::post('reportbydate', [VisitController::class, 'reportebydate'])->name('reportbydate.visits')->middleware('auth');
    Route::post('reportbyresi', [VisitController::class, 'reportebyresi'])->name('reportbyresi.visits')->middleware('auth');
    Route::get('reportbyresident', [VisitController::class, 'reportbyresidentform'])->name('byresident.visits')->middleware('auth');
    Route::get('ajax', [ReportController::class, 'detail_ajax'])->name('widajax.visits')->middleware('auth');
    Route::get('tablebyresident/{from}/{to}/{id}', [VisitController::class, 'tablebyresident'])->name('tablebyresident.visits')->middleware('auth');
    Route::post('saveimg', [VisitController::class, 'saveimg'])->name('saveimg.visits')->middleware('auth');
    Route::get('detailvisit/{id}', [VisitController::class, 'detailvisit'])->name('detailv.visits')->middleware('auth');
    Route::get('/imagen/{filename}', [VisitController::class, 'get_img'])->name('img.visits')->middleware('auth');
    Route::post('savelicencia', [VisitController::class, 'img_camara'])->name('saveimgcamara.visits')->middleware('auth');
    Route::get('create/licencia/{id}', [VisitController::class, 'take_picture_L'])->name('takephotoL.visits')->middleware('auth');
    Route::get('create/visitante/{id}', [VisitController::class, 'take_picture_V'])->name('takephotoV.visits')->middleware('auth');
    Route::get('create/placa/{id}', [VisitController::class, 'take_picture_P'])->name('takephotoP.visits')->middleware('auth');
    Route::post('egreso', [VisitController::class, 'egreso'])->name('egreso.visits')->middleware('auth');
});

Route::prefix('config')->group(function () {
    Route::get('index', [superadminController::class, 'config'])->name('index.config')->middleware('super');
    Route::post('save', [superadminController::class, 'save'])->name('save.config')->middleware('super');
    Route::get('/brand', [superadminController::class, 'get_brand'])->name('brand.config')->middleware('auth');
    Route::get('/logo', [superadminController::class, 'get_login'])->name('logo.config')->middleware('guest');
});





Auth::routes([
    'register' => false,
    'verify' => false, // Email Verification Routes...
]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
