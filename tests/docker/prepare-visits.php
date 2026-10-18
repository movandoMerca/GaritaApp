<?php
// This fixture is destructive ONLY inside the standalone disposable test instance.
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (app()->environment() !== 'testing' || config('database.connections.mysql.database') !== 'garita_visits_test'
    || config('filesystems.disks.visits.driver') !== 'local') {
    throw new RuntimeException('Only the isolated test instance with local storage is allowed.');
}
Illuminate\Support\Facades\DB::unprepared(file_get_contents(database_path('migrations/data.sql')));
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\DB::table('logs')->delete();
Illuminate\Support\Facades\DB::table('config')->where('id', 1)->update([
    'enable_fotolicencia' => 1, 'enable_fotoplaca' => 1, 'enable_fotovisitante' => 1,
    'enable_egreso' => 1, 'path_brand' => null, 'path_logo' => null, 'path_config' => '2099-01-01',
]);
Illuminate\Support\Facades\DB::table('residentes')->insert([
    'id' => 1, 'Codigo' => 'A-12', 'Nombres' => 'Residente', 'Apellidos' => 'Prueba',
    'Direccion' => 'Casa A-12', 'estado' => 1,
]);
Illuminate\Support\Facades\DB::table('users')->where('id', 1)->update([
    'password' => Illuminate\Support\Facades\Hash::make('visits-test-only'),
    'camara_id_licencia' => 'fake-license', 'camara_id_placa' => 'fake-plate', 'camara_id_visitante' => 'fake-visitor',
]);
$disk = Illuminate\Support\Facades\Storage::disk('visits');
foreach ($disk->allFiles() as $file) { $disk->delete($file); }
echo "Isolated visits fixture ready.\n";
