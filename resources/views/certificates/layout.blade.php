<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>@yield('title', 'Certificate verification')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />

    @include('certificates.partials.styles')

    @verbatim
        <style>
            /* Public shell, on the abbadev.com palette. -------------------- */
            :root {
                color-scheme: light dark;

                --pv-paper: oklch(97.5% 0.012 236);
                --pv-raised: oklch(94.5% 0.019 238);
                --pv-card: oklch(100% 0 0);
                --pv-ink: oklch(20% 0.045 250);
                --pv-body: oklch(43% 0.04 252);
                --pv-muted: oklch(50% 0.036 252);
                --pv-rule: oklch(89% 0.018 244);
                --pv-accent: oklch(55% 0.19 258);
                --pv-accent-soft: oklch(91% 0.055 254);
                --pv-success: oklch(52% 0.15 148);
                --pv-success-soft: oklch(94% 0.05 148);
                --pv-danger: oklch(52% 0.19 25);
                --pv-danger-soft: oklch(94% 0.04 25);
                --pv-shadow: 0 18px 54px oklch(20% 0.045 250 / 0.09);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --pv-paper: oklch(13% 0.022 254);
                    --pv-raised: oklch(17% 0.028 252);
                    --pv-card: oklch(21% 0.035 254);
                    --pv-ink: oklch(96% 0.012 240);
                    --pv-body: oklch(80% 0.022 245);
                    --pv-muted: oklch(64% 0.03 248);
                    --pv-rule: oklch(100% 0 0 / 0.13);
                    --pv-accent: oklch(68% 0.19 258);
                    --pv-accent-soft: oklch(32% 0.09 258);
                    --pv-success: oklch(72% 0.17 150);
                    --pv-success-soft: oklch(28% 0.07 150);
                    --pv-danger: oklch(70% 0.18 25);
                    --pv-danger-soft: oklch(28% 0.08 25);
                    --pv-shadow: 0 18px 54px oklch(0% 0 0 / 0.5);
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: var(--pv-paper);
                color: var(--pv-body);
                font: 16px/1.6 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            img,
            svg {
                display: block;
            }

            :focus-visible {
                outline: 2px solid var(--pv-accent);
                outline-offset: 2px;
            }

            .pv-shell {
                max-width: 1020px;
                margin: 0 auto;
                padding: 0 20px 72px;
            }

            .pv-topbar {
                border-bottom: 1px solid var(--pv-rule);
                background: var(--pv-card);
            }

            .pv-topbar-inner {
                display: flex;
                max-width: 1020px;
                margin: 0 auto;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
            }

            .pv-topbar img {
                width: 34px;
                height: 34px;
            }

            .pv-topbar span {
                font-size: 15px;
                font-weight: 800;
                letter-spacing: 0.16em;
                color: var(--pv-ink);
            }

            .pv-crumbs {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 22px 0 26px;
                font-size: 14px;
                color: var(--pv-muted);
            }

            .pv-crumbs a {
                color: var(--pv-accent);
                text-decoration: none;
            }

            .pv-crumbs a:hover {
                text-decoration: underline;
            }

            .pv-stage {
                border-radius: 14px;
                overflow: hidden;
                box-shadow: var(--pv-shadow);
            }

            .pv-actions {
                display: grid;
                gap: 12px;
                margin: 28px 0 0;
            }

            @media (min-width: 640px) {
                .pv-actions {
                    grid-template-columns: 1fr 1fr;
                }
            }

            .pv-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                padding: 16px 20px;
                border: 1px solid var(--pv-accent);
                border-radius: 10px;
                background: transparent;
                color: var(--pv-accent);
                font: inherit;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
            }

            .pv-btn:hover {
                background: var(--pv-accent-soft);
            }

            .pv-btn-primary {
                background: var(--pv-accent);
                color: oklch(100% 0 0);
            }

            .pv-btn-primary:hover {
                filter: brightness(1.08);
                background: var(--pv-accent);
            }

            .pv-record {
                margin: 32px 0 0;
                padding: 4px 22px 18px;
                border: 1px solid var(--pv-rule);
                border-radius: 12px;
                background: var(--pv-card);
            }

            .pv-record h2 {
                margin: 20px 0 14px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: var(--pv-muted);
            }

            .pv-record dl {
                display: grid;
                margin: 0;
                gap: 14px 28px;
            }

            @media (min-width: 640px) {
                .pv-record dl {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .pv-record dt {
                font-size: 12px;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: var(--pv-muted);
            }

            .pv-record dd {
                margin: 2px 0 0;
                color: var(--pv-ink);
                font-weight: 600;
                overflow-wrap: anywhere;
            }

            .pv-record dd.pv-mono {
                font-family: 'IBM Plex Mono', ui-monospace, monospace;
                font-weight: 400;
            }

            .pv-pill {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .pv-pill-active {
                background: var(--pv-success-soft);
                color: var(--pv-success);
            }

            .pv-pill-revoked {
                background: var(--pv-danger-soft);
                color: var(--pv-danger);
            }

            .pv-foot {
                margin: 34px 0 0;
                font-size: 13px;
                color: var(--pv-muted);
            }
        </style>
    @endverbatim

    @stack('styles')
</head>
<body>
    <header class="pv-topbar">
        <div class="pv-topbar-inner">
            <img src="{{ asset('images/abbadev-logo.png') }}" alt="" width="34" height="34" />
            <span>ABBADEV</span>
        </div>
    </header>

    @yield('content')

    @stack('scripts')
</body>
</html>
