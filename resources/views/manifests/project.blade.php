@php
    $name = mb_substr($project->name, 0, 30);
    $tagline = mb_substr($project->tagline ?? '', 0, 110);
    $username = '@'.$project->creator->username;
    $tags = $project->tags->take(3);
    $serial = $project->filed_serial !== null ? strtoupper($project->filed_serial) : null;
    $launchedOn = strtoupper($launchDate?->format('M j, Y'));
    $verifiedOn = strtoupper($project->verified_at?->format('M j, Y'));
    $firstCheer = $firstCheerUsername !== null
        ? 'FIRST CHEER FROM @'.strtoupper($firstCheerUsername)
        : null;
@endphp
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Shipped manifest — {{ $project->name }}">
    <rect width="1200" height="630" fill="#f4f4f0"/>

    {{-- Black structural frame --}}
    <path d="M40 40 H1160 M40 590 H1160 M40 40 V590 M1160 40 V590" stroke="#050505" stroke-width="8" stroke-linecap="square"/>

    {{-- Header: wordmark + SHIP MANIFEST meta --}}
    <rect x="92" y="76" width="34" height="34" fill="#e61919"/>
    <text x="106" y="93" text-anchor="middle" dominant-baseline="central" font-family="Arial Black, Arial, Helvetica, sans-serif" font-size="22" font-weight="900" fill="#f4f4f0">S</text>
    <text x="140" y="93" dominant-baseline="central" font-family="Arial Black, Arial, Helvetica, sans-serif" font-size="30" font-weight="900" letter-spacing="1" fill="#050505">SHIPPED</text>
    <text x="1108" y="93" text-anchor="end" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="18" font-weight="700" letter-spacing="3" fill="#585852">SHIP MANIFEST</text>

    {{-- Hero: project name + tagline --}}
    <text x="92" y="240" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="86" letter-spacing="-3" fill="#050505">{{ strtoupper($name) }}</text>
    @if ($tagline)
        <text x="94" y="300" font-family="Arial, Helvetica, sans-serif" font-size="30" fill="#585852">{{ $tagline }}</text>
    @endif

    {{-- Crew line: creator + top-3 stack tags --}}
    <text x="92" y="364" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="24" font-weight="700" letter-spacing="1" fill="#050505">{{ strtoupper($username) }}</text>
    @foreach ($tags as $tag)
        <text x="{{ 92 + 40 + $loop->iteration * 190 }}" y="364" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="22" letter-spacing="1" fill="#585852">+ {{ strtoupper(mb_substr($tag->name, 0, 16)) }}</text>
    @endforeach

    {{-- Docket row: launch date + filed serial --}}
    <path d="M92 404 H1108" stroke="#050505" stroke-width="2"/>
    <text x="92" y="448" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="22" font-weight="700" letter-spacing="2" fill="#050505">LAUNCHED {{ $launchedOn }}</text>
    @if ($serial)
        <text x="1108" y="448" text-anchor="end" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="22" font-weight="700" letter-spacing="3" fill="#050505">{{ $serial }}</text>
    @endif

    {{-- LIVE ON CLOUD stamp + verification date --}}
    <g transform="translate(92 484)">
        <rect width="250" height="46" fill="#16a34a"/>
        <text x="125" y="23" text-anchor="middle" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="20" font-weight="700" letter-spacing="2" fill="#f4f4f0">LIVE ON CLOUD</text>
    </g>
    @if ($verifiedOn)
        <text x="360" y="507" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="20" letter-spacing="1" fill="#585852">SINCE {{ $verifiedOn }}</text>
    @endif

    {{-- First cheer line --}}
    @if ($firstCheer)
        <text x="1108" y="507" text-anchor="end" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="20" letter-spacing="1" fill="#585852">{{ $firstCheer }}</text>
    @endif

    {{-- Footer wordmark --}}
    <path d="M92 548 H1108" stroke="#050505" stroke-width="2"/>
    <text x="92" y="574" dominant-baseline="central" font-family="Arial Black, Arial, Helvetica, sans-serif" font-size="22" font-weight="900" letter-spacing="2" fill="#050505">SHIPPED</text>
    <text x="1108" y="574" text-anchor="end" dominant-baseline="central" font-family="'IBM Plex Mono', 'Courier New', monospace" font-size="16" letter-spacing="2" fill="#585852">COMMUNITY REGISTRY</text>
</svg>
