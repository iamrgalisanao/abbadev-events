<x-filament-panels::page>
    @include('certificates.partials.styles')

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
                @include('certificates.partials.certificate', [
                    'c' => $this->data ?? [],
                    'verificationUrl' => $this->getVerificationUrl(),
                    'signatureUrl' => $this->getSignatureUrl(),
                ])
            </div>
        </div>
    </div>

    @script
        <script>
            // Printing straight from the admin layout would carry the sidebar
            // and form onto the page, so the certificate is lifted into a
            // body-level portal for the duration of the print job and put back
            // afterwards. This runs for the toolbar button and for the browser's
            // own Ctrl/Cmd+P.
            const portalId = 'ecert-print-portal'

            if (! document.getElementById(portalId)) {
                const portal = document.createElement('div')
                portal.id = portalId
                document.body.appendChild(portal)
            }

            if (! window.ecertPrintBound) {
                window.ecertPrintBound = true

                let moved = null
                let placeholder = null

                // The certificate is moved into the portal, not copied into it.
                // A copy made inside beforeprint is a set of brand-new nodes,
                // and its images have to load and decode before Chrome takes the
                // print snapshot - which they lose the race to, so every <img>
                // came out blank while the inline-SVG QR beside them printed
                // fine. Moving the nodes that are already painted avoids the
                // decode entirely, and with only one copy in the document there
                // are no duplicate SVG ids to reconcile either.
                window.addEventListener('beforeprint', () => {
                    const source = document.querySelector('[data-ecert-source]')
                    const portal = document.getElementById(portalId)

                    if (! source || ! portal || moved) {
                        return
                    }

                    placeholder = document.createComment('ecert-print-placeholder')
                    source.parentNode.insertBefore(placeholder, source)
                    portal.appendChild(source)
                    moved = source

                    document.documentElement.classList.add('ecert-printing')
                })

                window.addEventListener('afterprint', () => {
                    if (moved && placeholder && placeholder.parentNode) {
                        placeholder.parentNode.insertBefore(moved, placeholder)
                        placeholder.remove()
                    }

                    moved = null
                    placeholder = null

                    document.documentElement.classList.remove('ecert-printing')
                })
            }
        </script>
    @endscript
</x-filament-panels::page>
