{{--
    Shared form fields for create / edit.
    Expects: $profile (Profile|null), $errors (MessageBag)
    Alpine context: profileForm() — provides cityQuery, cityId, cityResults, cityOpen
--}}
@php
    $pfx      = $profile ? 'edit_' . $profile->uuid . '_' : 'new_';
    $initDate = old('birth_date', $profile?->birth_date?->format('Y-m-d')) ?? '';
@endphp

<div class="col-stack-lg">

    {{-- Name row --}}
    <div class="form-row-2">
        <div>
            <label class="form-label-light">First name <span style="color:#dc2626">*</span></label>
            @error('first_name')
            <div class="field-error-sm">{{ $message }}</div>
            @enderror
            <input type="text" name="first_name" value="{{ old('first_name', $profile?->first_name) }}" required
                   class="form-input-sm" style="border-color:{{ $errors->has('first_name') ? '#dc2626' : 'var(--theme-border)' }}"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
        </div>
        <div>
            <label class="form-label-light">Last name</label>
            <input type="text" name="last_name" value="{{ old('last_name', $profile?->last_name) }}"
                   class="form-input-sm"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
        </div>
    </div>

    {{-- Gender --}}
    <div>
        <label class="form-label-light">Gender <span style="color:#dc2626">*</span></label>
        @error('gender')
        <div class="field-error-sm">{{ $message }}</div>
        @enderror
        <select name="gender" required
                class="form-select" style="border-color:{{ $errors->has('gender') ? '#dc2626' : 'var(--theme-border)' }}">
            <option value="">— Select —</option>
            <option value="female" {{ old('gender', $profile?->gender?->value) === 'female' ? 'selected' : '' }}>Female</option>
            <option value="male"   {{ old('gender', $profile?->gender?->value) === 'male'   ? 'selected' : '' }}>Male</option>
            <option value="other"  {{ old('gender', $profile?->gender?->value) === 'other'  ? 'selected' : '' }}>Other</option>
        </select>
    </div>

    {{-- Birth date + time --}}
    <div class="form-row-2">

        {{-- Custom date picker --}}
        <div>
            <label class="form-label-light">Birth date <span style="color:#dc2626">*</span></label>
            @error('birth_date')
            <div class="field-error-sm">{{ $message }}</div>
            @enderror

            <div x-data="datePicker('{{ $initDate }}')" @click.outside="open=false" style="position:relative">

                {{-- Hidden input for form submission --}}
                <input type="hidden" name="birth_date" :value="hiddenValue">

                {{-- Trigger --}}
                <div @click="open=!open"
                     :style="`width:100%;box-sizing:border-box;background:var(--theme-raised);border:1px solid ${open ? '#6a329f' : '{{ $errors->has('birth_date') ? '#dc2626' : 'var(--theme-border)' }}'};border-radius:0.35rem;padding:0.45rem 0.6rem;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between`">
                    <span :style="selected ? 'color:var(--theme-text)' : 'color:var(--theme-muted)'"
                          x-text="displayValue || 'Select date…'"></span>
                    <span style="font-size:0.8rem;color:var(--theme-muted)">📅</span>
                </div>

                {{-- Calendar dropdown --}}
                <div x-show="open" x-cloak
                     style="position:absolute;z-index:300;top:calc(100% + 4px);left:0;min-width:260px;background:var(--theme-card);border:1px solid var(--theme-border);border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,0.15);padding:0.75rem;user-select:none">

                    {{-- ── Day view ── --}}
                    <div x-show="view==='days'">

                        {{-- Header --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.6rem">
                            <button type="button" @click="prevMonth()"
                                    style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--theme-muted);padding:0.15rem 0.4rem;border-radius:4px"
                                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">‹</button>
                            <button type="button" @click="view='months'"
                                    style="background:none;border:none;cursor:pointer;font-size:0.85rem;font-weight:600;color:#6a329f;padding:0.2rem 0.5rem;border-radius:4px"
                                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''"
                                    x-text="monthName + ' ' + year"></button>
                            <button type="button" @click="nextMonth()"
                                    style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--theme-muted);padding:0.15rem 0.4rem;border-radius:4px"
                                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">›</button>
                        </div>

                        {{-- Weekday headers --}}
                        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:0.25rem">
                            <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">
                                <div x-text="d" style="text-align:center;font-size:0.7rem;color:var(--theme-muted);padding:0.15rem 0"></div>
                            </template>
                        </div>

                        {{-- Day grid --}}
                        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                            <template x-for="(d, i) in daysInGrid" :key="i">
                                <button type="button"
                                        @click="d && selectDay(d)"
                                        x-text="d || ''"
                                        :disabled="!d"
                                        :style="`width:100%;aspect-ratio:1;border:none;border-radius:50%;font-size:0.8rem;cursor:${d ? 'pointer' : 'default'};
                                            background:${isSelected(d) ? '#6a329f' : 'transparent'};
                                            color:${isSelected(d) ? '#fff' : isToday(d) ? '#6a329f' : 'var(--theme-text)'};
                                            font-weight:${isSelected(d) || isToday(d) ? '600' : '400'};
                                            opacity:${d ? '1' : '0'}`"
                                        onmouseover="if(this.dataset.d) this.style.background='var(--theme-raised)'"
                                        onmouseout="if(!this.classList.contains('sel')) this.style.background=''"
                                        :data-d="d">
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- ── Month / Year view ── --}}
                    <div x-show="view==='months'">

                        {{-- Back + year navigation --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
                            <button type="button" @click="view='days'"
                                    style="background:none;border:none;cursor:pointer;font-size:0.78rem;color:#6a329f;padding:0.2rem 0.4rem;border-radius:4px"
                                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">← Back</button>
                            <div style="display:flex;align-items:center;gap:0.4rem">
                                <button type="button" @click="prevYear()"
                                        style="background:none;border:none;cursor:pointer;font-size:1rem;color:var(--theme-muted);padding:0.1rem 0.35rem;border-radius:4px"
                                        onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">‹</button>
                                <button type="button" @click="yearStart=Math.floor(year/12)*12; view='years'"
                    style="background:none;border:none;cursor:pointer;font-size:0.9rem;font-weight:600;color:#6a329f;padding:0.2rem 0.5rem;border-radius:4px;min-width:2.8rem;text-align:center"
                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''"
                    x-text="year"></button>
                                <button type="button" @click="nextYear()"
                                        style="background:none;border:none;cursor:pointer;font-size:1rem;color:var(--theme-muted);padding:0.1rem 0.35rem;border-radius:4px"
                                        onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">›</button>
                            </div>
                            <div style="width:3rem"></div>
                        </div>

                        {{-- Month grid 3×4 --}}
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.35rem">
                            <template x-for="(m, i) in months" :key="i">
                                <button type="button" @click="selectMonth(i)" x-text="m"
                                        :style="`padding:0.4rem;border:none;border-radius:6px;font-size:0.82rem;cursor:pointer;
                                            background:${isCurrentMonth(i) ? '#6a329f' : 'var(--theme-raised)'};
                                            color:${isCurrentMonth(i) ? '#fff' : 'var(--theme-text)'};
                                            font-weight:${isCurrentMonth(i) ? '600' : '400'}`">
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- ── Year view ── --}}
                    <div x-show="view==='years'">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
                            <button type="button" @click="view='months'"
                                    style="background:none;border:none;cursor:pointer;font-size:0.78rem;color:#6a329f;padding:0.2rem 0.4rem;border-radius:4px"
                                    onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">← Back</button>
                            <div style="display:flex;align-items:center;gap:0.4rem">
                                <button type="button" @click="prevYears()"
                                        style="background:none;border:none;cursor:pointer;font-size:1rem;color:var(--theme-muted);padding:0.1rem 0.35rem;border-radius:4px"
                                        onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">‹</button>
                                <span x-text="yearStart + ' – ' + (yearStart + 11)"
                                      style="font-size:0.82rem;font-weight:600;color:var(--theme-text);min-width:6rem;text-align:center"></span>
                                <button type="button" @click="nextYears()"
                                        style="background:none;border:none;cursor:pointer;font-size:1rem;color:var(--theme-muted);padding:0.1rem 0.35rem;border-radius:4px"
                                        onmouseover="this.style.background='var(--theme-raised)'" onmouseout="this.style.background=''">›</button>
                            </div>
                            <div style="width:3rem"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.35rem">
                            <template x-for="y in yearRange" :key="y">
                                <button type="button" @click="selectYear(y)" x-text="y"
                                        :style="`padding:0.4rem;border:none;border-radius:6px;font-size:0.82rem;cursor:pointer;
                                            background:${isCurrentYear(y) ? '#6a329f' : 'var(--theme-raised)'};
                                            color:${isCurrentYear(y) ? '#fff' : 'var(--theme-text)'};
                                            font-weight:${isCurrentYear(y) ? '600' : '400'}`">
                                </button>
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Birth time --}}
        @php
            $initTime = old('birth_time', $profile?->birth_time ? substr($profile->birth_time, 0, 5) : '');
            $initHour = $initTime ? substr($initTime, 0, 2) : '';
            $initMin  = $initTime ? substr($initTime, 3, 2) : '';
        @endphp
        <div x-data="{ hour: '{{ $initHour }}', minute: '{{ $initMin }}' }">
            <label class="form-label-light">Birth time <span style="font-size:0.72rem;font-weight:400">(optional)</span></label>
            @error('birth_time')
            <div class="field-error-sm">{{ $message }}</div>
            @enderror
            <input type="hidden" name="birth_time" :value="hour && minute !== '' ? hour + ':' + minute : ''">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.4rem">
                <select x-model="hour"
                        @change="window.dispatchEvent(new CustomEvent('birth-data-change'))"
                        class="form-select" style="padding:0.5rem 0.6rem;font-size:0.92rem;text-align:center"
                        onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
                    <option value="">HH</option>
                    @for($h = 0; $h < 24; $h++)
                        <option value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
                <select x-model="minute"
                        @change="window.dispatchEvent(new CustomEvent('birth-data-change'))"
                        class="form-select" style="padding:0.5rem 0.6rem;font-size:0.92rem;text-align:center"
                        onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
                    <option value="">MM</option>
                    @for($m = 0; $m < 60; $m++)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <p class="form-hint">Without an exact birth time, horoscopes will be incomplete — houses, Ascendant, and MC won't be calculated.</p>
        </div>
    </div>

    @if($profile)
    {{-- Birth data change warning (edit only) --}}
    <div x-data="{ birthChanged: false }" @birth-data-change.window="birthChanged = true">
        <div x-show="birthChanged" x-cloak
             style="font-size:0.78rem;color:#92400e;background:rgba(251,191,36,0.12);border:1px solid rgba(251,191,36,0.4);border-radius:0.35rem;padding:0.5rem 0.75rem;line-height:1.5">
            {{ __('ui.stellar_profiles.birth_data_warn') }}
        </div>
    </div>
    @endif

    {{-- Birth city autocomplete --}}
    <div>
        <label class="form-label-light">Birth city <span style="font-size:0.72rem;font-weight:400">(optional, needed for exact houses)</span></label>
        @error('birth_city_id')
        <div class="field-error-sm">{{ $message }}</div>
        @enderror
        <div style="position:relative">
            <input type="text"
                   x-model="cityQuery"
                   @input.debounce.300ms="searchCity()"
                   @keydown.escape="cityOpen = false"
                   placeholder="Start typing a city…"
                   autocomplete="off"
                   class="form-input-sm" style="border-color:{{ $errors->has('birth_city_id') ? '#dc2626' : 'var(--theme-border)' }}"
                   onfocus="this.style.borderColor='#6a329f'" onblur="this.style.borderColor='var(--theme-border)'">
            <input type="hidden" name="birth_city_id" :value="cityId">

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

@once
@push('scripts')
<script>
function datePicker(initVal) {
    const MONTHS = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];
    const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun',
                          'Jul','Aug','Sep','Oct','Nov','Dec'];

    const initDate = initVal ? new Date(initVal + 'T12:00:00') : null;

    const curYear = initDate ? initDate.getFullYear() : new Date().getFullYear();

    return {
        open:      false,
        view:      'days',
        selected:  initDate,
        viewDate:  initDate ? new Date(initDate) : new Date(),
        months:    MONTHS_SHORT,
        yearStart: Math.floor(curYear / 12) * 12,

        get displayValue() {
            if (!this.selected) return '';
            const d = this.selected;
            return String(d.getDate()).padStart(2,'0') + ' / ' +
                   String(d.getMonth()+1).padStart(2,'0') + ' / ' +
                   d.getFullYear();
        },
        get hiddenValue() {
            if (!this.selected) return '';
            const d = this.selected;
            return d.getFullYear() + '-' +
                   String(d.getMonth()+1).padStart(2,'0') + '-' +
                   String(d.getDate()).padStart(2,'0');
        },
        get monthName() { return MONTHS[this.viewDate.getMonth()]; },
        get year()      { return this.viewDate.getFullYear(); },
        get daysInGrid() {
            const y = this.viewDate.getFullYear(), m = this.viewDate.getMonth();
            const startDow = (new Date(y, m, 1).getDay() + 6) % 7; // Mon=0
            const total    = new Date(y, m+1, 0).getDate();
            const grid = [];
            for (let i = 0; i < startDow; i++) grid.push(null);
            for (let d = 1; d <= total; d++) grid.push(d);
            return grid;
        },

        prevMonth()  { this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth()-1, 1); },
        nextMonth()  { this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth()+1, 1); },
        prevYear()   { this.viewDate = new Date(this.viewDate.getFullYear()-1, this.viewDate.getMonth(), 1); },
        nextYear()   { this.viewDate = new Date(this.viewDate.getFullYear()+1, this.viewDate.getMonth(), 1); },
        prevYears()  { this.yearStart -= 12; },
        nextYears()  { this.yearStart += 12; },
        selectYear(y){ this.viewDate = new Date(y, this.viewDate.getMonth(), 1); this.yearStart = Math.floor(y/12)*12; this.view = 'months'; },
        get yearRange(){ return Array.from({length:12}, (_,i) => this.yearStart + i); },
        isCurrentYear(y){ return this.viewDate.getFullYear() === y; },

        selectDay(d) {
            this.selected = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), d);
            this.open = false;
            window.dispatchEvent(new CustomEvent('birth-data-change'));
        },
        selectMonth(m) {
            this.viewDate = new Date(this.viewDate.getFullYear(), m, 1);
            this.view = 'days';
        },

        isSelected(d) {
            if (!d || !this.selected) return false;
            return this.selected.getDate() === d &&
                   this.selected.getMonth() === this.viewDate.getMonth() &&
                   this.selected.getFullYear() === this.viewDate.getFullYear();
        },
        isToday(d) {
            if (!d) return false;
            const t = new Date();
            return t.getDate() === d &&
                   t.getMonth() === this.viewDate.getMonth() &&
                   t.getFullYear() === this.viewDate.getFullYear();
        },
        isCurrentMonth(m) {
            return this.viewDate.getMonth() === m;
        },
    };
}
</script>
@endpush
@endonce
