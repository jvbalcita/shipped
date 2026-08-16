@php
    $leftWidth = 96;
    $rightWidth = 20 + 7 * strlen($label);
    $total = $leftWidth + $rightWidth;
    $radius = 3;
@endphp
<svg width="{{ $total }}" height="28" viewBox="0 0 {{ $total }} 28" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Shipped — {{ $project->name }} {{ $label }}">
    {{-- Left SHIPPED wordmark segment (rounded left corners) --}}
    <path d="M {{ $radius }} 0 H {{ $leftWidth }} V 28 H {{ $radius }} Q 0 28 0 {{ 28 - $radius }} V {{ $radius }} Q 0 0 {{ $radius }} 0 Z" fill="#050505"/>
    <text x="{{ $leftWidth / 2 }}" y="14" text-anchor="middle" dominant-baseline="central" font-family="Arial Black, Arial, Helvetica, sans-serif" font-size="11" font-weight="900" letter-spacing="1.5" fill="#f4f4f0">SHIPPED</text>

    {{-- Right status segment (rounded right corners) --}}
    <path d="M {{ $leftWidth }} 0 H {{ $total - $radius }} Q {{ $total }} 0 {{ $total }} {{ $radius }} V {{ 28 - $radius }} Q {{ $total }} 28 {{ $total - $radius }} 28 H {{ $leftWidth }} Z" fill="{{ $color }}"/>
    <text x="{{ $leftWidth + $rightWidth / 2 }}" y="14" text-anchor="middle" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="10" font-weight="700" letter-spacing="1" fill="#f4f4f0">{{ $label }}</text>
</svg>
