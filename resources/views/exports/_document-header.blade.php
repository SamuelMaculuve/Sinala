@if($headerBannerData)
<img class="header-banner" src="{{ $headerBannerData }}">
@else
<table class="header"><tr>
<td class="secondary-logos-cell">
@if($secondaryLogosData)
<table class="secondary-logos"><tr>@foreach($secondaryLogosData as $secondaryLogo)<td><img src="{{ $secondaryLogo }}"></td>@endforeach</tr></table>
@endif
</td>
<td class="org">{{ $settings['header_title'] ?? $event->organization->name }}<br><span class="muted">{{ $settings['header_subtitle'] ?? 'Documento oficial' }}</span></td>
<td class="primary-logo-cell"><img class="logo" src="{{ $logoData ?: public_path('brand/sinala-logo-transparent.png') }}"></td>
</tr></table>
@endif
