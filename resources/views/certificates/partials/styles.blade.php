{{-- Shared certificate styling. Included by the admin generator, the public
     verification page and the print/download view so all three render the
     identical artwork. --}}
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
/>

@verbatim
    <style>
        .ecert-frame {
            --ecert-navy-0: oklch(15.5% 0.042 252);
            --ecert-navy-1: oklch(20% 0.05 252);
            --ecert-navy-2: oklch(27% 0.055 252);
            --ecert-paper: oklch(98.5% 0.004 250);
            --ecert-soft: oklch(86% 0.018 248);
            --ecert-muted: oklch(72% 0.026 250);
            --ecert-faint: oklch(60% 0.028 250);

            container-type: inline-size;
            position: relative;
            width: 100%;
            aspect-ratio: 297 / 210;
            overflow: hidden;
            border-radius: 6px;
            background: var(--ecert-navy-0);
            color: var(--ecert-paper);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            box-shadow: 0 26px 70px rgb(0 0 0 / 0.35);
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        /* Text, rules, the QR ring and the seal. The two stops match the
           background artwork's own accent gradient so the type sits on the
           same palette as the swoops behind it. */
        .ecert-frame[data-accent='blue'] {
            --ecert-accent-1: #154bff;
            --ecert-accent-2: #00c8ff;
            --ecert-accent-3: #eafbff;
        }

        .ecert-frame[data-accent='gold'] {
            --ecert-accent-1: #7c5a1d;
            --ecert-accent-2: #cfa74e;
            --ecert-accent-3: #f7e8b8;
        }

        .ecert-art {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            /* The artwork is 4:3 and the sheet is A4 landscape, so it fills the
               width and loses a sliver top and bottom where the swoops already
               run off the canvas. */
            object-fit: cover;
            object-position: center;
        }

        .ecert-body {
            position: relative;
            display: flex;
            height: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 1.4cqw;
            padding: 4.4cqw 17cqw;
            text-align: center;
        }

        .ecert-head,
        .ecert-main,
        .ecert-foot {
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: center;
        }

        .ecert-head {
            gap: 1.5cqw;
        }

        .ecert-brand {
            display: flex;
            align-items: center;
            gap: 1.3cqw;
        }

        .ecert-logo {
            height: 4.4cqw;
            width: auto;
        }

        .ecert-lockup {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5cqw;
        }

        .ecert-wordmark {
            font-size: 2.3cqw;
            font-weight: 800;
            letter-spacing: 0.2em;
            line-height: 1;
            /* The trailing letter-space would push the word off-centre against
               the tagline below it. */
            text-indent: 0.2em;
            color: var(--ecert-paper);
        }

        .ecert-tagline {
            font-size: 0.92cqw;
            font-weight: 600;
            letter-spacing: 0.42em;
            line-height: 1;
            text-indent: 0.42em;
            color: var(--ecert-accent-2);
        }

        .ecert-title {
            margin: 0;
            font-size: var(--ecert-title-size, 3.2cqw);
            font-weight: 700;
            letter-spacing: 0.15em;
            line-height: 1.1;
            text-transform: uppercase;
            color: var(--ecert-accent-3);
            /* Skips accent-1: the darkest stop is the artwork's own deep blue,
               which loses contrast against the ground when it lands on a
               leading letter. */
            background: linear-gradient(
                100deg,
                var(--ecert-accent-2) 0%,
                var(--ecert-accent-3) 34%,
                var(--ecert-accent-2) 62%,
                var(--ecert-accent-3) 100%
            );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .ecert-main {
            gap: 0.85cqw;
        }

        .ecert-lead {
            margin: 0;
            font-size: 1.4cqw;
            font-weight: 400;
            line-height: 1.4;
            color: var(--ecert-muted);
        }

        .ecert-name {
            margin: 0.2cqw 0;
            font-size: var(--ecert-name-size, 7.1cqw);
            font-weight: 800;
            letter-spacing: -0.015em;
            line-height: 1.08;
            color: var(--ecert-paper);
        }

        .ecert-rule {
            display: block;
            width: 32cqw;
            height: 0.16cqw;
            margin: 0.3cqw 0 0.9cqw;
            background: linear-gradient(90deg, transparent, var(--ecert-accent-2), transparent);
        }

        .ecert-activity {
            margin: 0;
            font-size: var(--ecert-activity-size, 3.05cqw);
            font-weight: 600;
            line-height: 1.25;
            color: var(--ecert-soft);
        }

        .ecert-copy {
            max-width: 60cqw;
            margin: 0.7cqw 0 0;
            font-size: 1.28cqw;
            line-height: 1.7;
            color: var(--ecert-muted);
        }

        .ecert-foot {
            gap: 1.7cqw;
        }

        .ecert-signatures {
            display: flex;
            width: 100%;
            align-items: end;
            justify-content: center;
            gap: 7cqw;
        }

        .ecert-signature {
            display: flex;
            width: 30cqw;
            flex-direction: column;
            align-items: center;
            gap: 0.55cqw;
        }

        .ecert-signature-mark {
            /* Sized from the width, not the height: a signature is wide and
               short, and sizing it by height left it a fraction of the line it
               is meant to sit across. 80% of the rule, with the height capped
               so an unusually square upload cannot push the footer down.
               The negative margin drops it onto the rule, the way a signature
               runs over a printed line. */
            width: 80%;
            height: auto;
            max-height: 9cqw;
            margin-bottom: -1.2cqw;
            object-fit: contain;
            /* Signatures are signed in dark ink, and this certificate is dark.
               Inverting turns the ink light; screen then drops what inverting
               made black — the paper of a scan — so only the strokes remain.
               A transparent PNG passes through the same way. */
            filter: invert(1);
            mix-blend-mode: screen;
        }

        .ecert-signature-rule {
            display: block;
            width: 100%;
            height: 0.18cqw;
            margin-bottom: 0.7cqw;
            background: linear-gradient(
                90deg,
                transparent,
                var(--ecert-accent-2) 22%,
                var(--ecert-accent-3) 50%,
                var(--ecert-accent-2) 78%,
                transparent
            );
        }

        .ecert-signature-name {
            margin: 0;
            font-size: 1.75cqw;
            font-weight: 700;
            line-height: 1.2;
            color: var(--ecert-paper);
        }

        .ecert-signature-role {
            margin: 0;
            font-size: 1.15cqw;
            font-weight: 500;
            letter-spacing: 0.06em;
            line-height: 1.3;
            color: var(--ecert-accent-2);
        }

        .ecert-seal {
            display: block;
            width: 10.4cqw;
            height: 10.4cqw;
        }

        .ecert-seal-label,
        .ecert-seal-year {
            text-anchor: middle;
            fill: var(--ecert-accent-3);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            font-weight: 700;
        }

        .ecert-seal-label {
            font-size: 12px;
            letter-spacing: 1.4px;
        }

        .ecert-seal-year {
            font-size: 14px;
            letter-spacing: 0.8px;
        }

        .ecert-qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.6cqw;
        }

        .ecert-qr-panel {
            display: block;
            padding: 0.7cqw;
            border-radius: 0.7cqw;
            background: #fff;
            box-shadow: 0 0 0 0.16cqw var(--ecert-accent-2);
        }

        .ecert-qr-svg {
            display: block;
            width: 9.4cqw;
            height: 9.4cqw;
        }

        .ecert-qr-caption {
            margin: 0;
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 0.86cqw;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ecert-accent-2);
        }

        .ecert-meta {
            display: flex;
            margin: 0;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.6cqw 4cqw;
        }

        .ecert-meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25cqw;
        }

        .ecert-meta-item dt {
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 0.92cqw;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ecert-faint);
        }

        .ecert-meta-item dd {
            margin: 0;
            font-family: 'IBM Plex Mono', ui-monospace, monospace;
            font-size: 1.12cqw;
            color: var(--ecert-soft);
        }

        .ecert-note {
            max-width: 64cqw;
            margin: 0;
            font-size: 1cqw;
            line-height: 1.5;
            color: var(--ecert-faint);
        }
    </style>
@endverbatim
