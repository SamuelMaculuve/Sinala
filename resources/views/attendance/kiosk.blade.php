@php
    $attendanceMap = $event->attendanceRecords->mapWithKeys(fn ($record) => [
        $record->participant_id.'-'.$record->event_day_id.'-'.$record->type => [
            'recorded_at' => $record->recorded_at->format('d/m/Y H:i'),
            'signature_url' => $record->signature ? route('attendance.signature', $record) : null,
        ],
    ])->all();
@endphp
<!doctype html>
<html lang="pt-MZ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assinar — {{ $event->name }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-white">
<main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-10">
    <header class="flex flex-col gap-4 rounded-[28px] border border-stone-800 bg-stone-900/80 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
        <div>
            <p class="text-xs font-black tracking-[.22em] text-orange-400">MODO DE ASSINATURA</p>
            <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ $event->name }}</h1>
            <p class="mt-2 text-sm text-stone-400 sm:text-base">{{ $event->location }} · {{ $event->starts_on->format('d/m/Y') }}</p>
        </div>
        <a class="btn-secondary bg-white text-stone-950" href="{{ route('events.show',$event) }}">Sair</a>
    </header>

    @if(session('success'))
        <div class="mt-5 rounded-3xl border border-emerald-300 bg-emerald-100 px-5 py-4 text-sm font-bold text-emerald-950 shadow-sm sm:text-base">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('attendance'))
        <div class="mt-5 rounded-3xl border border-amber-300 bg-amber-100 px-5 py-4 text-sm font-bold text-amber-950 shadow-sm sm:text-base">
            {{ $errors->first('attendance') }}
        </div>
    @endif

    @if($errors->has('signature'))
        <div class="mt-5 rounded-3xl border border-red-300 bg-red-100 px-5 py-4 text-sm font-bold text-red-950 shadow-sm sm:text-base">
            {{ $errors->first('signature') }}
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
        <section class="rounded-[32px] border border-stone-800 bg-stone-900/85 p-4 sm:p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-black tracking-[.2em] text-orange-400">RECOLHA</p>
                    <h2 class="mt-2 text-2xl font-bold">Assinar presença</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-400">Selecione participante, dia e tipo. O sistema mostra imediatamente se esta presença já existe e muda para modo de actualização sem obrigar uma tentativa falhada.</p>
                </div>
                <div id="attendance-status-chip" class="inline-flex items-center rounded-full border border-stone-700 bg-stone-800 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-stone-300">
                    Aguardando selecção
                </div>
            </div>

            <form method="post" action="{{ route('attendance.store',$event) }}" id="signature-form" class="mt-6">
                @csrf
                <input type="hidden" name="replace_existing" id="replace-existing" value="{{ session('attendance_can_update') ? '1' : '0' }}">

                <div class="grid gap-4 md:grid-cols-3">
                    <label class="field dark">
                        Participante
                        <select name="participant_id" id="participant-id" required>
                            <option value="">Procure o seu nome</option>
                            @foreach($event->participants as $participant)
                                <option value="{{ $participant->id }}" @selected((string) $participant->id === old('participant_id'))>{{ $participant->full_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field dark">
                        Dia
                        <select name="event_day_id" id="event-day-id" required>
                            @foreach($event->days as $day)
                                <option value="{{ $day->id }}" @selected((string) $day->id === old('event_day_id'))>{{ $day->date->format('d/m/Y') }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field dark">
                        Tipo
                        <select name="type" id="attendance-type">
                            <option value="check_in" @selected(old('type') === 'check_in')>Entrada</option>
                            @if($event->requires_check_out)
                                <option value="check_out" @selected(old('type') === 'check_out')>Saída</option>
                            @endif
                        </select>
                    </label>
                </div>

                <div id="attendance-state" class="mt-5 rounded-[28px] border border-stone-700 bg-stone-800/80 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <strong id="attendance-state-title" class="block text-lg font-bold text-white">Selecione participante, dia e tipo.</strong>
                            <p id="attendance-state-copy" class="mt-2 text-sm leading-6 text-stone-300">O sistema vai indicar se esta presença é nova ou se já foi registada.</p>
                        </div>
                        <div id="attendance-state-actions" class="hidden shrink-0">
                            <a id="attendance-preview-link" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-950 transition hover:bg-stone-100" href="#" target="_blank" rel="noopener">
                                Ver assinatura actual
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-[32px] bg-stone-100 p-3 sm:p-4">
                    <canvas id="signature" class="h-[40vh] min-h-[280px] w-full touch-none rounded-[24px] bg-white shadow-inner" aria-label="Área para desenhar assinatura"></canvas>
                </div>

                <input type="hidden" name="signature" id="signature-data">

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <button type="button" id="clear-signature" class="btn-secondary bg-white text-stone-950 sm:min-w-40">Limpar assinatura</button>
                    <button id="submit-attendance" class="btn-primary flex-1 text-base sm:text-lg">Confirmar presença</button>
                    <button id="update-attendance" type="submit" class="hidden min-h-12 items-center justify-center rounded-xl bg-amber-300 px-5 font-bold text-amber-950 transition hover:bg-amber-400 focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-amber-300 sm:min-w-56">Actualizar presença</button>
                </div>
            </form>
        </section>

        <aside class="rounded-[32px] border border-stone-800 bg-stone-900/85 p-4 sm:p-6">
            <p class="text-xs font-black tracking-[.2em] text-orange-400">PAINEL AO VIVO</p>
            <h2 class="mt-2 text-2xl font-bold">Estado dos participantes</h2>
            <p class="mt-2 text-sm leading-6 text-stone-400">Para o dia e tipo seleccionados, veja rapidamente quem já assinou e quem continua pendente.</p>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl border border-emerald-900/70 bg-emerald-950/30 p-4">
                    <small class="block text-xs font-bold uppercase tracking-[.16em] text-emerald-300">Presentes</small>
                    <strong id="signed-count" class="mt-2 block text-3xl font-bold text-white">0</strong>
                </div>
                <div class="rounded-2xl border border-amber-900/70 bg-amber-950/30 p-4">
                    <small class="block text-xs font-bold uppercase tracking-[.16em] text-amber-300">Pendentes</small>
                    <strong id="pending-count" class="mt-2 block text-3xl font-bold text-white">0</strong>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-[28px] border border-stone-800 bg-stone-950/70">
                <div class="border-b border-stone-800 px-4 py-3 text-sm font-semibold text-stone-300">
                    Lista actual
                </div>
                <div id="attendance-live-list" class="max-h-[420px] overflow-y-auto p-3 sm:p-4"></div>
            </div>
        </aside>
    </div>
</main>

<script>
const existingAttendance = @json($attendanceMap);
const participantSelect = document.querySelector('#participant-id');
const daySelect = document.querySelector('#event-day-id');
const typeSelect = document.querySelector('#attendance-type');
const replaceExisting = document.querySelector('#replace-existing');
const stateBox = document.querySelector('#attendance-state');
const stateTitle = document.querySelector('#attendance-state-title');
const stateCopy = document.querySelector('#attendance-state-copy');
const stateChip = document.querySelector('#attendance-status-chip');
const stateActions = document.querySelector('#attendance-state-actions');
const previewLink = document.querySelector('#attendance-preview-link');
const submitButton = document.querySelector('#submit-attendance');
const updateButton = document.querySelector('#update-attendance');
const signedCount = document.querySelector('#signed-count');
const pendingCount = document.querySelector('#pending-count');
const liveList = document.querySelector('#attendance-live-list');
const participantOptions = [...participantSelect.options].filter(option => option.value);
const canvas = document.querySelector('#signature');
const context = canvas.getContext('2d');
let drawing = false;
let moved = false;

function sizeCanvas() {
    const ratio = devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    context.scale(ratio, ratio);
    context.lineWidth = 3;
    context.lineCap = 'round';
    context.strokeStyle = '#111827';
}

function point(event) {
    const rect = canvas.getBoundingClientRect();
    const pointer = event.touches ? event.touches[0] : event;
    return [pointer.clientX - rect.left, pointer.clientY - rect.top];
}

function attendanceKey(participantId, dayId, type) {
    return `${participantId}-${dayId}-${type}`;
}

function currentRecord() {
    if (!participantSelect.value || !daySelect.value || !typeSelect.value) return null;
    return existingAttendance[attendanceKey(participantSelect.value, daySelect.value, typeSelect.value)] ?? null;
}

function setNeutralState() {
    stateBox.className = 'mt-5 rounded-[28px] border border-stone-700 bg-stone-800/80 p-5';
    stateTitle.textContent = 'Selecione participante, dia e tipo.';
    stateTitle.className = 'block text-lg font-bold text-white';
    stateCopy.textContent = 'O sistema vai indicar se esta presença é nova ou se já foi registada.';
    stateCopy.className = 'mt-2 text-sm leading-6 text-stone-300';
    stateChip.className = 'inline-flex items-center rounded-full border border-stone-700 bg-stone-800 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-stone-300';
    stateChip.textContent = 'Aguardando selecção';
    stateActions.classList.add('hidden');
    replaceExisting.value = '0';
    submitButton.classList.remove('hidden');
    updateButton.classList.add('hidden');
    updateButton.classList.remove('inline-flex');
}

function setExistingState(record) {
    stateBox.className = 'mt-5 rounded-[28px] border border-amber-300 bg-amber-100 p-5';
    stateTitle.textContent = 'Esta presença já foi registada.';
    stateTitle.className = 'block text-lg font-bold text-amber-950';
    stateCopy.textContent = `Registada em ${record.recorded_at}. Pode actualizar a assinatura directamente.`;
    stateCopy.className = 'mt-2 text-sm leading-6 text-amber-900';
    stateChip.className = 'inline-flex items-center rounded-full border border-amber-300 bg-amber-100 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-amber-950';
    stateChip.textContent = 'Modo actualização';
    replaceExisting.value = '1';
    submitButton.classList.add('hidden');
    updateButton.classList.remove('hidden');
    updateButton.classList.add('inline-flex');

    if (record.signature_url) {
        previewLink.href = record.signature_url;
        stateActions.classList.remove('hidden');
    } else {
        stateActions.classList.add('hidden');
    }
}

function setNewState() {
    stateBox.className = 'mt-5 rounded-[28px] border border-emerald-300 bg-emerald-100 p-5';
    stateTitle.textContent = 'Presença ainda não registada.';
    stateTitle.className = 'block text-lg font-bold text-emerald-950';
    stateCopy.textContent = 'Pode recolher a assinatura normalmente para este participante.';
    stateCopy.className = 'mt-2 text-sm leading-6 text-emerald-900';
    stateChip.className = 'inline-flex items-center rounded-full border border-emerald-300 bg-emerald-100 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-emerald-950';
    stateChip.textContent = 'Novo registo';
    stateActions.classList.add('hidden');
    replaceExisting.value = '0';
    submitButton.classList.remove('hidden');
    updateButton.classList.add('hidden');
    updateButton.classList.remove('inline-flex');
}

function applyState() {
    const ready = participantSelect.value && daySelect.value && typeSelect.value;
    if (!ready) {
        setNeutralState();
        return;
    }

    const record = currentRecord();
    if (record) {
        setExistingState(record);
        return;
    }

    setNewState();
}

function renderLiveList() {
    const dayId = daySelect.value;
    const type = typeSelect.value;

    if (!dayId || !type) {
        signedCount.textContent = '0';
        pendingCount.textContent = participantOptions.length.toString();
        liveList.innerHTML = '<p class="rounded-2xl border border-stone-800 bg-stone-900 px-4 py-4 text-sm text-stone-400">Selecione um dia e o tipo para ver a situação dos participantes.</p>';
        return;
    }

    let signed = 0;
    const rows = participantOptions.map(option => {
        const record = existingAttendance[attendanceKey(option.value, dayId, type)];
        if (record) signed += 1;

        return `
            <div class="flex items-center justify-between gap-3 rounded-2xl border ${record ? 'border-emerald-900/70 bg-emerald-950/25' : 'border-stone-800 bg-stone-900/70'} px-4 py-3">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-white">${option.textContent}</p>
                    <p class="mt-1 text-xs ${record ? 'text-emerald-200' : 'text-stone-400'}">${record ? `Assinou em ${record.recorded_at}` : 'Ainda sem assinatura'}</p>
                </div>
                <span class="inline-flex shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold ${record ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
                    ${record ? 'Presente' : 'Pendente'}
                </span>
            </div>
        `;
    });

    signedCount.textContent = String(signed);
    pendingCount.textContent = String(participantOptions.length - signed);
    liveList.innerHTML = rows.length ? rows.join('') : '<p class="rounded-2xl border border-stone-800 bg-stone-900 px-4 py-4 text-sm text-stone-400">Nenhum participante registado neste evento.</p>';
}

sizeCanvas();

canvas.addEventListener('pointerdown', event => {
    drawing = true;
    moved = false;
    context.beginPath();
    context.moveTo(...point(event));
    canvas.setPointerCapture(event.pointerId);
});

canvas.addEventListener('pointermove', event => {
    if (!drawing) return;
    context.lineTo(...point(event));
    context.stroke();
    moved = true;
});

canvas.addEventListener('pointerup', () => {
    drawing = false;
});

document.querySelector('#clear-signature').addEventListener('click', () => {
    context.clearRect(0, 0, canvas.width, canvas.height);
    moved = false;
});

[participantSelect, daySelect, typeSelect].forEach(element => {
    element.addEventListener('change', () => {
        applyState();
        renderLiveList();
    });
});

document.querySelector('#signature-form').addEventListener('submit', event => {
    if (!moved) {
        event.preventDefault();
        alert('Desenhe a sua assinatura.');
        return;
    }

    document.querySelector('#signature-data').value = canvas.toDataURL('image/png');
});

applyState();
renderLiveList();
</script>
</body>
</html>
