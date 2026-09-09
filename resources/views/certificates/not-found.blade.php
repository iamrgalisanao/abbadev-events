@extends('certificates.layout')

@section('title', 'Credential not found')

@push('styles')
    @verbatim
        <style>
            .nf-card {
                margin: 48px 0 0;
                padding: 32px 26px;
                border: 1px solid var(--pv-rule);
                border-radius: 14px;
                background: var(--pv-card);
                text-align: center;
            }

            .nf-card h1 {
                margin: 0 0 10px;
                font-size: 26px;
                color: var(--pv-ink);
            }

            .nf-card p {
                margin: 0 auto;
                max-width: 44ch;
                color: var(--pv-muted);
            }

            .nf-id {
                display: inline-block;
                margin: 20px 0 0;
                padding: 8px 14px;
                border-radius: 8px;
                background: var(--pv-raised);
                font-family: 'IBM Plex Mono', ui-monospace, monospace;
                font-size: 14px;
                color: var(--pv-ink);
                overflow-wrap: anywhere;
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

        <div class="nf-card">
            <h1>Credential not found</h1>
            <p>
                No certificate with this ID has been issued by ABBADev. Check the ID for typos, or ask the
                holder to re-share the verification link from their certificate.
            </p>
            <span class="nf-id">{{ $credential }}</span>
        </div>
    </main>
@endsection
