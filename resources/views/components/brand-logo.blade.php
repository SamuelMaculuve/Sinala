@props(['class' => 'h-[42px] w-auto'])

<img src="{{ asset('brand/sinala-logo-transparent.png') }}" alt="Sinala" width="121" height="42" {{ $attributes->class([$class]) }}>
