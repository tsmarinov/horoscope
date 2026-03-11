<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored weekday clothing short tips (1 sentence each).
 *
 * Section: weekday_clothing_short
 * Key format: {weekday}_venus_in_{sign}
 * 84 blocks: 7 days × 12 Venus signs
 */
class WeekdayClothingShortSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'weekday_clothing_short',
            'language'   => 'en',
            'variant'    => 1,
            'tone'       => 'neutral',
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'cost_usd'   => 0.0,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $this->blocks());

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('text_blocks')->upsert(
                $chunk,
                ['key', 'section', 'language', 'variant'],
                ['text', 'tone', 'updated_at']
            );
        }

        $this->command->info('Weekday clothing short tips seeded — ' . count($rows) . ' blocks.');
    }

    private function blocks(): array
    {
        return [

            // ── Monday (Moon · Silver, White, Green) ──────────────────────

            ['key' => 'monday_venus_in_aries',      'text' => 'Crisp white with a single red accent and a silver bracelet balances Monday\'s lunar softness with your bold instinct.'],
            ['key' => 'monday_venus_in_taurus',     'text' => 'Sage green or cream linen with a pearl or moonstone pendant is the natural Monday choice for you.'],
            ['key' => 'monday_venus_in_gemini',     'text' => 'Light grey over white with small silver hoops keeps Monday light and flexible.'],
            ['key' => 'monday_venus_in_cancer',     'text' => 'Soft silver-white with a moonstone pendant — Monday\'s energy and your instincts are completely aligned today.'],
            ['key' => 'monday_venus_in_leo',        'text' => 'A flowing white blouse with gold statement earrings lets the jewelry carry the drama while the palette stays lunar.'],
            ['key' => 'monday_venus_in_virgo',      'text' => 'Clean white or dove grey with a single silver ring or stud earrings — precise and perfectly in tune with Monday.'],
            ['key' => 'monday_venus_in_libra',      'text' => 'Soft white or ecru with rose gold jewelry hits Monday\'s silver palette with your instinct for harmony.'],
            ['key' => 'monday_venus_in_scorpio',    'text' => 'Deep charcoal or midnight blue with a silver chain or obsidian pendant works better for you than soft white on Monday.'],
            ['key' => 'monday_venus_in_sagittarius','text' => 'White base with one teal or green accent and a moonstone piece keeps Monday interesting without forcing its muted palette.'],
            ['key' => 'monday_venus_in_capricorn',  'text' => 'Structured white or pale grey blazer with silver studs or a pearl — professional and aligned with Monday\'s reflective energy.'],
            ['key' => 'monday_venus_in_aquarius',   'text' => 'White with an iridescent or electric blue accent and a distinctive silver piece gives Monday\'s palette your original stamp.'],
            ['key' => 'monday_venus_in_pisces',     'text' => 'Flowing white or pale sea-green with a moonstone or aquamarine pendant — Monday\'s dreamy energy is made for you.'],

            // ── Tuesday (Mars · Red, Orange) ──────────────────────────────

            ['key' => 'tuesday_venus_in_aries',      'text' => 'Bold red outfit with ruby or garnet jewelry — Tuesday is your natural home, go full Mars.'],
            ['key' => 'tuesday_venus_in_taurus',     'text' => 'Burnt orange or terracotta in quality fabric with solid gold jewelry channels Tuesday without sacrificing comfort.'],
            ['key' => 'tuesday_venus_in_gemini',     'text' => 'A red or orange scarf or bag on a neutral outfit with coral or citrine jewelry acknowledges Tuesday without committing fully.'],
            ['key' => 'tuesday_venus_in_cancer',     'text' => 'Warm peach or soft coral rather than hard red with a carnelian pendant gives you Mars energy in a gentler form.'],
            ['key' => 'tuesday_venus_in_leo',        'text' => 'Bold red or orange with gold jewelry and a statement necklace — Tuesday and your Venus are natural allies, go dramatic.'],
            ['key' => 'tuesday_venus_in_virgo',      'text' => 'A single red accent — belt, scarf, or lip color — on a clean neutral outfit is the precise Tuesday move for you.'],
            ['key' => 'tuesday_venus_in_libra',      'text' => 'Soft coral or salmon with rose gold jewelry honors Tuesday without clashing with your instinct for harmony.'],
            ['key' => 'tuesday_venus_in_scorpio',    'text' => 'Burgundy or oxblood with garnet jewelry channels Tuesday with intensity — avoid bright orange, dark and powerful reads better on you.'],
            ['key' => 'tuesday_venus_in_sagittarius','text' => 'Bright orange or red-and-gold combination with a fire opal or amber piece expresses both Mars and your adventurous energy.'],
            ['key' => 'tuesday_venus_in_capricorn',  'text' => 'Dark red or burgundy structured piece with a garnet ring channels Tuesday\'s drive while keeping your standards.'],
            ['key' => 'tuesday_venus_in_aquarius',   'text' => 'Red combined with cobalt or black geometric accessories gives Tuesday\'s bold energy your unconventional expression.'],
            ['key' => 'tuesday_venus_in_pisces',     'text' => 'Warm coral or watermelon pink with a carnelian pendant softens Tuesday\'s sharp Mars vibration into something you can wear comfortably.'],

            // ── Wednesday (Mercury · Yellow, Gold, Multicolor) ────────────

            ['key' => 'wednesday_venus_in_aries',      'text' => 'Yellow statement piece or gold accessories with a sharp modern cut and a tiger\'s eye bracelet reads confident and fast.'],
            ['key' => 'wednesday_venus_in_taurus',     'text' => 'Warm golden yellow in linen or silk with a substantial gold pendant is Wednesday\'s best expression for you.'],
            ['key' => 'wednesday_venus_in_gemini',     'text' => 'Multicolor prints with layered gold jewelry and mismatched earrings — Wednesday is your day, go full Mercury.'],
            ['key' => 'wednesday_venus_in_cancer',     'text' => 'Soft honey yellow or warm cream with a small gold locket or citrine pendant keeps Wednesday approachable and warm.'],
            ['key' => 'wednesday_venus_in_leo',        'text' => 'Yellow and gold head-to-toe with a tiger\'s eye statement necklace — Wednesday\'s gold palette is made for your theatrical side.'],
            ['key' => 'wednesday_venus_in_virgo',      'text' => 'Muted mustard in a clean, precise cut with a single gold stud — understated but sharp, exactly right for Wednesday.'],
            ['key' => 'wednesday_venus_in_libra',      'text' => 'Pastel yellow or champagne with delicate gold jewelry strikes the right note between Wednesday\'s liveliness and your elegance.'],
            ['key' => 'wednesday_venus_in_scorpio',    'text' => 'Deep gold or amber worn against dark clothing with a tiger\'s eye or smoky topaz pendant is Wednesday entirely on your terms.'],
            ['key' => 'wednesday_venus_in_sagittarius','text' => 'Bright yellow or multicolor piece with a turquoise or amber necklace — Wednesday\'s communicative energy amplifies your own naturally.'],
            ['key' => 'wednesday_venus_in_capricorn',  'text' => 'Structured golden yellow or camel blazer with a chain bracelet or small hoops — professional, sharp, and aligned with Mercury.'],
            ['key' => 'wednesday_venus_in_aquarius',   'text' => 'Unconventional color combination built around yellow or a geometric gold accessory gives Wednesday\'s quick energy your original stamp.'],
            ['key' => 'wednesday_venus_in_pisces',     'text' => 'Soft golden yellow or warm ivory in a flowing cut with a citrine or yellow topaz pendant carries Mercury\'s energy gently.'],

            // ── Thursday (Jupiter · Blue, Indigo, Purple) ─────────────────

            ['key' => 'thursday_venus_in_aries',      'text' => 'Cobalt blue statement piece or indigo jacket with gold accessories and a lapis lazuli piece channels Jupiter\'s confidence.'],
            ['key' => 'thursday_venus_in_taurus',     'text' => 'Rich indigo or deep blue in quality fabric with a gold and lapis pendant — substantial, elevated, and right for Thursday.'],
            ['key' => 'thursday_venus_in_gemini',     'text' => 'Layered shades of blue and purple with mixed silver and gold jewelry and an amethyst alongside a blue topaz captures Jupiter\'s range.'],
            ['key' => 'thursday_venus_in_cancer',     'text' => 'Soft periwinkle or dusty blue with an aquamarine or moonstone piece keeps Thursday\'s optimism gentle and accessible.'],
            ['key' => 'thursday_venus_in_leo',        'text' => 'Deep purple or royal blue with bold gold jewelry and an amethyst statement necklace — Thursday\'s royal palette is made for your theatrical side.'],
            ['key' => 'thursday_venus_in_virgo',      'text' => 'Precise navy or slate blue in a clean cut with a small sapphire or blue topaz stud — understated but clearly elevated.'],
            ['key' => 'thursday_venus_in_libra',      'text' => 'Lavender or soft violet with rose gold and amethyst jewelry balances Thursday\'s expansion with your instinct for elegance.'],
            ['key' => 'thursday_venus_in_scorpio',    'text' => 'Deep indigo or midnight blue with iolite or tanzanite jewelry channels Jupiter\'s depth without losing your intensity.'],
            ['key' => 'thursday_venus_in_sagittarius','text' => 'Rich cobalt, royal blue, or purple in a generous cut with a turquoise or lapis piece — Thursday is your ruling day, express it fully.'],
            ['key' => 'thursday_venus_in_capricorn',  'text' => 'Structured navy or deep indigo blazer with a sapphire or dark lapis piece as the single statement accessory — authoritative and aligned.'],
            ['key' => 'thursday_venus_in_aquarius',   'text' => 'Electric blue or violet with an unconventional cut and a labradorite or iolite piece with shifting color is Thursday perfectly expressed.'],
            ['key' => 'thursday_venus_in_pisces',     'text' => 'Flowing violet or soft indigo with amethyst or aquamarine jewelry — Thursday\'s dreamy palette is made for you, trust your instinct.'],

            // ── Friday (Venus · Rose, Pink, Warm Cream) ───────────────────

            ['key' => 'friday_venus_in_aries',      'text' => 'Bold rose or fuchsia with a rose gold or pink tourmaline piece meets Venus day with your energy still intact.'],
            ['key' => 'friday_venus_in_taurus',     'text' => 'Beautiful rose or blush in luxurious fabric with high-quality rose gold jewelry — Friday is your strongest day, make it count.'],
            ['key' => 'friday_venus_in_gemini',     'text' => 'Cream base with layered pink jewelry or a floral print with mixed rose and gold accents captures Friday\'s charm and your playfulness.'],
            ['key' => 'friday_venus_in_cancer',     'text' => 'Soft pink or ivory with pearl or moonstone jewelry — Friday\'s warm cream and blush palette feels comforting and exactly right for you.'],
            ['key' => 'friday_venus_in_leo',        'text' => 'Bold hot pink or deep rose with gold jewelry and rose quartz drop earrings — Friday demands drama even in soft tones.'],
            ['key' => 'friday_venus_in_virgo',      'text' => 'Clean blush in a precise minimal cut with a single rose quartz stud or delicate pink sapphire ring — refined and intentional.'],
            ['key' => 'friday_venus_in_libra',      'text' => 'Soft rose, blush, and warm cream with rose gold jewelry — Friday is your most natural day, trust your instinct completely.'],
            ['key' => 'friday_venus_in_scorpio',    'text' => 'Deep rose or dusty mauve with dark garnet or rhodonite jewelry — sensual and beautiful without losing your edge.'],
            ['key' => 'friday_venus_in_sagittarius','text' => 'Warm coral or terracotta accent with a sunstone or carnelian piece keeps Friday\'s rosy palette feeling adventurous and like you.'],
            ['key' => 'friday_venus_in_capricorn',  'text' => 'Structured blush blazer or tailored cream with a rose gold watch or pearl necklace turns Friday\'s softness into understated authority.'],
            ['key' => 'friday_venus_in_aquarius',   'text' => 'Neon pink with cream or an unexpected geometric rose gold accessory honors Venus day while remaining distinctly yourself.'],
            ['key' => 'friday_venus_in_pisces',     'text' => 'Flowing blush, rose, and cream with pearl or moonstone jewelry — Friday and your Venus are perfectly aligned, just wear what you love.'],

            // ── Saturday (Saturn · Black, Dark Grey, Deep Green) ──────────

            ['key' => 'saturday_venus_in_aries',      'text' => 'Sharp black or charcoal with bold red accessories and a jet or obsidian cuff grounds Saturday\'s energy while staying true to your style.'],
            ['key' => 'saturday_venus_in_taurus',     'text' => 'Deep forest green or charcoal in premium fabric with substantial dark gold jewelry — comfort and structure are not opposites on Saturday.'],
            ['key' => 'saturday_venus_in_gemini',     'text' => 'Dark tones with varied texture and layering, plus a malachite or green tourmaline piece, keeps Saturday from feeling too restrictive.'],
            ['key' => 'saturday_venus_in_cancer',     'text' => 'Deep teal or dark sage rather than stark black with silver or onyx jewelry that has a softer quality — structured but not harsh.'],
            ['key' => 'saturday_venus_in_leo',        'text' => 'All-black with a large gold statement piece — striking, structured, and completely on your terms for Saturday.'],
            ['key' => 'saturday_venus_in_virgo',      'text' => 'Precise charcoal or deep forest green with a thin black cord bracelet or obsidian stud — intentional, clean, and perfectly in tune with Saturday.'],
            ['key' => 'saturday_venus_in_libra',      'text' => 'Deep olive or dark sage with brushed silver or dark rose gold jewelry softens Saturday into something elegant rather than severe.'],
            ['key' => 'saturday_venus_in_scorpio',    'text' => 'All-black or deep charcoal with obsidian, jet, or black tourmaline jewelry — Saturday is entirely your element, powerful and on your terms.'],
            ['key' => 'saturday_venus_in_sagittarius','text' => 'Deep forest or hunter green over black with a malachite or dark jade piece keeps Saturday\'s energy while preserving your outdoor spirit.'],
            ['key' => 'saturday_venus_in_capricorn',  'text' => 'Perfectly tailored dark grey or black with a simple obsidian ring or platinum stud — Saturday is your strongest day, effortless and authoritative.'],
            ['key' => 'saturday_venus_in_aquarius',   'text' => 'Black with an electric green or cobalt accessory breaks Saturday\'s convention while staying within its serious range — striking and intentional.'],
            ['key' => 'saturday_venus_in_pisces',     'text' => 'Deep sea green or dark teal instead of black with labradorite jewelry that shifts in light gives Saturday structure with a dreamlike quality.'],

            // ── Sunday (Sun · Gold, Amber, Warm Orange) ───────────────────

            ['key' => 'sunday_venus_in_aries',      'text' => 'Gold or warm amber statement piece with bold jewelry and a citrine ring — Sunday\'s solar energy and your boldness align perfectly.'],
            ['key' => 'sunday_venus_in_taurus',     'text' => 'Rich amber or honey-colored outfit in luxurious fabric with substantial gold jewelry — Sunday\'s warmth and your appreciation for quality meet perfectly.'],
            ['key' => 'sunday_venus_in_gemini',     'text' => 'Layered gold and amber tones with varied textures and a citrine bracelet alongside gold hoops captures Sunday\'s energetic variety.'],
            ['key' => 'sunday_venus_in_cancer',     'text' => 'Soft honey or peach rather than bold amber with a gold-and-moonstone pendant balances Sunday\'s solar warmth with your receptive nature.'],
            ['key' => 'sunday_venus_in_leo',        'text' => 'Full gold, amber, and warm orange with the most dramatic jewelry you own — Sunday is your day of days, no holding back.'],
            ['key' => 'sunday_venus_in_virgo',      'text' => 'Clean amber or warm camel outfit with a single carefully chosen gold piece — let one thing stand out rather than everything at once.'],
            ['key' => 'sunday_venus_in_libra',      'text' => 'Soft amber or champagne with rose gold jewelry and an orange sapphire or warm tourmaline piece keeps Sunday harmonious and warm.'],
            ['key' => 'sunday_venus_in_scorpio',    'text' => 'Deep amber or cognac rather than bright gold with smoky topaz or amber jewelry gives Sunday\'s solar energy your darker, more concentrated form.'],
            ['key' => 'sunday_venus_in_sagittarius','text' => 'Rich amber or gold outfit with bold accessories and a tiger\'s eye or citrine piece — Sunday\'s expansive warmth aligns naturally with your optimism.'],
            ['key' => 'sunday_venus_in_capricorn',  'text' => 'Camel or warm gold tailored piece with a gold signet ring or chain bracelet channels Sunday\'s authority without excess.'],
            ['key' => 'sunday_venus_in_aquarius',   'text' => 'Amber with an unexpected color pairing or a solar-inspired geometric gold piece gives Sunday\'s warmth your unconventional stamp.'],
            ['key' => 'sunday_venus_in_pisces',     'text' => 'Honey, champagne, or warm ivory tones with citrine or golden topaz jewelry — Sunday\'s warmth translates like late afternoon light for you.'],

        ];
    }
}
