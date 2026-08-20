@extends('layouts.app',['title'=>'Configurar documentos — Sinala'])
@section('content')
@php($settings=$organization->report_settings ?? [])
@php($secondaryLogoPaths=array_values($settings['secondary_logo_paths'] ?? []))
<div><p class="eyebrow">DOCUMENTOS</p><h1 class="mt-2 text-4xl font-bold">Cabeçalho e assinaturas</h1><p class="mt-2 max-w-3xl text-stone-500">Estas configurações são exclusivas da {{ $organization->name }} e serão aplicadas automaticamente às listas de presença e de recebimento.</p></div>
<form method="post" enctype="multipart/form-data" action="{{ route('organization.documents.update') }}" class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">@csrf @method('put')
  <section class="grid gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">
    <label class="field sm:col-span-2">Título do cabeçalho<input name="header_title" value="{{ old('header_title',$settings['header_title'] ?? $organization->name) }}" placeholder="Nome da organização ou programa"></label>
    <label class="field sm:col-span-2">Subtítulo<input name="header_subtitle" value="{{ old('header_subtitle',$settings['header_subtitle'] ?? '') }}" placeholder="Unidade, programa ou descrição institucional"></label>
    <label class="field">Projecto<input name="project_name" value="{{ old('project_name',$settings['project_name'] ?? '') }}" placeholder="Nome do projecto"></label>
    <label class="field">Referência/financiamento<input name="funding_reference" value="{{ old('funding_reference',$settings['funding_reference'] ?? '') }}" placeholder="Contrato, financiador ou actividade"></label>
    <label class="field sm:col-span-2">Nota no rodapé<textarea name="footer_note" rows="3" placeholder="Texto opcional apresentado no final do documento">{{ old('footer_note',$settings['footer_note'] ?? '') }}</textarea></label>
    <div class="rounded-2xl border border-stone-200 p-5 sm:col-span-2">
      <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="font-bold text-stone-950">Logótipo principal</h2><p class="mt-1 text-sm text-stone-500">Aparece sozinho no lado esquerdo do cabeçalho.</p></div>@if($organization->logo_path)<img src="{{ route('organization.documents.logo') }}" alt="Logótipo principal actual" class="max-h-14 max-w-36 object-contain">@endif</div>
      <label class="field mt-4">Seleccionar logótipo principal<input type="file" name="logo" accept="image/png,image/jpeg"><small class="mt-2 block font-normal text-stone-400">PNG ou JPG, até 2 MB. Uma nova imagem substitui a actual.</small></label>
      @if($organization->logo_path)<label class="mt-4 flex items-center gap-3 text-sm text-stone-600"><input type="checkbox" name="remove_logo" value="1"> Remover o logótipo principal actual</label>@endif
    </div>
    <div class="rounded-2xl border border-stone-200 p-5 sm:col-span-2">
      <div><h2 class="font-bold text-stone-950">Logótipos adicionais</h2><p class="mt-1 text-sm text-stone-500">Até três parceiros ou financiadores, ajustados automaticamente no lado direito.</p></div>
      @if($secondaryLogoPaths)
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          @foreach($secondaryLogoPaths as $index => $path)
            <label class="flex min-h-32 flex-col items-center justify-between rounded-xl bg-stone-50 p-4 text-center text-sm text-stone-600"><img src="{{ route('organization.documents.secondary-logo',$index) }}" alt="Logótipo adicional {{ $index + 1 }}" class="max-h-14 max-w-full object-contain"><span class="mt-3 flex items-center gap-2"><input type="checkbox" name="remove_secondary_logos[]" value="{{ $index }}"> Remover</span></label>
          @endforeach
        </div>
      @endif
      <label class="field mt-4">Adicionar logótipos<input type="file" name="secondary_logos[]" accept="image/png,image/jpeg" multiple><small class="mt-2 block font-normal text-stone-400">PNG ou JPG, até 2 MB cada. Pode seleccionar vários ficheiros; o total não pode ultrapassar três.</small></label>
      @error('secondary_logos')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
      @error('secondary_logos.*')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
    </div>
    <label class="field sm:col-span-2">Faixa de logótipos do cabeçalho<input type="file" name="header_banner" accept="image/png,image/jpeg"><small class="mt-2 block font-normal text-stone-400">Use esta opção quando o documento deve apresentar vários parceiros ou financiadores no cabeçalho.</small></label>
    @if(!empty($settings['header_banner_path']))<p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800 sm:col-span-2">A faixa actual ocupa todo o cabeçalho e tem prioridade sobre os logótipos individuais. Remova-a para usar o novo formato com logótipo principal à esquerda e adicionais à direita.</p>@endif
    @if(!empty($settings['header_banner_path']))<label class="flex items-center gap-3 text-sm text-stone-600 sm:col-span-2"><input type="checkbox" name="remove_header_banner" value="1"> Remover a faixa de logótipos actual</label>@endif
  </section>
  <aside class="space-y-6"><section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">Pré-visualização do cabeçalho</h2><div class="mt-5 rounded-2xl border bg-stone-50 p-5">@if(!empty($settings['header_banner_path']))<img src="{{ route('organization.documents.header') }}" alt="Logótipos do cabeçalho" class="w-full object-contain">@else<div class="grid grid-cols-[minmax(60px,1fr)_1.5fr_minmax(90px,1.4fr)] items-center gap-3">@if($organization->logo_path)<img src="{{ route('organization.documents.logo') }}" alt="Logótipo principal" class="max-h-12 max-w-full object-contain object-left">@else<x-brand-logo class="h-9 max-w-full object-contain object-left" />@endif<div class="text-center"><strong class="block text-xs">{{ $settings['header_title'] ?? $organization->name }}</strong><small class="mt-1 block text-[10px] text-stone-500">{{ $settings['header_subtitle'] ?? 'Lista oficial da organização' }}</small></div><div class="grid items-center gap-2" style="grid-template-columns:repeat({{ max(count($secondaryLogoPaths),1) }},minmax(0,1fr))">@foreach($secondaryLogoPaths as $index => $path)<img src="{{ route('organization.documents.secondary-logo',$index) }}" alt="Logótipo adicional {{ $index + 1 }}" class="max-h-10 max-w-full object-contain">@endforeach</div></div>@endif</div></section>
  <section class="rounded-3xl bg-white p-6"><h2 class="text-xl font-bold">Responsáveis que assinam</h2><p class="mt-2 text-sm text-stone-500">Seleccione no máximo duas contas.</p><div class="mt-4 space-y-3">@foreach($organization->users as $user)<label class="flex items-center gap-3 rounded-xl border p-4"><input type="checkbox" name="signatory_user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id,old('signatory_user_ids',$settings['signatory_user_ids'] ?? [])))><span><strong class="block text-sm">{{ $user->name }}</strong><small class="text-stone-400">{{ $user->roles->pluck('name')->join(', ') }}</small></span></label>@endforeach</div></section></aside>
  <div class="flex gap-3 xl:col-span-2"><a class="btn-secondary" href="{{ route('organization.reports') }}">Cancelar</a><button class="btn-primary">Guardar configuração</button></div>
</form>
@endsection
