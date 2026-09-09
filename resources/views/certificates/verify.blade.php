@extends('certificates.layout')

@section('title', $certificate->recipient_name . ' — certificate verification')

@push('styles')
    @verbatim
        <style>
            /* Verification panel ------------------------------------------ */
            .cv-overlay {
                position: fixed;
                inset: 0;
                z-index: 60;
                display: flex;
                align-items: flex-start;
                justify-content: center;
                padding: 24px 16px;
                overflow-y: auto;
                background: oklch(20% 0.045 250 / 0.55);
                backdrop-filter: blur(3px);
            }

            .cv-overlay[hidden] {
                display: none;
            }

            .cv-panel {
                position: relative;
                width: 100%;
                max-width: 520px;
                margin: auto;
                padding: 28px 24px 26px;
                border-radius: 16px;
                background: var(--pv-card);
                box-shadow: 0 30px 80px oklch(0% 0 0 / 0.35);
            }

            .cv-close {
                position: absolute;
                top: 12px;
                right: 12px;
                width: 38px;
                height: 38px;
                border: 0;
                border-radius: 10px;
                background: transparent;
                color: var(--pv-muted);
                font-size: 22px;
                line-height: 1;
                cursor: pointer;
            }

            .cv-close:hover {
                background: var(--pv-raised);
                color: var(--pv-ink);
            }

            .cv-panel h2 {
                margin: 0;
                font-size: 26px;
                line-height: 1.2;
                text-align: center;
                color: var(--pv-ink);
            }

            .cv-lede {
                margin: 10px 0 24px;
                text-align: center;
                color: var(--pv-muted);
            }

            .cv-steps {
                display: grid;
                gap: 18px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .cv-step {
                display: grid;
                grid-template-columns: 26px minmax(0, 1fr);
                gap: 12px;
                animation: cv-rise 320ms cubic-bezier(0.16, 1, 0.3, 1) both;
            }

            @keyframes cv-rise {
                from {
                    opacity: 0;
                    transform: translateY(6px);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .cv-step {
                    animation: none;
                }

                .cv-spinner {
                    animation: none;
                }
            }

            .cv-mark {
                display: flex;
                width: 24px;
                height: 24px;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                margin-top: 3px;
                background: var(--pv-success);
                color: oklch(100% 0 0);
                font-size: 14px;
                font-weight: 700;
            }

            .cv-mark-fail {
                background: var(--pv-danger);
            }

            .cv-step h3 {
                margin: 0;
                font-size: 16px;
                color: var(--pv-ink);
            }

            .cv-step p {
                margin: 2px 0 0;
                font-size: 15px;
                color: var(--pv-muted);
            }

            .cv-step .cv-id {
                font-family: 'IBM Plex Mono', ui-monospace, monospace;
                overflow-wrap: anywhere;
            }

            .cv-pending {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-top: 18px;
                color: var(--pv-muted);
            }

            /* An author `display` beats the UA stylesheet's [hidden] rule, so
               the hidden state has to be restated here. */
            .cv-pending[hidden] {
                display: none;
            }

            .cv-spinner {
                width: 20px;
                height: 20px;
                border: 2px solid var(--pv-rule);
                border-top-color: var(--pv-accent);
                border-radius: 999px;
                animation: cv-spin 700ms linear infinite;
            }

            @keyframes cv-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .cv-result {
                margin: 24px 0 0;
                padding: 18px 20px;
                border-radius: 12px;
                text-align: center;
                animation: cv-rise 320ms cubic-bezier(0.16, 1, 0.3, 1) both;
            }

            .cv-result h3 {
                margin: 0 0 6px;
                font-size: 19px;
            }

            .cv-result p {
                margin: 0;
                font-size: 15px;
            }

            .cv-result-valid {
                background: var(--pv-success-soft);
                color: var(--pv-success);
            }

            .cv-result-invalid {
                background: var(--pv-danger-soft);
                color: var(--pv-danger);
            }
        </style>
    @endverbatim
@endpush

@section('content')
    <main class="pv-shell">
        <nav class="pv-crumbs" aria-label="Breadcrumb">
            <a href="https://abbadev.com">Home</a>
            <span aria-hidden="true">&raquo;</span>
            <span aria-current="page">Verification</span>
        </nav>

        <div class="pv-stage">
            @include('certificates.partials.certificate', [
                'c' => $certificate->toTemplateArray(),
                'verificationUrl' => $certificate->verificationUrl(),
            ])
        </div>

        <div class="pv-actions">
            <button type="button" class="pv-btn" data-verify-open>Verify authenticity</button>
            <a class="pv-btn pv-btn-primary" href="{{ $certificate->downloadUrl() }}">Download certificate</a>
        </div>

        <section class="pv-record">
            <h2>Record</h2>
            <dl>
                <div>
                    <dt>Recipient</dt>
                    <dd>{{ $certificate->recipient_name }}</dd>
                </div>
                @if (filled($certificate->activity_title))
                    <div>
                        <dt>Session</dt>
                        <dd>{{ $certificate->activity_title }}</dd>
                    </div>
                @endif
                @if ($certificate->issued_on)
                    <div>
                        <dt>Session date</dt>
                        <dd>{{ $certificate->issued_on->format('F j, Y') }}</dd>
                    </div>
                @endif
                <div>
                    <dt>Status</dt>
                    <dd>
                        <span class="pv-pill {{ $certificate->isActive() ? 'pv-pill-active' : 'pv-pill-revoked' }}">
                            {{ $certificate->status->getLabel() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Credential ID</dt>
                    <dd class="pv-mono">{{ $certificate->credential_id }}</dd>
                </div>
            </dl>
        </section>

        <p class="pv-foot">
            Issued {{ $certificate->created_at?->format('F j, Y') }}. This page is the authoritative record for
            this credential — anything that differs from what is shown here has not been issued by ABBADev.
        </p>
    </main>

    <div class="cv-overlay" hidden data-verify-overlay role="dialog" aria-modal="true" aria-labelledby="cv-heading">
        <div class="cv-panel">
            <button type="button" class="cv-close" data-verify-close aria-label="Close">&times;</button>
            <h2 id="cv-heading">Credential verification</h2>
            <p class="cv-lede">Checking this credential against ABBADev&rsquo;s records&hellip;</p>

            <ul class="cv-steps" data-verify-steps></ul>

            <div class="cv-pending" data-verify-pending>
                <span class="cv-spinner" aria-hidden="true"></span>
                <span>Running checks&hellip;</span>
            </div>

            <div data-verify-result></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const overlay = document.querySelector('[data-verify-overlay]')
            const stepsList = overlay.querySelector('[data-verify-steps]')
            const pending = overlay.querySelector('[data-verify-pending]')
            const result = overlay.querySelector('[data-verify-result]')
            const openButton = document.querySelector('[data-verify-open]')
            const closeButton = overlay.querySelector('[data-verify-close]')
            const endpoint = @json(route('certificates.checks', $certificate->credential_id))

            // Every check is answered by the server; the panel only paces how
            // they appear, so the audit reads as it runs rather than landing
            // as one block of already-decided text.
            const stepDelay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 800

            const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

            function renderStep(check) {
                const item = document.createElement('li')
                item.className = 'cv-step'

                const mark = document.createElement('span')
                mark.className = 'cv-mark' + (check.passed ? '' : ' cv-mark-fail')
                mark.setAttribute('aria-hidden', 'true')
                mark.textContent = check.passed ? '✓' : '✕'

                const body = document.createElement('div')
                const title = document.createElement('h3')
                title.textContent = check.title
                const detail = document.createElement('p')
                detail.textContent = check.detail
                body.append(title, detail)

                item.append(mark, body)
                stepsList.append(item)
            }

            function renderResult(payload) {
                const box = document.createElement('div')
                box.className = 'cv-result ' + (payload.valid ? 'cv-result-valid' : 'cv-result-invalid')

                const heading = document.createElement('h3')
                heading.textContent = payload.valid ? 'This is a valid credential' : 'This credential is not valid'

                const detail = document.createElement('p')
                detail.textContent = payload.summary

                box.append(heading, detail)
                result.append(box)
            }

            async function run() {
                stepsList.replaceChildren()
                result.replaceChildren()
                pending.hidden = false

                let payload

                try {
                    const response = await fetch(endpoint, { headers: { Accept: 'application/json' } })
                    payload = await response.json()
                } catch (error) {
                    payload = {
                        valid: false,
                        summary: 'The verification service could not be reached. Check your connection and try again.',
                        checks: [],
                    }
                }

                for (const check of payload.checks || []) {
                    await wait(stepDelay)
                    renderStep(check)
                }

                await wait(stepDelay)
                pending.hidden = true
                renderResult(payload)
            }

            function open() {
                overlay.hidden = false
                document.body.style.overflow = 'hidden'
                closeButton.focus()
                run()
            }

            function close() {
                overlay.hidden = true
                document.body.style.overflow = ''
                openButton.focus()
            }

            openButton.addEventListener('click', open)
            closeButton.addEventListener('click', close)
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    close()
                }
            })
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! overlay.hidden) {
                    close()
                }
            })
        })()
    </script>
@endpush
