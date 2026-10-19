<?php
// Replaces the test container's front controller ONLY; never mounted in normal Docker or deployed.
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/_visits_test_control') {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request = Illuminate\Http\Request::capture());
    $response->send();
    $kernel->terminate($request, $response);
    exit;
}
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
header('Content-Type: application/json');
if (!app()->environment('testing', 'visits-browser') || config('database.connections.mysql.database') !== 'garita_visits_test'
    || config('filesystems.disks.visits.driver') !== 'local'
    || ($_SERVER['HTTP_X_VISITS_TEST'] ?? '') !== 'isolated-visits-test') {
    http_response_code(403);
    echo json_encode(['error' => 'Only the isolated visits instance is allowed.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['photos'])) {
    if (!is_array($input['photos']) || count($input['photos']) !== 3 || array_diff($input['photos'], [0, 1])) {
        http_response_code(422); exit;
    }
    Illuminate\Support\Facades\DB::table('config')->where('id', 1)->update(array_combine(
        ['enable_fotolicencia', 'enable_fotoplaca', 'enable_fotovisitante'], $input['photos']
    ));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['cameras'])) {
    if (!is_array($input['cameras']) || count($input['cameras']) !== 3
        || count(array_filter($input['cameras'], 'is_string')) !== 3) {
        http_response_code(422); exit;
    }
    Illuminate\Support\Facades\DB::table('users')->where('id', 1)->update(array_combine(
        ['camara_id_licencia', 'camara_id_placa', 'camara_id_visitante'], $input['cameras']
    ));
}
echo json_encode([
    'database' => config('database.connections.mysql.database'),
    'storage' => config('filesystems.disks.visits.driver'),
    'test_key' => config('app.key') === 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=',
    'test_cookie' => config('session.cookie') === 'garita_visits_test_session',
    'visits' => Illuminate\Support\Facades\DB::table('visitas')->select([
        'id', 'submission_id', 'Placa', 'Primer_Nombre', 'es_repartidor', 'fechaegreso',
        'path_licencia', 'path_placa', 'path_visitante',
    ])->get(),
    'visit_logs' => Illuminate\Support\Facades\DB::table('logs')->where('accion', 'like', 'Ingreso una nueva Visita de%')->count(),
    'files' => Illuminate\Support\Facades\Storage::disk('visits')->allFiles(),
]);
