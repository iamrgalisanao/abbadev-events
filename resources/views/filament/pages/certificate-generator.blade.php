<x-filament-panels::page>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    />

    @verbatim
        <style>
            /* Page shell ------------------------------------------------- */
            .ecert-layout {
                display: grid;
                gap: 1.5rem;
                align-items: start;
            }

            @media (min-width: 1400px) {
                .ecert-layout {
                    grid-template-columns: minmax(0, 30rem) minmax(0, 1fr);
                }

                .ecert-preview {
                    position: sticky;
                    top: 5rem;
                }
            }

            .ecert-preview-label {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 0.75rem;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--gray-500, #6b7280);
            }

            .ecert-preview-hint {
                font-weight: 400;
                letter-spacing: 0;
                text-transform: none;
            }

            /* Certificate ------------------------------------------------ */
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
                background: radial-gradient(125% 95% at 50% 0%, var(--ecert-navy-1), var(--ecert-navy-0) 64%);
                color: var(--ecert-paper);
                font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
                box-shadow: 0 26px 70px rgb(0 0 0 / 0.35);
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .ecert-frame[data-accent='gold'] {
                --ecert-accent-1: #7c5a1d;
                --ecert-accent-2: #cfa74e;
                --ecert-accent-3: #f7e8b8;
            }

            .ecert-frame[data-accent='blue'] {
                --ecert-accent-1: oklch(42% 0.15 258);
                --ecert-accent-2: oklch(64% 0.17 258);
                --ecert-accent-3: oklch(89% 0.07 258);
            }

            .ecert-art {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
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

            .ecert-wordmark {
                font-size: 2.3cqw;
                font-weight: 800;
                letter-spacing: 0.2em;
                line-height: 1;
                color: var(--ecert-paper);
            }

            .ecert-title {
                margin: 0;
                font-size: var(--ecert-title-size, 3.2cqw);
                font-weight: 700;
                letter-spacing: 0.15em;
                line-height: 1.1;
                text-transform: uppercase;
                color: var(--ecert-accent-3);
                background: linear-gradient(
                    100deg,
                    var(--ecert-accent-1) 0%,
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
                gap: 1.9cqw;
            }

            .ecert-signatures {
                display: grid;
                width: 100%;
                grid-template-columns: 1fr auto 1fr;
                align-items: end;
                gap: 3cqw;
            }

            .ecert-signature {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.55cqw;
            }

            .ecert-signature-rule {
                display: block;
                width: 100%;
                max-width: 24cqw;
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

            /* Print ------------------------------------------------------ */
            #ecert-print-portal {
                display: none;
            }

            @media print {
                @page {
                    size: A4 landscape;
                    margin: 0;
                }

                html.ecert-printing,
                html.ecert-printing body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #fff !important;
                }

                html.ecert-printing body > *:not(#ecert-print-portal) {
                    display: none !important;
                }

                html.ecert-printing #ecert-print-portal {
                    display: block;
                }

                #ecert-print-portal .ecert-frame {
                    width: 297mm;
                    height: 210mm;
                    aspect-ratio: auto;
                    border-radius: 0;
                    box-shadow: none;
                }
            }
        </style>
    @endverbatim

    <div class="ecert-layout">
        <div class="ecert-form">
            {{ $this->form }}
        </div>

        <div class="ecert-preview">
            <p class="ecert-preview-label">
                <span>Live preview</span>
                <span class="ecert-preview-hint">A4 landscape &middot; prints at full bleed</span>
            </p>

            <div data-ecert-source>
                @include('filament.pages.partials.certificate', ['c' => $this->data ?? []])
            </div>
        </div>
    </div>

    @script
        <script>
            // Printing straight from the admin layout would carry the sidebar and
            // form onto the page, so a clone of the certificate is lifted into a
            // body-level portal for the duration of the print job. This runs for
            // the toolbar button and for the browser's own Ctrl/Cmd+P.
            const portalId = 'ecert-print-portal'

            if (! document.getElementById(portalId)) {
                const portal = document.createElement('div')
                portal.id = portalId
                document.body.appendChild(portal)
            }

            if (! window.ecertPrintBound) {
                window.ecertPrintBound = true

                window.addEventListener('beforeprint', () => {
                    const source = document.querySelector('[data-ecert-source]')
                    const portal = document.getElementById(portalId)

                    if (! source || ! portal) {
                        return
                    }

                    const clone = source.cloneNode(true)

                    // The clone carries a second copy of the certificate's SVG
                    // ids. url(#...) resolves to the first match in the
                    // document, which is the hidden original, so the ornament
                    // would paint as nothing. Namespace the copy's own ids and
                    // repoint its references at them.
                    const suffix = '-print'

                    clone.querySelectorAll('[id]').forEach((element) => {
                        element.id = element.id + suffix
                    })

                    clone.querySelectorAll('*').forEach((element) => {
                        Array.from(element.attributes).forEach((attribute) => {
                            if (attribute.value.includes('url(#')) {
                                element.setAttribute(
                                    attribute.name,
                                    attribute.value.replace(/url\(#([^)]+)\)/g, 'url(#$1' + suffix + ')'),
                                )
                            }
                        })
                    })

                    portal.replaceChildren(clone)
                    document.documentElement.classList.add('ecert-printing')
                })

                window.addEventListener('afterprint', () => {
                    document.getElementById(portalId)?.replaceChildren()
                    document.documentElement.classList.remove('ecert-printing')
                })
            }
        </script>
    @endscript
</x-filament-panels::page>
