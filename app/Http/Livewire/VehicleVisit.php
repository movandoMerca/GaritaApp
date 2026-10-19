<?php

namespace App\Http\Livewire;

use App\Models\config;
use App\Models\Resident;
use App\Models\Visit;
use App\Services\VehicleLicense;
use App\Services\VehicleVisitDrafts;
use App\Services\VehicleVisitRegistration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class VehicleVisit extends Component
{
    use WithFileUploads;

    public $ownerId;
    public $submissionId;
    public $residente = '';
    public $placa = '';
    public $cono = '';
    public $es_repartidor = false;
    public $codigoLicencia = '';
    public $nombreManualInput = '';
    public $nombreManual = '';
    public $visitor = [];
    public $houseCode = '';
    public $address = '';
    public $scannedAt = '';
    public $modalOpen = false;
    public $confirmCancel = false;
    public $captureGeneration = 0;
    public $photoVersions = [];
    public $capturePending = [];
    public $uploads = [];
    public $status = 'editing';
    public $notice = '';
    public $draftToken = '';

    private const INPUTS = ['residente', 'placa', 'cono', 'es_repartidor', 'codigoLicencia', 'nombreManualInput'];
    private const STATE = ['residente', 'placa', 'cono', 'es_repartidor', 'codigoLicencia', 'nombreManualInput',
        'nombreManual', 'visitor', 'scannedAt', 'captureGeneration'];

    public function mount()
    {
        abort_unless(auth()->check(), 401);
        $this->ownerId = (int) auth()->id();
        $this->submissionId = (string) Str::uuid();
    }

    public function hydrate()
    {
        $this->assertOwner();
        $draft = $this->drafts()->read($this->ownerId, $this->submissionId);
        if (!empty($draft['frozen'])) {
            $this->applyState($draft['frozen']['state']);
            $this->status = 'pending';
        }
    }

    public function dehydrate()
    {
        // Opaque proof for local backup; the browser never decides which visitor
        // or pending submission may be restored for the authenticated account.
        $this->draftToken = Crypt::encryptString(json_encode([
            'owner' => $this->ownerId, 'uuid' => $this->submissionId, 'state' => $this->state(),
        ], JSON_THROW_ON_ERROR));
    }

    public function updating($name)
    {
        if (strpos($name, 'uploads.') === 0) {
            abort_unless(preg_match('/\Auploads\.\d+\.(licencia|placa|visitante)\z/', $name), 403);
            return;
        }
        abort_unless(in_array($name, self::INPUTS, true), 403);
        if ($this->status !== 'editing') {
            throw ValidationException::withMessages(['registro' => 'Verifique el envío pendiente antes de modificar los datos.']);
        }
    }

    public function updatedResidente()
    {
        $this->loadResident();
        $this->persistState();
    }

    public function scanLicense()
    {
        $this->editable();
        $raw = $this->codigoLicencia;
        $this->resetValidation();
        $this->notice = '';
        $this->visitor = [];
        $this->nombreManual = '';
        $this->nombreManualInput = '';
        $this->scannedAt = '';
        $this->codigoLicencia = '';
        $this->newCapture();
        try {
            $this->visitor = app(VehicleLicense::class)->parse(is_string($raw) ? $raw : '');
            $this->scannedAt = now()->format('d/m/Y, h:i:s a');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            $this->modalOpen = true;
            $this->persistState();
            return;
        }
        $this->modalOpen = false;
        $this->persistState();
        $this->dispatchCapture();
    }

    public function openManual()
    {
        $this->editable();
        $this->modalOpen = true;
    }

    public function closeManual()
    {
        $this->modalOpen = false;
    }

    public function confirmManual()
    {
        $this->editable();
        $this->resetValidation();
        $validated = Validator::make(['nombreManualInput' => $this->nombreManualInput], [
            'nombreManualInput' => 'required|string|max:30',
        ], [
            'nombreManualInput.required' => 'Ingrese el nombre del visitante para continuar.',
            'nombreManualInput.string' => 'El nombre del visitante no es válido.',
            'nombreManualInput.max' => 'El nombre no puede superar los 30 caracteres.',
        ])->validate();
        $this->nombreManual = trim($validated['nombreManualInput']);
        $this->nombreManualInput = $this->nombreManual;
        $this->visitor = [];
        $this->codigoLicencia = '';
        $this->scannedAt = '';
        $this->modalOpen = false;
        $this->notice = '';
        $this->newCapture();
        $this->persistState();
    }

    public function requestPhotos()
    {
        $this->editable();
        $this->newCapture();
        $this->persistState();
        $this->dispatchCapture();
    }

    protected function dispatchCapture()
    {
        $roles = $this->photoRoles();
        $this->capturePending = $roles;
        $this->dispatchBrowserEvent('vehicle:capture', ['generation' => $this->captureGeneration, 'roles' => $roles]);
    }

    public function updatedUploads($file, $key)
    {
        [$generation, $role] = explode('.', $key, 2);
        if (!$file instanceof TemporaryUploadedFile) return;
        if ((int) $generation !== $this->captureGeneration || !in_array($role, $this->photoRoles(), true)
            || $this->status === 'pending' || $this->status === 'saved') {
            $file->delete();
            Arr::forget($this->uploads, $key);
            return;
        }
        try {
            $bytes = $file->get();
            $info = @getimagesizefromstring($bytes);
            if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg'], true)) {
                throw ValidationException::withMessages(['fotos' => 'La fotografía no es válida. Tómela nuevamente.']);
            }
            $prefix = $info['mime'] === 'image/png' ? 'data:image/png;base64,' : 'data:image/jpeg;base64,';
            if (strlen($prefix) + 4 * (int) ceil(strlen($bytes) / 3) > 6000000) {
                throw ValidationException::withMessages(['fotos' => 'La fotografía es demasiado grande. Tómela nuevamente.']);
            }
            $draft = $this->drafts()->photo($this->ownerId, $this->submissionId, (int) $generation, $role, $bytes,
                $info['mime'] === 'image/png' ? 'png' : 'jpg');
            if (($draft['photos'][$role]['generation'] ?? null) !== (int) $generation) return;
            $this->photoVersions[$role] = (int) $generation;
            $this->resetValidation('fotos');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('fotos', 'No se pudo conservar la foto. Vuelva a tomarla antes de guardar.');
        } finally {
            $this->capturePending = array_values(array_diff($this->capturePending, [$role]));
            Arr::forget($this->uploads, $key);
            $file->delete();
        }
        if ($this->status === 'restoring' && !$this->capturePending) {
            $this->drafts()->freeze($this->ownerId, $this->submissionId, $this->state());
            $this->status = 'pending';
        }
    }

    public function uploadErrored($name, $errorsInJson, $isMultiple)
    {
        $this->emit('upload:errored', $name)->self();
        if (preg_match('/\Auploads\.(\d+)\.(licencia|placa|visitante)\z/', $name, $parts)) {
            $this->cameraFailed((int) $parts[1], [$parts[2]]);
        }
    }

    public function cameraFailed($generation, array $roles)
    {
        if ((int) $generation !== $this->captureGeneration) return;
        $roles = array_values(array_intersect($roles, ['licencia', 'placa', 'visitante']));
        if (!$roles) return;
        $this->capturePending = array_values(array_diff($this->capturePending, $roles));
        $this->addError('fotos', 'No se pudo capturar '.implode(', ', $roles).'. Revise las cámaras e intente nuevamente.');
        if ($this->status === 'restoring' && !$this->capturePending) {
            $this->drafts()->freeze($this->ownerId, $this->submissionId, $this->state());
            $this->status = 'pending';
        }
    }

    public function save()
    {
        $this->assertOwner();
        $this->resetValidation();
        $this->notice = '';
        if ($this->finishExisting()) return;
        $draft = $this->drafts()->read($this->ownerId, $this->submissionId);
        $retry = !empty($draft['frozen']);
        if ($retry) $this->applyState($draft['frozen']['state']);
        try {
            $payload = $this->payload($retry ? $draft['frozen']['photos'] : ($draft['photos'] ?? []));
            // Validate before freezing so a rejected first submission stays editable.
            app(VehicleVisitRegistration::class)->validate($payload);
            $draft = $this->drafts()->freeze($this->ownerId, $this->submissionId, $this->state());
            $this->status = 'pending';
            $this->applyState($draft['frozen']['state']);
            $result = app(VehicleVisitRegistration::class)->register($this->payload($draft['frozen']['photos']), $this->ownerId);
            if ($result['status'] === 200 && isset($result['body']['id'])) {
                $this->finish($result['body']['id']);
                return;
            }
            if (!$retry && ($result['body']['outcome'] ?? null) === 'not_saved') {
                $this->drafts()->thaw($this->ownerId, $this->submissionId);
                $this->status = 'editing';
            }
            $this->addError('registro', $result['body']['message'] ?? 'No se pudo confirmar el registro. Verifique el mismo envío.');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
            if ($retry) $this->status = 'pending';
            elseif ($this->status === 'pending') {
                $this->drafts()->thaw($this->ownerId, $this->submissionId);
                $this->status = 'editing';
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->status = 'pending';
            $this->addError('registro', 'No se pudo confirmar el guardado. Sus datos se conservaron; verifique el mismo envío.');
        }
    }

    public function recover()
    {
        $this->assertOwner();
        $this->resetValidation();
        if ($this->finishExisting()) return;
        $draft = $this->drafts()->read($this->ownerId, $this->submissionId);
        if (!empty($draft['frozen'])) {
            $this->applyState($draft['frozen']['state']);
            $this->status = 'pending';
            $this->notice = 'El envío sigue pendiente. Verifique o reintente el mismo guardado para evitar duplicados.';
        } else {
            if (isset($draft['state'])) $this->applyState($draft['state']);
            $this->status = 'editing';
        }
        $this->loadPhotoVersions($draft);
        $this->loadResident();
        $this->dispatchBrowserEvent('vehicle:recovered');
    }

    public function restoreDraft(array $backup)
    {
        $this->assertOwner();
        abort_unless((string) ($backup['user_id'] ?? '') === (string) $this->ownerId, 403);
        $old = ($backup['version'] ?? null) === 1;
        $proof = null;
        if (!$old) {
            try { $proof = json_decode(Crypt::decryptString($backup['token'] ?? ''), true, 512, JSON_THROW_ON_ERROR); }
            catch (Throwable $exception) { $this->addError('registro', 'No se pudo verificar el borrador de esta pestaña.'); return; }
            abort_unless(($proof['owner'] ?? null) === $this->ownerId, 403);
        }
        $uuid = $old ? ($backup['submission_id'] ?? '') : ($proof['uuid'] ?? '');
        abort_unless(is_string($uuid) && Str::isUuid($uuid), 422);
        $this->submissionId = $uuid;
        if ($this->finishExisting()) return;
        $draft = $this->drafts()->read($this->ownerId, $uuid);
        if (!empty($draft['frozen'])) { $this->recover(); return; }
        $this->resetValidation();
        $pending = $old && ($backup['attempt_sent'] ?? false) && is_array($backup['pending_submission'] ?? null);
        $state = $old ? $this->legacyState($backup, $pending) : ($draft['state'] ?? $proof['state'] ?? []);
        $this->applyState($state);
        if (!$pending) {
            foreach ($backup['inputs'] ?? [] as $field => $value) {
                if (in_array($field, self::INPUTS, true) && (is_scalar($value) || $value === null)) $this->$field = $value;
            }
        }
        $this->status = $pending ? 'restoring' : 'editing';
        $this->captureGeneration++;
        $this->loadResident();
        // Preserve a server-side photo when the browser only has field edits.
        $this->drafts()->edit($this->ownerId, $uuid, $this->state());
        $this->loadPhotoVersions($draft);
        $roles = array_values(array_diff($this->photoRoles(), array_keys($this->photoVersions)));
        if (!$pending) {
            $localPhotos = is_array($backup['photos'] ?? null) ? $backup['photos'] : [];
            $fields = ['licencia' => 'photo1', 'placa' => 'photo2', 'visitante' => 'photo3'];
            $roles = array_values(array_filter($roles, function ($role) use ($localPhotos, $fields) {
                return !empty($localPhotos[$role]) || !empty($localPhotos[$fields[$role]]);
            }));
        }
        $this->capturePending = $roles;
        $this->dispatchBrowserEvent('vehicle:restore-photos', [
            'generation' => $this->captureGeneration, 'roles' => $roles,
            'source' => $pending ? 'pending_submission' : 'photos',
        ]);
        if ($pending && !$roles) {
            $this->drafts()->freeze($this->ownerId, $uuid, $this->state());
            $this->status = 'pending';
        }
        $this->notice = $pending ? 'Se recuperó un envío pendiente; verifique el mismo guardado.' : 'Se recuperó el borrador de esta pestaña.';
        $this->dispatchBrowserEvent('vehicle:recovered');
    }

    public function backupUnavailable()
    {
        $this->addError('respaldo', 'El navegador no permitió guardar un borrador. Mantenga esta pestaña abierta para no perder los datos.');
    }

    public function cancel()
    {
        if ($this->status !== 'editing' && !$this->confirmCancel) { $this->confirmCancel = true; return; }
        if ($this->status === 'editing') {
            $this->drafts()->discard($this->ownerId, $this->submissionId);
            $this->dispatchBrowserEvent('vehicle:clear-backup');
        }
        $this->redirectRoute('home');
    }

    public function keepEditing()
    {
        $this->confirmCancel = false;
    }

    public function photoUrl($role)
    {
        if (!isset($this->photoVersions[$role])) return null;
        return URL::temporarySignedRoute('photo.draft.visits', now()->addMinutes(30), [
            'uuid' => $this->submissionId, 'generation' => $this->photoVersions[$role], 'role' => $role,
        ]);
    }

    public function render()
    {
        $this->assertOwner();
        $names = $this->nombreManual !== '' ? [$this->nombreManual, '', '', ''] : array_map(function ($field) {
            return $this->visitor[$field] ?? '';
        }, ['Primer_Nombre', 'Segundo_Nombre', 'Primer_Apellido', 'Segundo_Apellido']);
        return view('livewire.vehicle-visit', [
            'config' => config::find(1), 'residents' => Resident::where('estado', '1')->get(['id', 'accesotel']),
            'visitorNames' => $names,
            'initials' => mb_strtoupper(mb_substr($names[0], 0, 1).($this->nombreManual === '' ? mb_substr($names[2], 0, 1) : '')),
        ]);
    }

    protected function assertOwner()
    {
        abort_unless(auth()->check(), 401);
        abort_unless((int) auth()->id() === (int) $this->ownerId, 403);
    }

    protected function editable()
    {
        $this->assertOwner();
        abort_unless($this->status === 'editing', 409);
    }

    protected function drafts(): VehicleVisitDrafts { return app(VehicleVisitDrafts::class); }
    protected function state(): array { return $this->only(self::STATE); }
    protected function persistState() { $this->drafts()->edit($this->ownerId, $this->submissionId, $this->state()); }
    protected function applyState(array $state) { $this->fill(array_intersect_key($state, array_flip(self::STATE))); }

    protected function newCapture()
    {
        $this->captureGeneration++;
        $this->photoVersions = [];
        $this->capturePending = [];
        $this->uploads = [];
        $this->drafts()->resetPhotos($this->ownerId, $this->submissionId, $this->state());
        $this->dispatchBrowserEvent('vehicle:reset-cameras', ['generation' => $this->captureGeneration]);
    }

    protected function photoRoles(): array
    {
        $config = config::find(1);
        return array_keys(array_filter([
            'licencia' => ($config->enable_fotolicencia ?? 0) == 1 && $this->nombreManual === '',
            'placa' => ($config->enable_fotoplaca ?? 1) == 1,
            'visitante' => ($config->enable_fotovisitante ?? 0) == 1,
        ]));
    }

    protected function loadResident()
    {
        $this->houseCode = '';
        $this->address = '';
        $this->resetValidation('residente');
        if (!$this->residente) return;
        $resident = Resident::where('estado', '1')->find($this->residente);
        if (!$resident) { $this->addError('residente', 'Seleccione una casa activa.'); return; }
        $this->houseCode = $resident->Codigo ?? '';
        $this->address = $resident->Direccion ?? '';
    }

    protected function payload(array $photos): array
    {
        $payload = [
            'submission_id' => $this->submissionId, 'residente' => $this->residente,
            'placa' => is_string($this->placa) ? trim($this->placa) : $this->placa,
            'cono' => is_string($this->cono) ? trim($this->cono) : $this->cono,
            'es_repartidor' => $this->es_repartidor, 'nombreManual' => $this->nombreManual,
            'tokens' => $this->visitor ? app(VehicleLicense::class)->tokens($this->visitor) : null,
        ];
        foreach (['licencia' => 'photo1', 'placa' => 'photo2', 'visitante' => 'photo3'] as $role => $field) {
            if (!isset($photos[$role])) continue;
            $bytes = $this->drafts()->bytes($this->ownerId, $this->submissionId, $photos[$role]);
            $mime = @getimagesizefromstring($bytes)['mime'];
            $payload[$field] = 'data:'.$mime.';base64,'.base64_encode($bytes);
        }
        return $payload;
    }

    protected function loadPhotoVersions(array $draft)
    {
        $this->photoVersions = [];
        foreach ($draft['photos'] ?? [] as $role => $photo) {
            if (Storage::disk('local')->exists($photo['path'])) $this->photoVersions[$role] = $photo['generation'];
        }
    }

    protected function legacyState(array $backup, bool $pending): array
    {
        $data = $pending ? $backup['pending_submission'] : [
            'residente' => $backup['resident'] ?? '', 'placa' => $backup['plate'] ?? '', 'cono' => $backup['cone'] ?? '',
            'es_repartidor' => $backup['delivery'] ?? false, 'tokens' => $backup['tokens'] ?? [],
            'nombreManual' => $backup['manual_name'] ?? '',
        ];
        $state = array_intersect_key($data, array_flip(self::INPUTS));
        $state['nombreManual'] = is_string($data['nombreManual'] ?? null) ? trim($data['nombreManual']) : '';
        $state['nombreManualInput'] = $pending ? $state['nombreManual'] : ($backup['manual_name_input'] ?? $state['nombreManual']);
        $state['visitor'] = [];
        if ($state['nombreManual'] === '' && !empty($data['tokens']) && is_array($data['tokens'])) {
            try { $state['visitor'] = app(VehicleLicense::class)->fromTokens($data['tokens']); }
            catch (ValidationException $exception) { $this->setErrorBag($exception->validator->errors()); }
        }
        return $state;
    }

    protected function finishExisting(): bool
    {
        $visit = Visit::where('submission_id', $this->submissionId)->first();
        if (!$visit) return false;
        abort_unless((int) $visit->created_by === $this->ownerId, 403);
        $this->finish($visit->id);
        return true;
    }

    protected function finish($id)
    {
        $this->status = 'saved';
        try { $this->drafts()->discard($this->ownerId, $this->submissionId); } catch (Throwable $exception) { report($exception); }
        $this->dispatchBrowserEvent('vehicle:clear-backup');
        $this->redirectRoute('detailv.visits', ['id' => $id]);
    }
}
