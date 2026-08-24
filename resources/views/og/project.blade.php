@php
    $name = mb_strtoupper(mb_substr($project->name, 0, 46));
    $username = '@'.$project->creator->username;
    $category = mb_strtoupper($project->category?->name ?? 'Launch');
    $tagline = mb_substr($project->tagline ?? '', 0, 110);
    $storyExcerpt = $project->shipStory?->excerpt(92);
    $serial = $project->filed_serial;
@endphp
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $project->name }}">
    <rect width="1200" height="630" fill="#f4f4f0"/>

    {{-- Black structural frame --}}
    <path d="M40 40 H1160 M40 590 H1160 M40 40 V590 M1160 40 V590" stroke="#050505" stroke-width="8" stroke-linecap="square"/>

    {{-- Top meta row --}}
    <text x="92" y="118" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="2" fill="#050505">{{ $category }}</text>
    @if ($serial)
        <text x="1108" y="118" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="3" fill="#050505">{{ mb_strtoupper($serial) }}</text>
    @endif

    {{-- Red FILED stamp --}}
    <g transform="translate(92 168)">
        <rect width="186" height="60" fill="#e61919"/>
        <text x="93" y="30" text-anchor="middle" dominant-baseline="central" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900" letter-spacing="2" fill="#f4f4f0">FILED</text>
    </g>

    {{-- Project name --}}
    <text x="92" y="402" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="104" letter-spacing="-4" fill="#050505">{{ $name }}</text>

    {{-- Tagline --}}
    @if ($tagline)
        <text x="94" y="468" font-family="Arial, Helvetica, sans-serif" font-size="32" fill="#585852">{{ $tagline }}</text>
    @endif

    @if ($storyExcerpt)
        <text x="94" y="508" font-family="Arial, Helvetica, sans-serif" font-size="22" fill="#585852">{{ $storyExcerpt }}</text>
    @endif

    {{-- Bottom rule + creator username --}}
    <path d="M92 528 H1108" stroke="#050505" stroke-width="2"/>
    <text x="92" y="566" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="1" fill="#050505">{{ mb_strtoupper($username) }}</text>
    <text x="1108" y="566" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="2" fill="#050505">SHIPPED</text>
</svg>
