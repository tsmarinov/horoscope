import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ── Theme manager ────────────────────────────────────────────────────────
Alpine.data('themeManager', () => ({
    theme: null,
    menuOpen: false,
    profileOpen: false,

    init() {
        const saved  = localStorage.getItem('stellar-theme');
        const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        this.theme   = saved || system;

        // Keep in sync if system preference changes and user hasn't overridden
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('stellar-theme')) {
                this.theme = e.matches ? 'dark' : 'light';
            }
        });
    },

    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('stellar-theme', this.theme);
        this.$nextTick(() => applyEmojiFilter());
    },

    closeMenu() {
        this.menuOpen = false;
    },
}));

// ── Stellar Profile form with city autocomplete ───────────────────────────
Alpine.data('profileForm', (initCityName = '', initCityId = null, initOpen = false) => ({
    open:        initOpen,
    cityQuery:   initCityName,
    cityResults: [],
    cityId:      initCityId,
    cityOpen:    false,

    async searchCity() {
        if (this.cityQuery.length < 2) { this.cityResults = []; this.cityOpen = false; return; }
        const res = await fetch('/api/cities?q=' + encodeURIComponent(this.cityQuery));
        this.cityResults = await res.json();
        this.cityOpen = this.cityResults.length > 0;
    },

    selectCity(city) {
        this.cityId    = city.id;
        this.cityQuery = city.name + ' (' + city.country_code + ')';
        this.cityResults = [];
        this.cityOpen    = false;
        window.dispatchEvent(new CustomEvent('birth-data-change'));
    },

    clearCity() {
        this.cityId    = null;
        this.cityQuery = '';
        window.dispatchEvent(new CustomEvent('birth-data-change'));
    },
}));

Alpine.start();

// ── Twemoji — parse after DOM ready ──────────────────────────────────────
const EMOJI_FILTER_LIGHT = 'grayscale(1) sepia(1) saturate(4) hue-rotate(242deg) brightness(0.75)';
const EMOJI_FILTER_DARK  = 'grayscale(1) sepia(1) saturate(4) hue-rotate(242deg) brightness(1.4) opacity(0.85)';

function applyEmojiFilter() {
    const dark = document.documentElement.dataset.theme === 'dark';
    const globalFilter = dark ? EMOJI_FILTER_DARK : EMOJI_FILTER_LIGHT;

    document.querySelectorAll('img.emoji').forEach(img => {
        // Check if inside a variant preview container
        const variant = img.closest('[data-emoji-filter]');
        if (variant) {
            const filters = JSON.parse(variant.dataset.emojiFilter);
            img.style.filter = dark ? (filters.dark || filters.light) : filters.light;
        } else {
            img.style.filter = globalFilter;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.twemoji) {
        twemoji.parse(document.body, { folder: 'svg', ext: '.svg' });
        applyEmojiFilter();
    }
});
