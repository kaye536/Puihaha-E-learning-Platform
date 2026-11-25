@props(['size' => 40, 'word' => false, 'tag' => null, 'tone' => 'cream'])
<span {{ $attributes->merge(['class' => 'logo']) }}>
    <img class="logo-mark" src="{{ asset('images/logo.png') }}" width="{{ $size }}" height="{{ $size }}" alt="Puihaha logo" style="width:{{ $size }}px;height:{{ $size }}px;object-fit:contain">
    @if ($word)
        <span class="logo-text"><strong>puihaha</strong>@if ($tag)<small>{{ $tag }}</small>@endif</span>
    @endif
</span>
