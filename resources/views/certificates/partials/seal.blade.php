{{-- Gold medallion that sits between the two signature blocks. --}}
<svg class="ecert-seal" viewBox="0 0 100 100" role="img" aria-label="{{ $label }} {{ $year }}">
    <circle cx="50" cy="50" r="47" fill="url(#ecertSweep)" />
    <circle
        cx="50" cy="50" r="43"
        fill="none" stroke="var(--ecert-navy-0)" stroke-width="4"
        stroke-dasharray="3 5.2" opacity="0.75"
    />
    <circle cx="50" cy="50" r="38" fill="var(--ecert-navy-0)" />
    <circle cx="50" cy="50" r="33.5" fill="none" stroke="url(#ecertSweep)" stroke-width="1.1" />

    <text class="ecert-seal-label" x="50" y="46">{{ $label }}</text>
    <line x1="33" y1="53" x2="67" y2="53" stroke="url(#ecertSweep)" stroke-width="0.9" opacity="0.7" />
    <text class="ecert-seal-year" x="50" y="66">{{ $year }}</text>
</svg>
