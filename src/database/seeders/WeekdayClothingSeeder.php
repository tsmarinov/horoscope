<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored weekday clothing tips — variant 1.
 *
 * Section: weekday_clothing
 * Key format: {weekday}_venus_in_{sign}  (e.g. tuesday_venus_in_aries)
 * 7 days × 12 Venus signs = 84 blocks
 *
 * Each text combines the day's planetary ruler + colors with the natal Venus sign style.
 * ~2 sentences. HTML: <strong> for key clothing items.
 */
class WeekdayClothingSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'weekday_clothing',
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

        $this->command->info('Weekday clothing tips seeded — ' . count($rows) . ' blocks.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Monday — Moon · Silver, White, Green · Intuition, Home, Memory
    // ─────────────────────────────────────────────────────────────────────────

    private function blocks(): array
    {
        return [

            ['key' => 'monday_venus_in_aries', 'text' =>
                "Monday's soft, reflective energy sits in contrast to your bold instincts — try a <strong>crisp white shirt</strong> with a single <strong>red accessory</strong> to honor the day while staying true to your style. "
                . "A <strong>silver bracelet</strong> keeps the Moon's wavelength without dulling your edge."],

            ['key' => 'monday_venus_in_taurus', 'text' =>
                "A <strong>sage green knit</strong> or <strong>cream linen blouse</strong> works perfectly with Monday's intuitive, domestic energy — comfort and quality are the priority today. "
                . "A <strong>pearl or moonstone pendant</strong> adds quiet elegance."],

            ['key' => 'monday_venus_in_gemini', 'text' =>
                "Monday favors soft, understated layers — a <strong>light grey cardigan over a white tee</strong> gives you flexibility through the day. "
                . "Keep jewelry light: <strong>small silver hoops</strong> or a <strong>delicate chain</strong> work well."],

            ['key' => 'monday_venus_in_cancer', 'text' =>
                "Your natural instinct and Monday's energy align completely — <strong>soft silver-white fabrics</strong> and a <strong>moonstone pendant</strong> feel exactly right today. "
                . "Choose something comfortable that you have worn before and love."],

            ['key' => 'monday_venus_in_leo', 'text' =>
                "Monday calls for softness, but your Venus pushes for drama — a <strong>flowing white blouse with gold statement earrings</strong> balances both. "
                . "Let the jewelry do the talking while the palette stays lunar."],

            ['key' => 'monday_venus_in_virgo', 'text' =>
                "A <strong>clean white or dove grey outfit</strong> with minimal detail suits Monday's reflective mood. "
                . "A single piece of <strong>silver jewelry</strong> — a thin ring or stud earrings — completes the look without overcomplicating it."],

            ['key' => 'monday_venus_in_libra', 'text' =>
                "Monday's silver and white palette matches your instinct for elegant simplicity — a <strong>soft white dress or tailored ecru blouse</strong> with <strong>rose gold jewelry</strong> hits the right note. "
                . "Harmony over statement today."],

            ['key' => 'monday_venus_in_scorpio', 'text' =>
                "Lean into Monday's mystical side with <strong>deep charcoal or midnight blue</strong> rather than stark white — a <strong>silver chain or obsidian pendant</strong> grounds the intuitive energy. "
                . "Dark and quiet works better for you than soft and pale."],

            ['key' => 'monday_venus_in_sagittarius', 'text' =>
                "Monday's muted palette can feel restrictive — try a <strong>white base with one bold green or teal accent</strong> to stay comfortable with the day's energy. "
                . "A <strong>moonstone or labradorite piece</strong> adds subtle intrigue."],

            ['key' => 'monday_venus_in_capricorn', 'text' =>
                "A <strong>structured white or pale grey blazer</strong> with minimal accessories channels Monday's reflective energy while keeping your standards. "
                . "A <strong>simple silver watch</strong> or <strong>pearl studs</strong> complete the professional look."],

            ['key' => 'monday_venus_in_aquarius', 'text' =>
                "Monday's lunar colors can be your canvas for something unexpected — try <strong>white with an electric blue or iridescent accessory</strong> that catches the light differently. "
                . "A <strong>distinctive silver piece</strong> with unusual geometry suits your taste."],

            ['key' => 'monday_venus_in_pisces', 'text' =>
                "Monday's dreamy, intuitive energy is made for you — <strong>flowing white or pale sea-green fabrics</strong> with a <strong>moonstone or aquamarine pendant</strong> feel completely natural. "
                . "Soft layers and gentle colors mirror the day perfectly."],

            // ── Tuesday — Mars · Red, Orange · Action, Courage, Drive ─────────

            ['key' => 'tuesday_venus_in_aries', 'text' =>
                "Tuesday is your natural home — go full force with a <strong>bold red statement piece</strong> or an <strong>all-red look</strong>. "
                . "<strong>Ruby or garnet jewelry</strong> channels both Mars energy and your Venus instincts perfectly."],

            ['key' => 'tuesday_venus_in_taurus', 'text' =>
                "Tuesday's fiery energy can be grounded with <strong>burnt orange or terracotta tones</strong> in quality fabric — a <strong>rich rust cashmere</strong> keeps you comfortable while matching the day. "
                . "Keep jewelry solid gold and substantial."],

            ['key' => 'tuesday_venus_in_gemini', 'text' =>
                "Add a <strong>red or orange scarf or bag</strong> to an otherwise neutral outfit to acknowledge Tuesday's energy without committing fully. "
                . "A <strong>coral necklace or orange citrine bracelet</strong> keeps things lively."],

            ['key' => 'tuesday_venus_in_cancer', 'text' =>
                "Tuesday's directness doesn't suit your softer style — a <strong>warm peach or soft coral top</strong> rather than hard red keeps the day's color in palette. "
                . "A <strong>carnelian pendant</strong> gives you Mars energy in a gentler form."],

            ['key' => 'tuesday_venus_in_leo', 'text' =>
                "Tuesday and your Venus are natural allies — a <strong>bold red or orange ensemble</strong> with <strong>gold jewelry</strong> is exactly right. "
                . "Go dramatic: a <strong>statement necklace or oversized gold hoops</strong> with a red dress makes the full impact."],

            ['key' => 'tuesday_venus_in_virgo', 'text' =>
                "A single <strong>red accent — belt, scarf, or a lip color</strong> — on an otherwise clean neutral outfit is the right balance for Tuesday. "
                . "Avoid overwhelming the look; precision and restraint serve you better than full Mars mode."],

            ['key' => 'tuesday_venus_in_libra', 'text' =>
                "A <strong>soft coral or salmon top</strong> with <strong>rose gold jewelry</strong> lets you acknowledge Tuesday without clashing with your instinct for harmony. "
                . "Avoid hard, saturated red — softer warm tones suit your palette better."],

            ['key' => 'tuesday_venus_in_scorpio', 'text' =>
                "Deep red is your natural territory — a <strong>burgundy or oxblood outfit</strong> with <strong>garnet or red jasper jewelry</strong> channels Tuesday with intensity and control. "
                . "Avoid bright orange; dark and powerful reads better on you."],

            ['key' => 'tuesday_venus_in_sagittarius', 'text' =>
                "Tuesday's energy amplifies your natural boldness — a <strong>bright orange or red-and-gold combination</strong> with an adventurous cut expresses both. "
                . "A <strong>fire opal or amber piece</strong> adds warmth and movement."],

            ['key' => 'tuesday_venus_in_capricorn', 'text' =>
                "A <strong>dark red or burgundy structured piece</strong> — blazer, coat, or fitted shirt — channels Tuesday's drive while keeping your standards. "
                . "Minimal jewelry: a <strong>garnet ring or simple cufflinks</strong> is enough."],

            ['key' => 'tuesday_venus_in_aquarius', 'text' =>
                "Tuesday's conventional boldness isn't quite your instinct — try an <strong>unexpected red combination</strong> like red with cobalt or red with black geometric accessories. "
                . "The energy is there but expressed in an original form."],

            ['key' => 'tuesday_venus_in_pisces', 'text' =>
                "Tuesday's sharp energy can feel abrasive — a <strong>warm coral or watermelon pink</strong> softens the Mars vibration while staying in palette. "
                . "A <strong>carnelian or sunstone pendant</strong> keeps the day's drive accessible."],

            // ── Wednesday — Mercury · Yellow, Gold, Multicolor · Communication ─

            ['key' => 'wednesday_venus_in_aries', 'text' =>
                "Wednesday's quick energy suits you — a <strong>yellow statement piece or gold accessories</strong> with a sharp, modern cut reads confident and fast. "
                . "A <strong>tiger's eye bracelet</strong> or <strong>gold chain</strong> adds the right edge."],

            ['key' => 'wednesday_venus_in_taurus', 'text' =>
                "A <strong>warm golden yellow in natural fabric</strong> — linen or silk — hits Wednesday's communicative energy without sacrificing comfort. "
                . "A <strong>gold chain with a substantial pendant</strong> is perfectly in tune."],

            ['key' => 'wednesday_venus_in_gemini', 'text' =>
                "Wednesday is your day — go <strong>multicolor or mix-and-match prints</strong> with layered jewelry. "
                . "A <strong>bright yellow accessory, mismatched earrings, or a stack of thin gold rings</strong> reflects Mercury at full speed."],

            ['key' => 'wednesday_venus_in_cancer', 'text' =>
                "A <strong>soft golden yellow or warm cream</strong> keeps Wednesday approachable — pair it with a <strong>small gold locket or citrine pendant</strong> for gentle communication energy. "
                . "Avoid harsh citrus yellow; warm honey tones work better for you."],

            ['key' => 'wednesday_venus_in_leo', 'text' =>
                "Wednesday's gold palette is made for you — <strong>yellow and gold from head to toe</strong> with dramatic jewelry turns heads for the right reason. "
                . "A <strong>tiger's eye statement necklace</strong> is the finishing touch."],

            ['key' => 'wednesday_venus_in_virgo', 'text' =>
                "A <strong>muted mustard or golden beige</strong> in a clean, precise cut is the right Wednesday move — understated but sharp. "
                . "A single <strong>gold stud or thin ring</strong> keeps jewelry intentional."],

            ['key' => 'wednesday_venus_in_libra', 'text' =>
                "Wednesday's lively palette suits your social side — a <strong>pastel yellow or champagne outfit</strong> with <strong>delicate gold jewelry</strong> strikes the right elegance note. "
                . "Avoid loud multicolor; soft and harmonious reads better on you."],

            ['key' => 'wednesday_venus_in_scorpio', 'text' =>
                "Yellow isn't your instinct, but Wednesday calls for it — a <strong>deep gold or amber piece worn against dark clothing</strong> is the compromise. "
                . "A <strong>tiger's eye or smoky topaz pendant</strong> keeps it entirely on your terms."],

            ['key' => 'wednesday_venus_in_sagittarius', 'text' =>
                "Wednesday's lively, communicative energy amplifies your own — a <strong>bright yellow or multicolor piece</strong> with adventurous accessories feels completely natural. "
                . "A <strong>turquoise or amber necklace</strong> adds the right world-traveler note."],

            ['key' => 'wednesday_venus_in_capricorn', 'text' =>
                "A <strong>structured golden yellow or camel blazer</strong> channels Wednesday's intellect without compromising your authority. "
                . "Minimal gold jewelry — a <strong>chain bracelet or small hoops</strong> — keeps it professional and sharp."],

            ['key' => 'wednesday_venus_in_aquarius', 'text' =>
                "Wednesday's multicolor invitation is one you'll take — try an <strong>unconventional color combination built around yellow</strong> or a <strong>geometric gold accessory</strong> that plays with the day's quick energy. "
                . "Original and communicative."],

            ['key' => 'wednesday_venus_in_pisces', 'text' =>
                "A <strong>soft golden yellow or warm ivory</strong> in a flowing cut suits Wednesday without demanding too much sharpness. "
                . "A <strong>citrine or yellow topaz pendant</strong> carries Mercury's energy gently."],

            // ── Thursday — Jupiter · Blue, Indigo, Purple · Expansion, Wisdom ──

            ['key' => 'thursday_venus_in_aries', 'text' =>
                "Thursday's expansive energy can be bold — a <strong>cobalt blue statement piece</strong> or <strong>indigo jacket</strong> with gold accessories channels Jupiter's confidence. "
                . "A <strong>lapis lazuli or sapphire piece</strong> adds the day's wisdom."],

            ['key' => 'thursday_venus_in_taurus', 'text' =>
                "A <strong>rich indigo or deep blue in quality fabric</strong> feels exactly right for Thursday's generous energy. "
                . "A <strong>gold and lapis pendant</strong> or <strong>sapphire ring</strong> completes a look that is both substantial and elevated."],

            ['key' => 'thursday_venus_in_gemini', 'text' =>
                "Thursday's big energy suits variety — layer <strong>shades of blue and purple</strong> with a <strong>mix of silver and gold jewelry</strong>. "
                . "An <strong>amethyst pendant alongside a blue topaz ring</strong> captures Jupiter's range."],

            ['key' => 'thursday_venus_in_cancer', 'text' =>
                "A <strong>soft periwinkle or dusty blue</strong> is Thursday's gentler face — comfortable fabric in these tones with a <strong>moonstone or aquamarine piece</strong> keeps the day's optimism accessible. "
                . "Avoid harsh royal blue; soft versions suit you better."],

            ['key' => 'thursday_venus_in_leo', 'text' =>
                "Thursday's royal palette is made for your theatrical side — <strong>deep purple or royal blue</strong> with <strong>bold gold jewelry</strong> makes a full Jupiter statement. "
                . "An <strong>amethyst statement necklace</strong> with a gold setting is perfect."],

            ['key' => 'thursday_venus_in_virgo', 'text' =>
                "A <strong>precise navy or slate blue in a clean cut</strong> suits Thursday without overreaching — understated but clearly elevated. "
                . "A <strong>small sapphire or blue topaz stud</strong> keeps jewelry minimal and intentional."],

            ['key' => 'thursday_venus_in_libra', 'text' =>
                "Thursday's refined palette suits you naturally — a <strong>lavender or soft violet outfit</strong> with <strong>rose gold and amethyst jewelry</strong> balances elegance with expansion. "
                . "Soft purples feel more harmonious than saturated indigo."],

            ['key' => 'thursday_venus_in_scorpio', 'text' =>
                "Deep indigo or midnight blue is entirely on your wavelength for Thursday — a <strong>dark blue ensemble with iolite or tanzanite jewelry</strong> channels Jupiter's depth without losing your intensity."],

            ['key' => 'thursday_venus_in_sagittarius', 'text' =>
                "Thursday is your ruling day — <strong>rich cobalt, royal blue, or purple</strong> in a generous, relaxed cut reflects Jupiter at full expression. "
                . "A <strong>turquoise, lapis, or amethyst piece</strong> from somewhere you have traveled is even better."],

            ['key' => 'thursday_venus_in_capricorn', 'text' =>
                "A <strong>structured navy or deep indigo suit or blazer</strong> is Thursday's power look for you — authoritative and aligned with Jupiter's wisdom. "
                . "A <strong>sapphire or dark lapis piece</strong> as a single statement accessory is exactly enough."],

            ['key' => 'thursday_venus_in_aquarius', 'text' =>
                "Thursday's indigo and purple invite experimentation — try an <strong>electric blue or violet piece</strong> with an <strong>unconventional cut or layered accessories</strong>. "
                . "A <strong>labradorite or iolite piece</strong> with shifting color is perfectly aligned."],

            ['key' => 'thursday_venus_in_pisces', 'text' =>
                "Thursday's dreamy purple and blue palette is made for you — <strong>flowing violet or soft indigo</strong> with <strong>amethyst or aquamarine jewelry</strong> feels completely effortless. "
                . "Trust your instinct and go fully into the color."],

            // ── Friday — Venus · Rose, Pink, Warm Cream · Beauty, Relationships ─

            ['key' => 'friday_venus_in_aries', 'text' =>
                "Friday's softness is not quite your instinct, but a <strong>bold rose or fuchsia</strong> meets Venus day halfway — vibrant enough for your energy, still in palette. "
                . "A <strong>rose gold or pink tourmaline piece</strong> adds feminine edge."],

            ['key' => 'friday_venus_in_taurus', 'text' =>
                "Friday is your strongest day — a <strong>beautiful rose or blush outfit in luxurious fabric</strong> with <strong>high-quality rose gold jewelry</strong> is exactly right. "
                . "Take care with the details; quality and beauty matter more today than any other day."],

            ['key' => 'friday_venus_in_gemini', 'text' =>
                "Friday's soft palette invites playful accessorizing — a <strong>cream base with layered pink jewelry</strong> or a <strong>floral print with mixed rose and gold accents</strong> captures the day's charm. "
                . "Light, social, and a little flirtatious."],

            ['key' => 'friday_venus_in_cancer', 'text' =>
                "Friday's warm cream and blush tones feel comforting and right — a <strong>soft pink or ivory dress</strong> with <strong>pearl or moonstone jewelry</strong> is your ideal Venus day look. "
                . "Delicate and emotionally resonant."],

            ['key' => 'friday_venus_in_leo', 'text' =>
                "Friday demands drama even in soft tones — a <strong>bold hot pink or deep rose statement piece</strong> with <strong>gold jewelry</strong> is your take on Venus day. "
                . "Go big on accessories: <strong>rose quartz drop earrings</strong> or a <strong>gold statement cuff</strong>."],

            ['key' => 'friday_venus_in_virgo', 'text' =>
                "A <strong>clean blush or pale pink in a precise, minimal cut</strong> is perfect for Friday — nothing overdone, everything intentional. "
                . "A single <strong>rose quartz stud or delicate pink sapphire ring</strong> keeps it refined."],

            ['key' => 'friday_venus_in_libra', 'text' =>
                "Friday is your most natural day — <strong>soft rose, blush, and warm cream in an elegant combination</strong> with <strong>rose gold jewelry</strong> is effortless and exactly right. "
                . "Trust your instinct completely today."],

            ['key' => 'friday_venus_in_scorpio', 'text' =>
                "Friday's softness sits in contrast with your depth — try <strong>deep rose or dusty mauve</strong> rather than pastel pink, with <strong>dark garnet or rhodonite jewelry</strong>. "
                . "Sensual and beautiful without losing your edge."],

            ['key' => 'friday_venus_in_sagittarius', 'text' =>
                "Friday's rosy palette can feel a little precious — add a <strong>warm coral or terracotta accent</strong> to keep your adventurous energy present. "
                . "A <strong>sunstone or carnelian piece</strong> warms the look and keeps it feeling like you."],

            ['key' => 'friday_venus_in_capricorn', 'text' =>
                "A <strong>structured blush blazer or tailored cream outfit</strong> turns Friday's softness into understated authority. "
                . "A single <strong>rose gold watch or pearl necklace</strong> maintains your standards without softening too much."],

            ['key' => 'friday_venus_in_aquarius', 'text' =>
                "Friday's conventional prettiness needs your twist — try <strong>neon pink with cream neutrals</strong> or an <strong>unexpected geometric rose gold accessory</strong>. "
                . "You can honor Venus day while remaining distinctly yourself."],

            ['key' => 'friday_venus_in_pisces', 'text' =>
                "Friday and your Venus are completely aligned — <strong>flowing blush, rose, and soft cream fabrics</strong> with <strong>pearl, moonstone, or rose quartz jewelry</strong> feels exactly like you. "
                . "No effort required; just wear what you love."],

            // ── Saturday — Saturn · Black, Dark Grey, Deep Green · Discipline ───

            ['key' => 'saturday_venus_in_aries', 'text' =>
                "Saturday's structured energy can be grounding — try a <strong>sharp black or charcoal outfit with bold red accessories</strong> to stay true to your style while meeting the day. "
                . "A <strong>jet or obsidian cuff</strong> adds authority."],

            ['key' => 'saturday_venus_in_taurus', 'text' =>
                "Saturday's dark, earthy palette suits your quality instinct — a <strong>deep forest green or charcoal in premium fabric</strong> with <strong>substantial dark gold jewelry</strong> hits the mark. "
                . "Comfort and structure are not opposites today."],

            ['key' => 'saturday_venus_in_gemini', 'text' =>
                "Saturday's monochrome energy can feel limiting — add interest with <strong>texture and layering in dark tones</strong> or a <strong>single unexpected accessory</strong> in a contrasting color. "
                . "A <strong>malachite or green tourmaline piece</strong> brightens the palette."],

            ['key' => 'saturday_venus_in_cancer', 'text' =>
                "Saturday's dark palette can feel heavy — choose <strong>deep teal or dark sage</strong> rather than stark black, and pair with <strong>silver or onyx jewelry</strong> that has a softer quality. "
                . "Structured but not harsh."],

            ['key' => 'saturday_venus_in_leo', 'text' =>
                "Saturday's restrictions can work dramatically — an <strong>all-black look with bold gold jewelry</strong> is striking, structured, and completely on your terms. "
                . "A <strong>large gold statement piece</strong> against black is your Saturday signature."],

            ['key' => 'saturday_venus_in_virgo', 'text' =>
                "Saturday and your sensibility align perfectly — a <strong>precise charcoal or deep forest green outfit with minimal jewelry</strong>. "
                . "A <strong>thin black cord bracelet or simple obsidian stud</strong> keeps it intentional and clean."],

            ['key' => 'saturday_venus_in_libra', 'text' =>
                "Saturday's dark palette is less naturally comfortable for you — soften it with <strong>deep olive or dark sage</strong> rather than pure black, and keep jewelry in <strong>brushed silver or dark rose gold</strong>. "
                . "Elegant, not severe."],

            ['key' => 'saturday_venus_in_scorpio', 'text' =>
                "Saturday is entirely in your element — <strong>all-black or deep charcoal with obsidian, jet, or black tourmaline jewelry</strong> channels both Saturn's discipline and your intensity. "
                . "Powerful and completely on your terms."],

            ['key' => 'saturday_venus_in_sagittarius', 'text' =>
                "Saturday's structure can feel confining — choose <strong>deep forest green or hunter green</strong> over black to keep some optimism in the palette. "
                . "A <strong>malachite or dark green jade piece</strong> maintains Saturn's energy with your outdoor spirit."],

            ['key' => 'saturday_venus_in_capricorn', 'text' =>
                "Saturday is your strongest day — a <strong>perfectly tailored dark grey or black outfit with precise minimal jewelry</strong> is both effortless and authoritative. "
                . "A <strong>simple obsidian ring or platinum stud</strong> is exactly enough."],

            ['key' => 'saturday_venus_in_aquarius', 'text' =>
                "Saturday's dark palette is your canvas for contrast — try <strong>black with an electric green or cobalt accessory</strong> that breaks convention while staying in the day's serious range. "
                . "Striking and intentional."],

            ['key' => 'saturday_venus_in_pisces', 'text' =>
                "Saturday's harshness can be softened — choose <strong>deep sea green or dark teal instead of black</strong>, and pair with <strong>dark aquamarine or labradorite jewelry</strong> that shifts in light. "
                . "Structure with a dreamlike quality."],

            // ── Sunday — Sun · Gold, Amber, Warm Orange · Vitality, Expression ──

            ['key' => 'sunday_venus_in_aries', 'text' =>
                "Sunday's solar energy and your boldness align perfectly — a <strong>gold or warm amber statement piece</strong> with <strong>strong jewelry</strong> makes the full expression. "
                . "A <strong>citrine or topaz ring</strong> with visible impact is right."],

            ['key' => 'sunday_venus_in_taurus', 'text' =>
                "Sunday's warm gold palette suits your appreciation for quality — a <strong>rich amber or honey-colored outfit in luxurious fabric</strong> with <strong>substantial gold jewelry</strong> is the ideal expression. "
                . "Beauty and warmth in equal measure today."],

            ['key' => 'sunday_venus_in_gemini', 'text' =>
                "Sunday's warm palette is a fun canvas — layer <strong>gold and amber tones with varied textures</strong> and a <strong>mix of gold jewelry pieces</strong>. "
                . "A <strong>citrine bracelet alongside gold hoops</strong> captures the day's energetic variety."],

            ['key' => 'sunday_venus_in_cancer', 'text' =>
                "Sunday's warm gold can feel a bit intense — try a <strong>soft honey or peach tone</strong> rather than bold amber, with a <strong>gold-and-moonstone pendant</strong> that balances solar warmth with your receptive nature."],

            ['key' => 'sunday_venus_in_leo', 'text' =>
                "Sunday is your day of days — <strong>full gold, amber, and warm orange</strong> with the most dramatic jewelry you own. "
                . "A <strong>large citrine or amber statement necklace</strong> with matching pieces lets your Venus shine completely."],

            ['key' => 'sunday_venus_in_virgo', 'text' =>
                "Sunday's expressiveness can be channeled with precision — a <strong>clean amber or warm camel outfit with a single carefully chosen gold jewelry piece</strong>. "
                . "Let one thing stand out rather than everything at once."],

            ['key' => 'sunday_venus_in_libra', 'text' =>
                "Sunday's warm gold is a beautiful palette for you — a <strong>soft amber or champagne outfit with rose gold jewelry</strong> keeps the solar warmth while staying harmonious. "
                . "An <strong>orange sapphire or warm tourmaline piece</strong> is ideal."],

            ['key' => 'sunday_venus_in_scorpio', 'text' =>
                "Sunday's open warmth sits outside your instinct — try <strong>deep amber or cognac rather than bright gold</strong>, with <strong>smoky topaz or amber jewelry</strong> that has depth. "
                . "Solar energy in a darker, more concentrated form."],

            ['key' => 'sunday_venus_in_sagittarius', 'text' =>
                "Sunday's expansive warmth is perfectly aligned with your optimism — a <strong>rich amber or gold outfit with bold accessories</strong> from somewhere meaningful. "
                . "A <strong>tiger's eye or citrine piece with travel associations</strong> is ideal."],

            ['key' => 'sunday_venus_in_capricorn', 'text' =>
                "Sunday's warmth can be structured — a <strong>camel or warm gold tailored piece</strong> with <strong>gold hardware details and minimal jewelry</strong> channels the day's authority without excess. "
                . "A <strong>gold signet ring or chain bracelet</strong> is enough."],

            ['key' => 'sunday_venus_in_aquarius', 'text' =>
                "Sunday's conventional warmth needs your original touch — try <strong>amber with an unexpected color pairing</strong> or a <strong>solar-inspired geometric gold piece</strong> that is unmistakably yours. "
                . "Warm and unconventional."],

            ['key' => 'sunday_venus_in_pisces', 'text' =>
                "Sunday's golden warmth translates beautifully for you in <strong>honey, champagne, or warm ivory tones</strong> with <strong>citrine, amber, or golden topaz jewelry</strong>. "
                . "Let the colors feel like late afternoon light — warm, diffuse, and beautiful."],

        ];
    }
}
