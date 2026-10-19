<?php

namespace Tests\Feature;

use App\Http\Livewire\VehicleVisit;
use App\Models\log;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Livewire\Livewire;

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
        Storage::fake('local');
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

    private function vehiclePhoto(): string
    {
        return 'data:image/png;base64,' . base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aX1sAAAAASUVORK5CYII='
        ));
    }

    public function test_only_vehicle_registration_loads_livewire_and_keeps_camera_setup_guard()
    {
        $this->get(route('create.visits'))->assertOk()->assertSeeLivewire('vehicle-visit')
            ->assertSee('/livewire/livewire.js', false)->assertSee('selectResidente');
        $this->get(route('create.pedestrian.visits'))->assertOk()->assertDontSee('/livewire/livewire.js', false);
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        DB::table('users')->where('id', 1)->update(['camara_id_placa' => null]);
        $this->actingAs(User::findOrFail(1))->get(route('create.visits'))->assertRedirect(route('camaras.user'));
    }

    private function livewireVehicle(bool $manual = false)
    {
        $component = Livewire::test(VehicleVisit::class)
            ->set('residente', '1')->set('placa', 'P123ABC')->set('cono', '12');
        if ($manual) return $component->set('nombreManualInput', 'VISITANTE MANUAL')->call('confirmManual');
        return $component->set('codigoLicencia', implode('|', $this->vehicle()['tokens']))->call('scanLicense');
    }

    private function uploadVehiclePhoto($component, string $role, ?int $generation = null)
    {
        return $component->set('uploads.'.($generation ?? $component->get('captureGeneration')).'.'.$role,
            UploadedFile::fake()->createWithContent($role.'.png', base64_decode(explode(',', $this->vehiclePhoto())[1])));
    }

    public function test_livewire_saves_each_photo_configuration_and_retries_the_same_registration()
    {
        foreach (range(0, 7) as $mask) {
            $flags = [$mask & 1 ? 1 : 0, $mask & 2 ? 1 : 0, $mask & 4 ? 1 : 0];
            DB::table('config')->where('id', 1)->update(array_combine(
                ['enable_fotolicencia', 'enable_fotoplaca', 'enable_fotovisitante'], $flags
            ));
            $manual = $mask % 2 === 0;
            $component = $this->livewireVehicle($manual)->set('es_repartidor', $mask % 3 === 0);
            foreach (['licencia', 'placa', 'visitante'] as $index => $role) {
                if ($flags[$index] && !($manual && $index === 0)) $this->uploadVehiclePhoto($component, $role);
            }
            $uuid = $component->get('submissionId');
            $component->call('save')->assertHasNoErrors()->assertSet('status', 'saved');
            $visit = Visit::where('submission_id', $uuid)->firstOrFail();
            $component->assertRedirect(route('detailv.visits', ['id' => $visit->id]));
            $this->assertSame(1, $visit->created_by);
            $this->assertSame('vehicular', $visit->tipo_visita);
            $this->assertSame($mask % 3 === 0, $visit->es_repartidor);
            $this->assertSame($manual ? 'VISITANTE MANUAL' : 'JUAN', $visit->Primer_Nombre);
            foreach (['path_licencia', 'path_placa', 'path_visitante'] as $index => $column) {
                $expected = (bool) $flags[$index] && !($manual && $index === 0);
                $this->assertSame($expected, (bool) $visit->$column);
                if ($expected) Storage::disk('visits')->assertExists($visit->$column);
            }
            $this->assertStringNotContainsString('data:image/', json_encode($component->payload['serverMemo']));
            $component->call('save')->assertHasNoErrors();
            $this->assertDatabaseCount('visitas', $mask + 1);
            $this->assertDatabaseCount('logs', $mask + 1);
        }
    }

    public function test_livewire_validates_on_the_server_and_reads_current_photo_settings()
    {
        $component = Livewire::test(VehicleVisit::class)->call('save')->assertHasErrors(['placa', 'cono', 'tokens', 'residente'])
            ->assertSet('status', 'editing');
        $component = $this->livewireVehicle();
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        $component->call('save')->assertHasErrors('photo2')->assertSet('status', 'editing');
        $this->uploadVehiclePhoto($component, 'placa');
        $component->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('visitas', 1);
    }

    public function test_livewire_resident_binding_renders_house_and_clears_inactive_residents()
    {
        $component = Livewire::test(VehicleVisit::class)->set('residente', '1')
            ->assertSet('houseCode', 'A-12')->assertSet('address', 'Casa A-12');
        DB::table('residentes')->where('id', 1)->update(['estado' => 0]);
        $component->set('residente', '1')->assertHasErrors('residente')->assertSet('houseCode', '')->assertSet('address', '');
        $component->call('save')->assertHasErrors('residente');
        $this->assertDatabaseCount('visitas', 0);
    }

    public function test_livewire_failed_photo_storage_does_not_register_and_can_retry()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        $component = $this->livewireVehicle();
        $this->uploadVehiclePhoto($component, 'placa');
        $disk = Storage::disk('visits');
        $failedDisk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $failedDisk->shouldReceive('put')->once()->andReturn(false);
        $failedDisk->shouldReceive('delete')->once()->andReturn(true);
        Storage::set('visits', $failedDisk);
        $component->call('save')->assertHasErrors('registro')->assertSet('status', 'editing');
        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
        Storage::set('visits', $disk);
        $component->call('save')->assertHasNoErrors()->assertSet('status', 'saved');
        $this->assertDatabaseCount('visitas', 1);
    }

    public function test_livewire_rejects_owner_and_derived_updates_and_changed_or_expired_accounts()
    {
        Livewire::test(VehicleVisit::class)->set('ownerId', 2)->assertForbidden();
        Livewire::test(VehicleVisit::class)->set('visitor.Primer_Nombre', 'ALTERADO')->assertForbidden();
        $component = Livewire::test(VehicleVisit::class);
        $other = User::factory()->create(['username' => 'other-livewire', 'status' => 1]);
        $this->actingAs($other);
        $component->call('save')->assertForbidden();
        $this->assertDatabaseCount('visitas', 0);
        $this->actingAs(User::findOrFail(1));
        $component = Livewire::test(VehicleVisit::class);
        auth()->logout();
        $component->call('recover')->assertUnauthorized();
        Livewire::test(VehicleVisit::class)->assertUnauthorized();
    }

    public function test_long_unused_license_field_is_ignored_in_livewire_legacy_endpoint_and_backup()
    {
        $tokens = $this->vehicle()['tokens'];
        $tokens[10] = str_repeat('METADATO NO PERSISTIDO ', 20);
        $component = Livewire::test(VehicleVisit::class)->set('residente', '1')->set('placa', 'P123ABC')->set('cono', '1')
            ->set('codigoLicencia', implode('|', $tokens))->call('scanLicense')->assertHasNoErrors()->assertSet('visitor.Primer_Nombre', 'JUAN');
        $this->assertStringNotContainsString('METADATO', json_encode($component->payload['serverMemo']));
        $component->call('save')->assertHasNoErrors()->assertSet('status', 'saved');
        $this->postJson(route('save.visits'), $this->vehicle(['tokens' => $tokens]))->assertOk();
        Livewire::test(VehicleVisit::class)->call('restoreDraft', [
            'version' => 1, 'user_id' => '1', 'submission_id' => (string) Str::uuid(),
            'tokens' => $tokens, 'resident' => '1', 'plate' => 'P123ABC', 'cone' => '2',
        ])->assertHasNoErrors()->assertSet('visitor.Primer_Nombre', 'JUAN')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('visitas', 3);
    }

    public function test_license_formats_and_spanish_validation_without_silent_truncation()
    {
        $parser = app(\App\Services\VehicleLicense::class);
        $tokens = $this->vehicle()['tokens'];
        $this->assertSame('JUAN', $parser->parse(implode(']', $tokens))['Primer_Nombre']);
        $prefixed = $tokens; $prefixed[0] = 'PREFIJO12345678'; array_splice($prefixed, 6, 0, ['IGNORADO']);
        $this->assertSame('12345678', $parser->parse(implode('|', $prefixed))['numeroDocumento']);
        $tokens[2] = str_repeat('N', 31);
        $component = Livewire::test(VehicleVisit::class)->set('codigoLicencia', implode('|', $tokens))->call('scanLicense')
            ->assertHasErrors('Primer_Nombre')->assertSet('visitor', [])->assertSet('modalOpen', true);
        $component->assertSee('primer nombre')->assertDontSee('tokens.');
        $this->postJson(route('save.visits'), $this->vehicle(['tokens' => $tokens]))->assertStatus(422)
            ->assertJsonValidationErrors('tokens.2')->assertJsonFragment(['primer nombre no puede superar los 30 caracteres.']);
    }

    public function test_late_upload_is_discarded_and_automatic_capture_is_server_requested()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotolicencia' => 1, 'enable_fotoplaca' => 1]);
        $component = $this->livewireVehicle()->assertDispatchedBrowserEvent('vehicle:capture');
        $generation = $component->get('captureGeneration');
        $component->set('nombreManualInput', 'MANUAL')->call('confirmManual');
        $this->uploadVehiclePhoto($component, 'licencia', $generation)->assertSet('photoVersions', []);
        $component->call('requestPhotos')->assertDispatchedBrowserEvent('vehicle:capture', [
            'generation' => $component->get('captureGeneration'), 'roles' => ['placa'],
        ]);
        $this->uploadVehiclePhoto($component, 'placa');
        $component->call('save')->assertHasNoErrors();
        $visit = Visit::firstOrFail();
        $this->assertNull($visit->path_licencia);
        Storage::disk('visits')->assertExists($visit->path_placa);
    }

    public function test_frozen_server_submission_restores_immutable_fields_and_confirms_one_visit()
    {
        $component = $this->livewireVehicle();
        $token = $component->get('draftToken');
        $uuid = $component->get('submissionId');
        $drafts = app(\App\Services\VehicleVisitDrafts::class);
        $state = $drafts->read(1, $uuid)['state'];
        $drafts->freeze(1, $uuid, $state);
        $restored = Livewire::test(VehicleVisit::class)->call('restoreDraft', [
            'version' => 2, 'user_id' => '1', 'token' => $token, 'inputs' => ['placa' => 'ALTERADO'],
        ])->assertSet('status', 'pending')->assertSet('placa', 'P123ABC');
        $restored->set('placa', 'OTRA')->assertHasErrors('registro');
        $restored->call('save')->assertHasNoErrors()->assertSet('status', 'saved');
        Livewire::test(VehicleVisit::class)->call('restoreDraft', [
            'version' => 2, 'user_id' => '1', 'token' => $token,
        ])->assertSet('status', 'saved');
        $this->assertDatabaseCount('visitas', 1);
        $this->assertDatabaseCount('logs', 1);
        $this->assertSame('P123ABC', Visit::firstOrFail()->Placa);
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
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['cui' => '123']))->assertStatus(422)->assertJsonValidationErrors('cui');
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['photo_dpi' => null]))->assertStatus(422)->assertJsonValidationErrors('photo_dpi');
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian(['Fecha_nac' => '1990-02-30']))->assertStatus(422)->assertJsonValidationErrors('Fecha_nac');
        DB::table('residentes')->where('id', 1)->update(['estado' => 0]);
        $this->postJson(route('save.pedestrian.visits'), $this->pedestrian())->assertStatus(422)->assertJsonValidationErrors('residente');
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
        $this->postJson(route('save.visits'), $this->vehicle(['placa' => '']))->assertStatus(422)->assertJsonValidationErrors('placa');
    }

    public function test_vehicle_submission_id_returns_one_visit_for_repeated_requests()
    {
        $submissionId = (string) Str::uuid();
        $payload = $this->vehicle(['submission_id' => $submissionId]);

        $first = $this->postJson(route('save.visits'), $payload)->assertOk()
            ->assertJsonStructure(['id', 'redirect_url']);
        $retry = $this->postJson(route('save.visits'), array_merge($payload, ['placa' => 'P999XYZ']))->assertOk()
            ->assertJsonStructure(['id', 'redirect_url']);

        $this->assertSame($first->json('id'), $retry->json('id'));
        $this->assertSame('P123ABC', Visit::findOrFail($first->json('id'))->Placa);
        $this->assertDatabaseCount('visitas', 1);
        $this->assertDatabaseCount('logs', 1);

        $newVisit = $this->postJson(route('save.visits'), $this->vehicle(['submission_id' => (string) Str::uuid()]))
            ->assertOk()->json('id');

        $this->assertNotSame($first->json('id'), $newVisit);
        $this->assertDatabaseCount('visitas', 2);
        $this->assertDatabaseCount('logs', 2);

        $otherUser = User::factory()->create(['username' => 'vehicle-retry-agent', 'status' => 1]);
        $this->actingAs($otherUser);
        $this->postJson(route('save.visits'), $payload)->assertStatus(409);
        $this->assertDatabaseCount('visitas', 2);
        $this->assertDatabaseCount('logs', 2);
    }

    public function test_vehicle_missing_submission_column_returns_a_recoverable_error()
    {
        \Illuminate\Support\Facades\Schema::table('visitas', function ($table) {
            $table->dropUnique(['submission_id']);
            $table->dropColumn('submission_id');
        });
        $this->postJson(route('save.visits'), $this->vehicle(['submission_id' => (string) Str::uuid()]))
            ->assertStatus(503)->assertJsonPath('outcome', 'unknown')
            ->assertJsonMissing(['exception' => 'Illuminate\\Database\\QueryException']);
        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
    }

    public function test_vehicle_commit_acknowledgement_failure_keeps_the_committed_photos()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        $payload = $this->vehicle(['submission_id' => (string) Str::uuid(), 'photo2' => $this->vehiclePhoto()]);
        $events = DB::connection()->getEventDispatcher();
        $events->listen(\Illuminate\Database\Events\TransactionCommitted::class, function () {
            throw new \RuntimeException('Simulated lost commit acknowledgement');
        });
        try {
            $first = $this->postJson(route('save.visits'), $payload)->assertOk();
        } finally {
            $events->forget(\Illuminate\Database\Events\TransactionCommitted::class);
        }
        $visit = Visit::findOrFail($first->json('id'));
        Storage::disk('visits')->assertExists($visit->path_placa);
        $this->postJson(route('save.visits'), $payload)->assertOk()->assertJsonPath('id', $visit->id);
        $this->assertDatabaseCount('visitas', 1);
        $this->assertDatabaseCount('logs', 1);
        $this->assertCount(1, Storage::disk('visits')->allFiles());
    }

    public function test_vehicle_unverifiable_commit_retains_files_until_a_retry_can_confirm_it()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        $payload = $this->vehicle(['submission_id' => (string) Str::uuid(), 'photo2' => $this->vehiclePhoto()]);
        $events = DB::connection()->getEventDispatcher();
        $events->listen(\Illuminate\Database\Events\TransactionCommitted::class, function () {
            Visit::retrieved(function () { throw new \RuntimeException('Verification connection unavailable'); });
            throw new \RuntimeException('Simulated lost commit acknowledgement');
        });
        try {
            $this->postJson(route('save.visits'), $payload)->assertStatus(503)->assertJsonPath('outcome', 'unknown');
        } finally {
            $events->forget(\Illuminate\Database\Events\TransactionCommitted::class);
            Visit::flushEventListeners();
        }
        $visit = Visit::where('submission_id', $payload['submission_id'])->firstOrFail();
        Storage::disk('visits')->assertExists($visit->path_placa);
        $this->postJson(route('save.visits'), $payload)->assertOk()->assertJsonPath('id', $visit->id);
        $this->assertDatabaseCount('visitas', 1);
        $this->assertDatabaseCount('logs', 1);
    }

    public function test_vehicle_registration_keeps_the_previous_response_for_old_forms()
    {
        $response = $this->postJson(route('save.visits'), $this->vehicle())->assertOk();

        $this->assertIsString($response->json());
        $this->assertSame('vehicular', json_decode($response->json(), true)['tipo_visita']);
        $this->assertDatabaseCount('visitas', 1);
    }

    public function test_vehicle_registration_requires_an_active_resident_and_schema_safe_values()
    {
        DB::table('residentes')->where('id', 1)->update(['estado' => 0]);
        $this->postJson(route('save.visits'), $this->vehicle())
            ->assertStatus(422)->assertJsonValidationErrors('residente');

        DB::table('residentes')->where('id', 1)->update(['estado' => 1]);
        $this->postJson(route('save.visits'), $this->vehicle(['placa' => '123456789']))
            ->assertStatus(422)->assertJsonValidationErrors('placa');
        $this->postJson(route('save.visits'), $this->vehicle(['tokens' => ['123456789', 'C', 'JUAN', '', 'PEREZ', '', '01/01/1990', '01/01/2030', '1234567890101', '', '']]))
            ->assertStatus(422)->assertJsonValidationErrors('tokens.0');

        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
    }

    public function test_session_endpoint_returns_the_current_authenticated_user_and_fresh_csrf_token()
    {
        $response = $this->getJson(route('session.visits'))
            ->assertOk()
            ->assertJsonStructure(['user_id', 'csrf_token']);
        foreach (['private', 'no-store', 'max-age'] as $directive) {
            $this->assertTrue($response->headers->hasCacheControlDirective($directive));
        }

        $this->assertSame(1, $response->json('user_id'));
        $this->assertNotEmpty($response->json('csrf_token'));
    }

    public function test_vehicle_photo_storage_failure_leaves_no_visit_or_log()
    {
        DB::table('config')->where('id', 1)->update([
            'enable_fotoplaca' => 1,
            'enable_fotovisitante' => 1,
        ]);
        $fakeDisk = Storage::disk('visits');
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $writes = 0;
        $disk->shouldReceive('put')->twice()->andReturnUsing(function ($path, $contents) use (&$writes, $fakeDisk) {
            $writes++;

            return $writes === 1 ? $fakeDisk->put($path, $contents) : false;
        });
        $disk->shouldReceive('delete')->twice()->andReturnUsing(function ($path) use ($fakeDisk) {
            return $fakeDisk->delete($path);
        });
        $configDisk = Storage::disk('config');
        Storage::shouldReceive('disk')->with('config')->andReturn($configDisk);
        Storage::shouldReceive('disk')->with('visits')->andReturn($disk);

        $this->postJson(route('save.visits'), $this->vehicle([
            'photo2' => $this->vehiclePhoto(),
            'photo3' => $this->vehiclePhoto(),
        ]))
            ->assertStatus(503)->assertJsonPath('outcome', 'not_saved');
        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
        $this->assertSame([], $fakeDisk->allFiles());
    }

    public function test_vehicle_s3_failure_uses_bounded_sdk_options_without_internal_retries()
    {
        $values = [
            'IMAGE_STORAGE_DRIVER' => 's3', 'GARITA_FOLDER' => 'synthetic-test',
            'AWS_ACCESS_KEY_ID' => 'synthetic', 'AWS_SECRET_ACCESS_KEY' => 'synthetic',
            'AWS_DEFAULT_REGION' => 'us-east-1', 'AWS_BUCKET' => 'synthetic-test',
        ];
        $previous = [];
        foreach ($values as $name => $value) {
            $previous[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        try {
            $settings = require config_path('filesystems.php');
        } finally {
            foreach ($previous as $name => [$env, $server]) {
                if ($env === null) unset($_ENV[$name]); else $_ENV[$name] = $env;
                if ($server === null) unset($_SERVER[$name]); else $_SERVER[$name] = $server;
            }
        }
        $puts = 0;
        $options = $settings['disks']['visits'];
        $options['http_handler'] = function ($request, array $http) use (&$puts) {
            $this->assertSame(3, $http['connect_timeout']);
            $this->assertSame(10, $http['timeout']);
            if ($request->getMethod() === 'PUT') {
                $puts++;
                return new \GuzzleHttp\Promise\RejectedPromise(new \GuzzleHttp\Exception\ConnectException('Simulated S3 outage', $request));
            }
            return new \GuzzleHttp\Promise\FulfilledPromise(new \GuzzleHttp\Psr7\Response(200));
        };
        Storage::set('visits', Storage::createS3Driver($options));
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        $this->postJson(route('save.visits'), $this->vehicle([
            'submission_id' => (string) Str::uuid(), 'photo2' => $this->vehiclePhoto(),
        ]))->assertStatus(503)->assertJsonPath('outcome', 'not_saved');
        $this->assertSame(1, $puts);
        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
    }

    public function test_vehicle_registration_rejects_malformed_photo_data_before_storing()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);

        $this->postJson(route('save.visits'), $this->vehicle(['photo2' => 'data:image/png;base64,bm90LWltYWdl']))
            ->assertStatus(422)->assertJsonValidationErrors('photo2');

        $this->assertDatabaseCount('visitas', 0);
        $this->assertDatabaseCount('logs', 0);
        $this->assertSame([], Storage::disk('visits')->allFiles());
    }

    public function test_vehicle_database_failure_rolls_back_the_log_record_and_uploaded_photos()
    {
        DB::table('config')->where('id', 1)->update(['enable_fotoplaca' => 1]);
        log::creating(function () { throw new \RuntimeException('Simulated log failure'); });
        try {
            $this->postJson(route('save.visits'), $this->vehicle(['photo2' => $this->vehiclePhoto()]))
                ->assertStatus(503)->assertJsonPath('outcome', 'not_saved');
            $this->assertDatabaseCount('visitas', 0);
            $this->assertDatabaseCount('logs', 0);
            $this->assertSame([], Storage::disk('visits')->allFiles());
        } finally {
            log::flushEventListeners();
        }
    }

    public function test_manual_vehicle_visit_can_skip_license_photo_but_keeps_other_photos_required()
    {
        DB::table('config')->where('id', 1)->update([
            'enable_fotolicencia' => 1,
            'enable_fotoplaca' => 1,
            'enable_fotovisitante' => 1,
        ]);

        $manualVisit = $this->vehicle([
            'tokens' => null,
            'nombreManual' => '  Visitante sin licencia  ',
        ]);

        $invalidManualVisit = $this->postJson(route('save.visits'), $manualVisit);
        $invalidManualVisit->assertStatus(422)->assertJsonValidationErrors(['photo2', 'photo3']);
        $this->assertArrayNotHasKey('photo1', $invalidManualVisit->json('errors'));

        $manualVisit['photo2'] = $this->vehiclePhoto();
        $manualVisit['photo3'] = $this->vehiclePhoto();

        $this->postJson(route('save.visits'), $manualVisit)->assertOk();
        $visit = Visit::latest('id')->firstOrFail();
        $this->assertSame('VISITANTE SIN LICENCIA', $visit->Primer_Nombre);
        $this->assertNull($visit->path_licencia);
        Storage::disk('visits')->assertExists($visit->path_placa);
        Storage::disk('visits')->assertExists($visit->path_visitante);

        $scannedVisit = $this->vehicle([
            'photo2' => $manualVisit['photo2'],
            'photo3' => $manualVisit['photo3'],
        ]);
        $this->postJson(route('save.visits'), $scannedVisit)
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo1');
    }

    public function test_vehicle_photo_configurations_for_manual_and_scanned_visitors()
    {
        for ($mask = 0; $mask < 8; $mask++) {
            DB::table('config')->where('id', 1)->update([
                'enable_fotolicencia' => ($mask & 1) ? 1 : 0,
                'enable_fotoplaca' => ($mask & 2) ? 1 : 0,
                'enable_fotovisitante' => ($mask & 4) ? 1 : 0,
            ]);
            foreach ([false, true] as $manual) {
                $payload = $this->vehicle([
                    'submission_id' => (string) Str::uuid(),
                    'photo1' => $this->vehiclePhoto(), 'photo2' => $this->vehiclePhoto(), 'photo3' => $this->vehiclePhoto(),
                ]);
                if ($manual) { $payload['nombreManual'] = 'MANUAL'; $payload['tokens'] = null; }
                $response = $this->postJson(route('save.visits'), $payload)->assertOk();
                $visit = Visit::findOrFail($response->json('id'));
                foreach (['path_licencia', 'path_placa', 'path_visitante'] as $index => $column) {
                    $expected = (bool) ($mask & (1 << $index)) && !($manual && $index === 0);
                    $this->assertSame($expected, (bool) $visit->$column);
                    if ($expected) Storage::disk('visits')->assertExists($visit->$column);
                }
            }
        }
        $this->assertDatabaseCount('visitas', 16);
        $this->assertDatabaseCount('logs', 16);
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
        $this->getJson(route('detail.visits', ['tipo_visita' => 'invalid']))->assertStatus(422);
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
