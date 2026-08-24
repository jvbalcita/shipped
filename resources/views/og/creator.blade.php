@php
    $name = mb_strtoupper(mb_substr($creator->name, 0, 30));
    $username = '@'.mb_strtoupper($creator->username);
    $title = mb_strtoupper(mb_substr($creator->title ?? 'Laravel Creator', 0, 52));
    $bio = mb_substr((string) ($creator->bio ?? ''), 0, 92);
@endphp
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $creator->name }} Shipping Profile">
    <rect width="1200" height="630" fill="#f4f4f0"/>
    <path d="M40 40 H1160 M40 590 H1160 M40 40 V590 M1160 40 V590" stroke="#050505" stroke-width="8" stroke-linecap="square"/>

    <text x="92" y="118" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="3" fill="#050505">SHIPPING PROFILE</text>
    <text x="1108" y="118" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="26" font-weight="700" letter-spacing="3" fill="#050505">CREATOR RECORD</text>

    <g transform="translate(92 168)">
        <rect width="186" height="60" fill="#e61919"/>
        <text x="93" y="30" text-anchor="middle" dominant-baseline="central" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="900" letter-spacing="2" fill="#f4f4f0">SHIPPED</text>
    </g>

    <text x="92" y="402" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="92" letter-spacing="-4" fill="#050505">{{ $name }}</text>
    <text x="94" y="462" font-family="Arial, Helvetica, sans-serif" font-size="30" fill="#585852">{{ $title }}</text>
    @if ($bio)
        <text x="94" y="504" font-family="Arial, Helvetica, sans-serif" font-size="22" fill="#585852">{{ $bio }}</text>
    @endif

    <path d="M92 528 H1108" stroke="#050505" stroke-width="2"/>
    <text x="92" y="566" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="1" fill="#050505">{{ $username }}</text>
    <text x="1108" y="566" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="700" letter-spacing="1" fill="#050505">{{ $projectCount }} PROJECTS · {{ $verifiedProjectCount }} VERIFIED · {{ $releaseCount }} RELEASES</text>
</svg>
