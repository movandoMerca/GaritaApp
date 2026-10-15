<?php

namespace Tests\Feature;

use App\Models\log;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'garita_visits_test') {
            $this->markTestSkipped('Use phpunit.visits.xml with the dedicated garita_visits_test MySQL database.');
        }
        DB::unprepared(file_get_contents(database_path('migrations/data.sql')));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        DB::table('logs')->delete();
        DB::table('config')->where('id', 1)->update([
            'enable_fotolicencia' => 0, 'enable_fotovisitante' => 0, 'enable_fotoplaca' => 0,
            'path_brand' => null, 'path_logo' => null, 'path_config' => '2099-01-01',
        ]);
        DB::table('residentes')->insert([
            'id' => 1, 'Codigo' => 'A-12', 'Nombres' => 'Residente', 'Apellidos' => 'Prueba',
            'Direccion' => 'Casa A-12', 'estado' => 1,
        ]);
        Storage::fake('visits');
        Storage::fake('config');
        $this->actingAs(User::findOrFail(1));
    }

    private function pedestrian(array $changes = [])
    {
        return array_merge([
            'Primer_Nombre' => 'MARÍA', 'Segundo_Nombre' => 'LUCÍA', 'Primer_Apellido' => 'PÉREZ',
            'Segundo_Apellido' => 'LÓPEZ', 'cui' => '1234 56789 0101', 'Fecha_nac' => '1990-08-09',
            'residente' => 1,
            'photo_dpi' => UploadedFile::fake()->createWithContent('dpi.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='
            )),
        ], $changes);
    }

    private function vehicle(array $changes = [])
    {
        return array_merge([
            'residente' => 1, 'placa' => 'P123ABC', 'cono' => '12',
            'tokens' => ['12345678', 'C', 'JUAN', '', 'PEREZ', '', '01/01/1990', '01/01/2030', '1234567890101', '12345678', ''],
        ], $changes);
    }

    public function test_pedestrian_registration_photo_detail_and_exit()
    {
        $this->get(route('create.pedestrian.visits'))->assertOk()->assertSee('Registro de visita peatonal');
        $response = $this->postJson(route('save.pedestrian.visits'), $this->pedestrian([
            'tipo_visita' => 'vehicular', 'es_repartidor' => 1, 'Placa' => 'IGNORED',
        ]))->assertCreated();
        $visit = Visit::findOrFail($response->json('id'));
        $this->assertSame('peatonal', $visit->tipo_visita);
        $this->assertFalse($visit->es_repartidor);
        $this->assertSame('1234567890101', $visit->cui);
        foreach (['Placa', 'numeroDocumento', 'tipoLicencia', 'Fecha_vencimiento', 'cono'] as $field) {
            $this->assertNull($visit->$field);
        }
        $this->assertSame(1, $visit->created_by);
        Storage::disk('visits')->assertExists($visit->path_licencia);
        $this->get($response->json('redirect_url'))->assertOk()->assertSee('Foto del DPI')->assertDontSee('>Placa<', false);
        $this->get(route('img.visits', $visit->path_licencia))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->post(route('egreso.visits'), ['id' => $visit->id])->assertRedirect();
        $this->assertNotNull($visit->fresh()->fechaegreso);
        $this->assertSame(1, $visit->fresh()->updated_by);
        $this->assertDatabaseCount('logs', 2);
        $this->get(route('detailegreso.visits', $visit->id))->assertOk()->assertSee('Foto del DPI');
    }

    public function test_visit_photo_routes_serve_png_and_jpeg_from_storage()
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII=');
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/AX//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/AX//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Al//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IT//2Q==');
        Storage::disk('visits')->put('legacy.png', $png);
        Storage::disk('visits')->put('folder/visitor photo.jpg', $jpeg);

        $pngResponse = $this->get(route('img.visits', 'legacy.png'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
        $this->assertSame($png, $pngResponse->getContent());

        $jpegResponse = $this->get(route('img.visits.query', ['filename' => 'folder/visitor photo.jpg']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($jpeg, $jpegResponse->getContent());
    }

    public function test_visit_photo_route_rejects_missing_invalid_and_unknown_keys()
    {
        $this->get(route('img.visits.query'))->assertNotFound();
        $this->get(route('img.visits.query', ['filename' => '../outside.png']))->assertNotFound();
        $this->get(route('img.visits.query', ['filename' => '/absolute.png']))->assertNotFound();
        $this->get(route('img.visits.query', ['filename' => ['not-a-string']]))->assertNotFound();
        $this->get(route('img.visits.query', ['filename' => 'missing.png']))->assertNotFound();
    }

    public function test_visit_detail_views_use_relative_query_photo_urls()
    {
        $response = $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertCreated();
        $visit = Visit::findOrFail($response->json('id'));
        $visit->path_visitante = 'visitor/face one.jpg';
        $visit->path_licencia = 'identity/dpi.png';
        $visit->path_placa = 'vehicle/plate.png';
        $visit->save();
        DB::table('config')->where('id', 1)->update([
            'enable_fotovisitante' => 1,
            'enable_fotoplaca' => 1,
        ]);

        $expectedUrls = [
            route('img.visits.query', ['filename' => $visit->path_visitante], false),
            route('img.visits.query', ['filename' => $visit->path_licencia], false),
            route('img.visits.query', ['filename' => $visit->path_placa], false),
        ];

        foreach ([
            route('detailv.visits', $visit->id),
            route('detailegreso.visits', $visit->id),
        ] as $detailUrl) {
            $html = $this->get($detailUrl)->assertOk()->getContent();
            foreach ($expectedUrls as $imageUrl) {
                $this->assertStringContainsString('src="' . $imageUrl . '"', $html);
            }
            $this->assertStringContainsString('visitor%2Fface%20one.jpg', $html);
        }
    }

    public function test_pedestrian_validation_and_repeated_cui()
    {
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['cui' => '123']))->assertUnprocessable()->assertJsonValidationErrors('cui');
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['photo_dpi' => null]))->assertUnprocessable()->assertJsonValidationErrors('photo_dpi');
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['Fecha_nac' => '1990-02-30']))->assertUnprocessable()->assertJsonValidationErrors('Fecha_nac');
        DB::table('residentes')->where('id', 1)->update(['estado' => 0]);
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertUnprocessable()->assertJsonValidationErrors('residente');
        $this->get(route('create.pedestrian.visits'))->assertDontSee('A-12');
        DB::table('residentes')->where('id', 1)->update(['estado' => 1]);
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertCreated();
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertCreated();
        $this->assertDatabaseCount('visitas', 2);
    }

    public function test_storage_failure_does_not_create_a_visit()
    {
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        $disk->shouldReceive('delete')->once()->andReturn(true);
        $configDisk = Storage::disk('config');
        Storage::shouldReceive('disk')->with('config')->andReturn($configDisk);
        Storage::shouldReceive('disk')->with('visits')->andReturn($disk);
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertStatus(503);
        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
    }

    public function test_database_failure_rolls_back_visit_and_removes_photo()
    {
        log::creating(function () { throw new \RuntimeException('Simulated log failure'); });
        try {
            $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertStatus(503);
            $this->assertDatabaseCount('visitas', 0);
            $this->assertSame([], Storage::disk('visits')->allFiles());
        } finally {
            log::flushEventListeners();
        }
    }

    public function test_vehicle_delivery_switch_for_scanned_manual_and_old_clients()
    {
        foreach ([1, 0, null] as $flag) {
            $data = $this->vehicle();
            if ($flag !== null) $data['es_repartidor'] = $flag;
            $this->postJson(route('save.visits'), $data)->assertOk();
            $visit = Visit::latest('id')->first();
            $this->assertSame('vehicular', $visit->tipo_visita);
            $this->assertSame($flag === 1, $visit->es_repartidor);
        }
        $this->postJson(route('save.visits'), $this->vehicle([
            'nombreManual' => 'Visitante manual', 'tokens' => null, 'es_repartidor' => 1,
        ]))->assertOk();
        $this->assertTrue(Visit::latest('id')->first()->es_repartidor);
        $this->postJson(route('save.visits'), $this->vehicle(['placa' => '']))->assertUnprocessable()->assertJsonValidationErrors('placa');
    }

    public function test_filters_reports_exports_and_redirects()
    {
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertCreated();
        $this->postJson(route('save.visits'), $this->vehicle(['es_repartidor' => 1]))->assertOk();
        $this->postJson(route('save.visits'), $this->vehicle())->assertOk();
        $dates = ['from' => now()->toDateString(), 'to' => now()->toDateString()];
        foreach ([['tipo_visita' => 'peatonal'], ['es_repartidor' => '1'], ['tipo_visita' => 'vehicular', 'es_repartidor' => '0']] as $filter) {
            foreach ([
                route('detail.visits', $filter),
                route('table.visits', array_merge($dates, $filter)),
                route('tablebyresident.visits', array_merge($dates, ['id' => 1], $filter)),
            ] as $url) {
                $this->get($url)->assertOk()->assertViewHas('visits', fn ($visits) => $visits->count() === 1);
            }
        }
        $filter = ['tipo_visita' => 'peatonal', 'es_repartidor' => '0'];
        $this->post(route('reportbydate.visits'), array_merge($dates, $filter))
            ->assertRedirect(route('table.visits', array_merge($dates, $filter)));
        $this->post(route('reportbyresi.visits'), array_merge($dates, $filter, ['selectResidente' => 1]))
            ->assertRedirect(route('tablebyresident.visits', array_merge($dates, $filter, ['id' => 1])));
        $this->get(route('table.visits', array_merge($dates, $filter)))->assertSee('es_repartidor=0');
        $this->get(route('tablepdf.visits', array_merge($dates, $filter)))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->getJson(route('detail.visits', ['tipo_visita' => 'invalid']))->assertUnprocessable();
    }

    public function test_migration_preserves_legacy_records_and_is_not_repeated()
    {
        $this->artisan('migrate:rollback', ['--force' => true])->assertExitCode(0);
        $legacy = [
            'residente_id' => 1, 'numeroDocumento' => '12345678', 'cui' => '1234567890101', 'Placa' => 'P123ABC',
            'Primer_Nombre' => 'Anterior', 'Primer_Apellido' => 'Visita', 'Fecha_nac' => '01/01/1990',
            'Fecha_vencimiento' => '01/01/2030', 'tipoLicencia' => 'C', 'fechaingreso' => now(),
        ];
        $id = DB::table('visitas')->insertGetId($legacy);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $visit = Visit::findOrFail($id);
        $this->assertSame('Anterior', $visit->Primer_Nombre);
        $this->assertSame('vehicular', $visit->tipo_visita);
        $this->assertFalse($visit->es_repartidor);
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->assertDatabaseCount('visitas', 1);
    }

    public function test_pedestrian_routes_require_authentication()
    {
        auth()->logout();
        $this->get(route('create.pedestrian.visits'))->assertRedirect(route('login'));
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertUnauthorized();
        $this->get(route('img.visits.query', ['filename' => 'photo.png']))->assertRedirect(route('login'));
    }
}
