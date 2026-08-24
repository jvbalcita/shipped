@php
    $title = mb_strtoupper(mb_substr($release->title, 0, 46));
    $projectName = mb_strtoupper(mb_substr($release->project->name, 0, 46));
    $creatorUsername = '@'.mb_strtoupper($release->project->creator->username);
    $notes = mb_substr($release->notes ?? '', 0, 110);
@endphp
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $release->title }} — {{ $release->project->name }}">
    <rect width="1200" height="630" fill="#f4f4f0"/>
    <path d="M40 40 H1160 M40 590 H1160 M40 40 V590 M1160 40 V590" stroke="#050505" stroke-width="8" stroke-linecap="square"/>

    <text x="92" y="118" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="3" fill="#050505">RELEASE RECORD</text>
    <text x="1108" y="118" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="3" fill="#050505">{{ $projectName }}</text>

    <g transform="translate(92 168)">
        <rect width="186" height="60" fill="#e61919"/>
        <text x="93" y="30" text-anchor="middle" dominant-baseline="central" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900" letter-spacing="2" fill="#f4f4f0">PUBLISHED</text>
    </g>

    <text x="92" y="402" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="96" letter-spacing="-4" fill="#050505">{{ $title }}</text>
    @if ($notes)
        <text x="94" y="468" font-family="Arial, Helvetica, sans-serif" font-size="32" fill="#585852">{{ $notes }}</text>
    @endif

    <path d="M92 528 H1108" stroke="#050505" stroke-width="2"/>
    <text x="92" y="566" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="1" fill="#050505">{{ $creatorUsername }}</text>
    <text x="1108" y="566" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="2" fill="#050505">SHIPPED</text>
</svg>
