@extends('layouts.app',['title'=>'Editar '.$event->name.' — Sinala']) @section('content')<p class="eyebrow">EDITAR EVENTO</p><h1 class="mt-2 text-4xl font-bold">{{ $event->name }}</h1><form method="post" action="{{ route('events.update',$event) }}" class="mt-8 grid gap-5 rounded-3xl bg-white p-6 sm:grid-cols-2 lg:p-8">@csrf @method('PUT')
@include('events._form', ['event'=>$event])
<div class="flex gap-3 sm:col-span-2"><a class="btn-secondary" href="{{ route('events.show',$event) }}">Cancelar</a><button class="btn-primary">Guardar alterações</button></div></form>@endsection
