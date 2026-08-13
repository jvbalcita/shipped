@php
    $name = mb_strtoupper($project->name ?? '');
    $len = mb_strlen($name);
    // Size the wordmark to fit the frame; clamp very long names.
    if ($len <= 8) {
        $size = 160;
    } elseif ($len <= 12) {
        $size = 128;
    } elseif ($len <= 16) {
        $size = 104;
    } else {
        $size = 84;
        if ($len > 18) {
            $name = mb_substr($name, 0, 17).'…';
        }
    }
    $username = '@'.strtoupper($project->creator->username);
    $serial = $project->filed_serial;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 750" role="img" aria-label="{{ $project->name }}">
    <rect width="1200" height="750" fill="#f4f4f0"/>
    <rect x="60" y="60" width="1080" height="630" fill="none" stroke="#050505" stroke-width="8"/>

    <rect x="100" y="324" width="116" height="14" fill="#e61919"/>

    <text x="96" y="472" font-family="Arial Black, Arial, Helvetica, sans-serif" font-size="{{ $size }}" font-weight="900" letter-spacing="-4" fill="#050505">{{ $name }}</text>

    <text x="100" y="638" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="2" fill="#585852">{{ $username }}</text>
    <text x="1100" y="638" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="2" fill="#050505">{{ $serial ? strtoupper($serial) : 'COVER PENDING' }}</text>
</svg>
