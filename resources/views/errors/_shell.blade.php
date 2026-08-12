@php
    $code = $code ?? 500;
    $label = $label ?? 'System notice';
    $headline = $headline ?? 'Record error';
    $message = $message ?? 'Something went wrong while retrieving this record.';
    $hint = $hint ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $code }} — {{ config('app.name', 'Shipped') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <style>
            :root { color-scheme: light; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                background-color: #f4f4f0;
                color: #050505;
                font-family: ui-monospace, "IBM Plex Mono", "SFMono-Regular", Menlo, monospace;
                -webkit-font-smoothing: antialiased;
            }
            a { color: inherit; }
            .frame {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                border-bottom: 1px solid #050505;
            }
            .card {
                width: 100%;
                max-width: 42rem;
                border: 1px solid #050505;
                background: #f4f4f0;
            }
            .meta {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                padding: 0.9rem 1.25rem;
                border-bottom: 1px solid #050505;
                font-size: 0.72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }
            .meta .signal { color: #e61919; }
            .body { padding: 2.5rem 1.25rem; }
            .headline {
                margin: 0;
                font-family: "Arial Black", "Archivo Black", Impact, sans-serif;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: -0.065em;
                line-height: 0.84;
                font-size: clamp(2.75rem, 9vw, 5.5rem);
            }
            .code {
                display: inline-block;
                margin-top: 1.25rem;
                padding: 0.15rem 0.6rem;
                border: 1px solid #050505;
                font-size: 0.72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-variant-numeric: tabular-nums;
            }
            .message {
                margin-top: 1.5rem;
                max-width: 32rem;
                font-size: 0.95rem;
                line-height: 1.6;
                color: #585852;
            }
            .hint {
                margin-top: 1rem;
                font-size: 0.85rem;
                color: #585852;
            }
            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 2rem;
            }
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.6rem 1.1rem;
                border: 1px solid #050505;
                font-family: inherit;
                font-size: 0.72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                text-decoration: none;
                background: #f4f4f0;
                color: #050505;
                cursor: pointer;
            }
            .btn-primary { background: #e61919; color: #f4f4f0; border-color: #e61919; }
            .footer {
                padding: 1rem 2rem;
                font-size: 0.72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }
        </style>
    </head>
    <body>
        <div class="frame">
            <div class="card">
                <div class="meta">
                    <span class="signal">{{ $label }}</span>
                    <span>Shipped / Registry</span>
                </div>
                <div class="body">
                    <h1 class="headline">{{ $headline }}</h1>
                    <span class="code">Status {{ $code }}</span>
                    <p class="message">{{ $message }}</p>
                    @if ($hint)
                        <p class="hint">{{ $hint }}</p>
                    @endif
                    <div class="actions">
                        <a class="btn btn-primary" href="/discover">Return to registry</a>
                        <a class="btn" href="/">Back to home</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">A public home for launches worth sharing.</div>
    </body>
</html>
