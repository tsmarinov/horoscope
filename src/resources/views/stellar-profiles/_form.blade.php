{{--
    Shared form fields for create / edit.
    Expects: $profile (Profile|null), $errors (MessageBag)
    Alpine context: profileForm() — provides cityQuery, cityId, cityResults, cityOpen
--}}
@php
    $pfx = $profile ? 'edit_' . $profile->id . '_' : 'new_';
@endphp

<div style="display:flex;flex-direction:column;gap:0.75rem">

    {{-- Name row --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
        <div>
            <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">First name <span style="color:#dc2626">*</span></label>
            @error('first_name')
            <div style="font-size:0.73rem;color:#dc2626;margin-bottom:0.2rem">{{ $message }}</div>
            @enderror
            <input type="text" name="first_name" value="{{ old('first_name', $profile?->first_name) }}" required
                   style="width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid {{ $errors->has('first_name') ? '#dc2626' : 'var(--theme-border)' }};border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
        </div>
        <div>
            <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">Last name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $profile?->last_name) }}"
                   style="width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
        </div>
    </div>

    {{-- Gender --}}
    <div>
        <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">Gender <span style="color:#dc2626">*</span></label>
        @error('gender')
        <div style="font-size:0.73rem;color:#dc2626;margin-bottom:0.2rem">{{ $message }}</div>
        @enderror
        <select name="gender" required
                style="width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid {{ $errors->has('gender') ? '#dc2626' : 'var(--theme-border)' }};border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none;appearance:none">
            <option value="">— Select —</option>
            <option value="female" {{ old('gender', $profile?->gender?->value) === 'female' ? 'selected' : '' }}>Female</option>
            <option value="male"   {{ old('gender', $profile?->gender?->value) === 'male'   ? 'selected' : '' }}>Male</option>
            <option value="other"  {{ old('gender', $profile?->gender?->value) === 'other'  ? 'selected' : '' }}>Other</option>
        </select>
    </div>

    {{-- Birth date + time --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem">
        <div>
            <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">Birth date <span style="color:#dc2626">*</span></label>
            @error('birth_date')
            <div style="font-size:0.73rem;color:#dc2626;margin-bottom:0.2rem">{{ $message }}</div>
            @enderror
            <input type="date" name="birth_date" value="{{ old('birth_date', $profile?->birth_date?->format('Y-m-d')) }}" required
                   style="width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid {{ $errors->has('birth_date') ? '#dc2626' : 'var(--theme-border)' }};border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
        </div>
        @php
            $initTime = old('birth_time', $profile?->birth_time ? substr($profile->birth_time, 0, 5) : '');
            $initHour = $initTime ? substr($initTime, 0, 2) : '';
            $initMin  = $initTime ? substr($initTime, 3, 2) : '';
        @endphp
        <div x-data="{ hour: '{{ $initHour }}', minute: '{{ $initMin }}' }">
            <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">Birth time <span style="font-size:0.72rem;font-weight:400">(optional)</span></label>
            @error('birth_time')
            <div style="font-size:0.73rem;color:#dc2626;margin-bottom:0.2rem">{{ $message }}</div>
            @enderror
            {{-- Hidden input combines hour + minute --}}
            <input type="hidden" name="birth_time" :value="hour && minute !== '' ? hour + ':' + minute : ''">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.4rem">
                <select x-model="hour"
                        style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.5rem 0.6rem;font-size:0.92rem;color:var(--theme-text);outline:none;appearance:none;text-align:center"
                        onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
                    <option value="">HH</option>
                    @for($h = 0; $h < 24; $h++)
                        <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
                <select x-model="minute"
                        style="width:100%;background:var(--theme-raised);border:1px solid var(--theme-border);border-radius:0.35rem;padding:0.5rem 0.6rem;font-size:0.92rem;color:var(--theme-text);outline:none;appearance:none;text-align:center"
                        onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
                    <option value="">MM</option>
                    @for($m = 0; $m < 60; $m++)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <p style="font-size:0.72rem;color:var(--theme-muted);margin-top:0.3rem;line-height:1.4">Without an exact birth time, horoscopes will be incomplete — houses, Ascendant, and MC won't be calculated.</p>
        </div>
    </div>

    {{-- Birth city autocomplete --}}
    <div>
        <label style="display:block;font-size:0.78rem;color:var(--theme-muted);margin-bottom:0.25rem">Birth city <span style="font-size:0.72rem;font-weight:400">(optional, needed for exact houses)</span></label>
        @error('birth_city_id')
        <div style="font-size:0.73rem;color:#dc2626;margin-bottom:0.2rem">{{ $message }}</div>
        @enderror
        <div style="position:relative">
            <input type="text"
                   x-model="cityQuery"
                   @input.debounce.300ms="searchCity()"
                   @keydown.escape="cityOpen = false"
                   placeholder="Start typing a city…"
                   autocomplete="off"
                   style="width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid {{ $errors->has('birth_city_id') ? '#dc2626' : 'var(--theme-border)' }};border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;color:var(--theme-text);outline:none"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            <input type="hidden" name="birth_city_id" :value="cityId">

            {{-- Dropdown --}}
            <div x-show="cityOpen" x-cloak
                 style="position:absolute;z-index:50;top:100%;left:0;right:0;margin-top:0.2rem;background:var(--theme-card);border:1px solid var(--theme-border);border-radius:0.35rem;box-shadow:0 4px 16px rgba(0,0,0,0.12);overflow:hidden">
                <template x-for="city in cityResults" :key="city.id">
                    <div @click="selectCity(city)"
                         style="padding:0.5rem 0.75rem;font-size:0.83rem;cursor:pointer;color:var(--theme-text);border-bottom:1px solid var(--theme-border)"
                         onmouseover="this.style.background='rgba(106,50,159,0.07)'"
                         onmouseout="this.style.background=''"
                         x-text="city.name + ' (' + city.country_code + ')'">
                    </div>
                </template>
            </div>
        </div>
        <div x-show="cityId" x-cloak style="margin-top:0.3rem">
            <button type="button" @click="clearCity()"
                    style="font-size:0.73rem;color:var(--theme-muted);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline">
                Clear city
            </button>
        </div>
    </div>

</div>
