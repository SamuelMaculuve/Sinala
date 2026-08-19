@extends('layouts.app',['title'=>'Novo evento — Sinala']) @section('content')<p class="eyebrow">NOVO EVENTO</p><h1 class="mt-2 text-4xl font-bold">Prepare a próxima actividade</h1><form method="post" action="{{ route('events.store') }}" class="mt-8 grid gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">@csrf
@include('events._form')
<div class="flex gap-3 sm:col-span-2"><a class="btn-secondary" href="{{ route('events.index') }}">Cancelar</a><button class="btn-primary">Criar evento</button></div></form>@endsection
