{{--
    Premium Button Partial
    Usage: @include('partials.premium-button', ['context' => 'natal'])
    Emits: Alpine event 'premium-confirmed' when user confirms.

    When PREMIUM_ENABLED=false — nothing renders at all.
    When premium user    — active button + confirm dialog.
    When non-premium     — disabled locked button.
--}}
@guest
@if(config('premium.enabled'))
<a href="{{ route('register') }}" class="btn-register-unlock">
    🔒 {{ __('ui.retrograde.premium.register_to_unlock') }}
</a>
@endif
@endguest
@auth
@if(config('premium.enabled'))
@php
    $user      = auth()->user();
    $isPremium = $user->isPremium();
    $remaining = $isPremium ? $user->premiumRemaining() : 0;
    $limit     = config('premium.monthly_limit');
@endphp

@if($isPremium)
<div x-data="{
        open: false,
        loading: false,
        remaining: {{ $remaining }},
        limit: {{ $limit }},
        openDialog() {
            this.open = true;
            const self = this;
            fetch('{{ route('premium.remaining') }}', {
                headers: { 'Accept': 'application/json' },
            })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) { if (data) { self.remaining = data.remaining; self.limit = data.limit; } })
            .catch(function() {});
        },
        generate() {
            const self = this;
            self.loading = true;
            const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
            fetch('{{ route('premium.use') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function(r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function(data) {
                self.remaining = data.remaining;
                self.open = false;
                window.dispatchEvent(new CustomEvent('premium-confirmed', {
                    detail: { context: '{{ $context ?? 'natal' }}', remaining: data.remaining }
                }));
            })
            .catch(function(err) { console.error('premium error', err); })
            .finally(function() { self.loading = false; });
        }
    }">

    @if($generated ?? false)
    <button disabled
            style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 1rem;border-radius:6px;border:1px solid var(--theme-border);background:none;color:var(--theme-muted);font-size:0.82rem;cursor:not-allowed;font-family:inherit;opacity:0.45">
        {{ __('ui.retrograde.premium.button_show') }}
    </button>
    @else
    <button @click="openDialog()"
            data-premium-btn
            style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 1rem;border-radius:6px;border:1px solid #6a329f;background:none;color:#6a329f;font-size:0.82rem;cursor:pointer;font-family:inherit"
            onmouseover="this.style.background='#6a329f';this.style.color='#fff'"
            onmouseout="this.style.background='none';this.style.color='#6a329f'">
        {{ __('ui.retrograde.premium.button_show') }}
    </button>
    @endif

    {{-- Confirm modal --}}
    <div x-show="open" x-cloak
         style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem"
         @click.self="open = false">

        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.45);z-index:0"></div>

        <div style="position:relative;z-index:1;background:var(--theme-card);border-radius:12px;padding:1.5rem 1.75rem;max-width:360px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,0.25)"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div style="font-size:1rem;font-weight:600;color:var(--theme-text);margin-bottom:0.6rem">
                {{ __('ui.retrograde.premium.dialog_title') }}
            </div>

            <p style="font-size:0.85rem;color:var(--theme-muted);line-height:1.6;margin:0 0 0.4rem">
                {{ __('ui.retrograde.premium.dialog_body') }}
            </p>
            <p style="font-size:0.85rem;color:var(--theme-muted);line-height:1.6;margin:0 0 1.25rem">
                {{ __('ui.retrograde.premium.dialog_remaining_prefix') }}
                <strong x-text="remaining" style="color:#6a329f"></strong>
                {{ __('ui.retrograde.premium.dialog_remaining_of') }}
                <span x-text="limit"></span>
                {{ __('ui.retrograde.premium.dialog_remaining_suffix') }}
            </p>

            <div style="display:flex;gap:0.6rem;justify-content:flex-end">
                <button @click="open = false"
                        style="padding:0.4rem 1rem;border-radius:6px;border:1px solid var(--theme-border);background:none;color:var(--theme-muted);font-size:0.82rem;cursor:pointer;font-family:inherit">
                    {{ __('ui.retrograde.premium.cancel') }}
                </button>
                <button @click="generate()" :disabled="loading || remaining <= 0"
                        :style="`padding:0.4rem 1rem;border-radius:6px;border:none;background:#6a329f;color:#fff;font-size:0.82rem;font-family:inherit;cursor:${(loading || remaining <= 0) ? 'not-allowed' : 'pointer'};opacity:${(loading || remaining <= 0) ? '0.45' : '1'}`">
                    <span x-show="!loading">{{ __('ui.retrograde.premium.confirm') }}</span>
                    <span x-show="loading" x-cloak>...</span>
                </button>
            </div>
        </div>
    </div>

</div>

@else
{{-- Non-premium: show locked button --}}
<button disabled
        style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.45rem 1rem;border-radius:6px;border:1px solid var(--theme-border);background:none;color:var(--theme-muted);font-size:0.82rem;cursor:not-allowed;font-family:inherit;opacity:0.6">
    {{ __('ui.retrograde.premium.button_locked') }}
</button>
@endif

@endif
@endauth
