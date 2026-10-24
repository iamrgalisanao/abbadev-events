{{-- One signatory: the signature (when there is one) resting on a rule, then
     the name and role. Used by the signatories layout for the founder and the
     guests alike. --}}
<div class="ecert-signature">
    @if (filled($url ?? null))
        <span class="ecert-signature-slot">
            <img
                class="ecert-signature-mark"
                src="{{ $url }}"
                @if (filled($style ?? null)) style="{{ $style }}" @endif
                alt=""
            />
        </span>
    @else
        <span class="ecert-signature-slot" aria-hidden="true"></span>
    @endif
    <span class="ecert-signature-rule" aria-hidden="true"></span>
    <p class="ecert-signature-name">{{ filled($name ?? null) ? $name : ' ' }}</p>
    <p class="ecert-signature-role">{{ $role ?? '' }}</p>
</div>
