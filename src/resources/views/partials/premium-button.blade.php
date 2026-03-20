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
    $ctx       = $context ?? 'retrograde';
    $premBase  = 'ui.' . $ctx . '.premium.';
    $premShow   = \Lang::has($premBase . 'button_show')  ? __($premBase . 'button_show')  : __('ui.retrograde.premium.button_show');
    $premLocked = \Lang::has($premBase . 'button_locked') ? __($premBase . 'button_locked') : __('ui.retrograde.premium.button_locked');
    $premTitle  = \Lang::has($premBase . 'dialog_title') ? __($premBase . 'dialog_title') : __('ui.retrograde.premium.dialog_title');
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
    <button disabled class="btn-premium-disabled">
        {{ $premShow }}
    </button>
    @else
    <button @click="openDialog()"
            data-premium-btn
            class="btn-premium"
            onmouseover="this.style.background='#6a329f';this.style.color='#fff'"
            onmouseout="this.style.background='none';this.style.color='#6a329f'">
        {{ $premShow }}
    </button>
    @endif

    {{-- Confirm modal --}}
    <div x-show="open" x-cloak
         class="premium-modal-wrap"
         @click.self="open = false">

        <div class="premium-modal-overlay"></div>

        <div class="premium-modal-box"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="premium-modal-title">
                {{ $premTitle }}
            </div>

            <p class="premium-modal-text">
                {{ __('ui.retrograde.premium.dialog_body') }}
            </p>
            <p class="premium-modal-text-last">
                {{ __('ui.retrograde.premium.dialog_remaining_prefix') }}
                <strong x-text="remaining" class="premium-accent"></strong>
                {{ __('ui.retrograde.premium.dialog_remaining_of') }}
                <span x-text="limit"></span>
                {{ __('ui.retrograde.premium.dialog_remaining_suffix') }}
            </p>

            <div class="premium-modal-actions">
                <button @click="open = false" class="btn-modal-cancel">
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
<button disabled class="btn-premium-disabled-soft">
    {{ $premLocked }}
</button>
@endif

@endif
@endauth
