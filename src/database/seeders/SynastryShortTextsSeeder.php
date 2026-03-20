<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Short (1-2 sentence) synastry text blocks for simplified mode.
 *
 * Sections seeded:
 *   synastry_type_short, synastry_intro_short, synastry_planet_house_short,
 *   synastry_asc_house_short, synastry_partner_male_short,
 *   synastry_partner_female_short, synastry_seventh_lord_sign_short,
 *   synastry_seventh_lord_house_short
 *
 * synastry_partner_male (FULL) and synastry_partner_female (FULL) are also
 * seeded here because they were not in previous seeders.
 *
 * All blocks: variant=1, language='en', tone='neutral'.
 */
class SynastryShortTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $allBlocks = array_merge(
            $this->section('synastry_type_short', $this->typeShort()),
            $this->section('synastry_intro_short', $this->introShort()),
            $this->section('synastry_planet_house_short', $this->planetHouseShort()),
            $this->section('synastry_asc_house_short', $this->ascHouseShort()),
            $this->section('synastry_partner_male', $this->partnerMaleFull()),
            $this->section('synastry_partner_male_short', $this->partnerMaleShort()),
            $this->section('synastry_partner_female', $this->partnerFemaleFull()),
            $this->section('synastry_partner_female_short', $this->partnerFemaleShort()),
            $this->section('synastry_seventh_lord_sign', $this->seventhLordSignFull()),
            $this->section('synastry_seventh_lord_sign_short', $this->seventhLordSignShort()),
            $this->section('synastry_seventh_lord_house', $this->seventhLordHouseFull()),
            $this->section('synastry_seventh_lord_house_short', $this->seventhLordHouseShort()),
            $this->section('synastry_partner_male_same', $this->partnerMaleSame()),
            $this->section('synastry_partner_female_same', $this->partnerFemaleSame()),
        );

        $rows = array_map(fn ($r) => array_merge($r, [
            'language'   => 'en',
            'variant'    => 1,
            'tone'       => 'neutral',
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'cost_usd'   => 0.0,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $allBlocks);

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('text_blocks')->upsert(
                $chunk,
                ['key', 'section', 'language', 'variant'],
                ['text', 'tone', 'updated_at']
            );
        }
    }

    private function section(string $section, array $blocks): array
    {
        return array_map(fn ($b) => array_merge($b, ['section' => $section]), $blocks);
    }

    // ── synastry_type_short ─────────────────────────────────────────────

    private function typeShort(): array
    {
        return [
            ['key' => 'general',       'text' => 'Overall compatibility — the baseline energy and ease of the connection.'],
            ['key' => 'romantic',      'text' => 'Romantic chemistry — attraction, tenderness, and potential for lasting love.'],
            ['key' => 'business',      'text' => 'Professional synergy — shared ambition, reliability, and complementary strengths.'],
            ['key' => 'friends',       'text' => 'Friendship quality — ease, loyalty, and genuine mutual enjoyment.'],
            ['key' => 'family',        'text' => 'Family dynamics — how roles, care, and responsibility are naturally shared.'],
            ['key' => 'spiritual',     'text' => 'Spiritual resonance — shared values, growth orientation, and depth of connection.'],
            ['key' => 'communication', 'text' => 'Communication flow — how naturally they understand and express themselves together.'],
            ['key' => 'emotion',       'text' => 'Emotional attunement — depth of feeling, safety, and empathetic connection.'],
            ['key' => 'sexual',        'text' => 'Physical and erotic chemistry — magnetism, desire, and embodied attraction.'],
            ['key' => 'creative',      'text' => 'Creative synergy — how imagination, play, and self-expression are sparked together.'],
        ];
    }

    // ── synastry_planet_house_short ──────────────────────────────────────

    private function planetHouseShort(): array
    {
        return [
            ['key' => 'sun_house_1',  'text' => ":owner's Sun energises :other's self-image and how they show up in the world."],
            ['key' => 'sun_house_2',  'text' => ":owner's Sun illuminates :other's values and resources, boosting confidence and earning potential."],
            ['key' => 'sun_house_3',  'text' => ":owner's Sun brightens :other's mind — lively conversation and mental spark come naturally."],
            ['key' => 'sun_house_4',  'text' => ":owner's Sun warms :other's home and family life, bringing light to roots and security."],
            ['key' => 'sun_house_5',  'text' => ":owner's Sun ignites :other's creativity and playfulness — romance and fun flourish here."],
            ['key' => 'sun_house_6',  'text' => ":owner's Sun focuses on :other's work and health routines, bringing order and vitality."],
            ['key' => 'sun_house_7',  'text' => ":owner's Sun shines on :other's partnerships — this connection feels fated and identity-defining."],
            ['key' => 'sun_house_8',  'text' => ":owner's Sun penetrates :other's depths, activating transformation, intensity, and shared resources."],
            ['key' => 'sun_house_9',  'text' => ":owner's Sun expands :other's worldview — travel, philosophy, and growth are catalysed."],
            ['key' => 'sun_house_10', 'text' => ":owner's Sun elevates :other's public life and career ambitions, boosting visibility."],
            ['key' => 'sun_house_11', 'text' => ":owner's Sun energises :other's social world and long-term dreams."],
            ['key' => 'sun_house_12', 'text' => ":owner's Sun reaches into :other's hidden realms — spiritual depth and private vulnerability emerge."],
            ['key' => 'moon_house_1',  'text' => ":owner's Moon resonates with :other's outward personality, making their emotional needs immediately visible."],
            ['key' => 'moon_house_2',  'text' => ":owner's Moon seeks comfort in :other's material world — nurturing through security and shared values."],
            ['key' => 'moon_house_3',  'text' => ":owner's Moon connects emotionally through conversation, creating natural understanding and ease."],
            ['key' => 'moon_house_4',  'text' => ":owner's Moon feels at home with :other — deep nurturing, domesticity, and rootedness."],
            ['key' => 'moon_house_5',  'text' => ":owner's Moon delights in :other's creativity and play — emotional joy and romance flow easily."],
            ['key' => 'moon_house_6',  'text' => ":owner's Moon is expressed through care and service in :other's everyday life."],
            ['key' => 'moon_house_7',  'text' => ":owner's Moon seeks emotional partnership with :other — feelings are open and deeply shared."],
            ['key' => 'moon_house_8',  'text' => ":owner's Moon dives into :other's depths — emotional intensity, intimacy, and transformation."],
            ['key' => 'moon_house_9',  'text' => ":owner's Moon is inspired by :other's beliefs and adventures — emotional growth through exploration."],
            ['key' => 'moon_house_10', 'text' => ":owner's Moon is visible in :other's public life — emotions become part of the shared reputation."],
            ['key' => 'moon_house_11', 'text' => ":owner's Moon finds belonging in :other's social circle — friendship and shared ideals."],
            ['key' => 'moon_house_12', 'text' => ":owner's Moon connects to :other's hidden world — deep empathy and spiritual sensitivity."],
        ];
    }

    // ── synastry_asc_house_short ─────────────────────────────────────────

    private function ascHouseShort(): array
    {
        return [
            ['key' => 'asc_house_1',  'text' => ":owner's Ascendant mirrors :other's self — instant recognition and a sense of shared identity."],
            ['key' => 'asc_house_2',  'text' => ":owner's Ascendant connects to :other's values — :owner's presence feels materially grounding."],
            ['key' => 'asc_house_3',  'text' => ":owner's Ascendant activates :other's communication — mental rapport and lively exchange."],
            ['key' => 'asc_house_4',  'text' => ":owner's Ascendant feels like home to :other — domestic warmth and emotional security."],
            ['key' => 'asc_house_5',  'text' => ":owner's Ascendant sparks :other's creativity and joy — playful, romantic energy."],
            ['key' => 'asc_house_6',  'text' => ":owner's Ascendant enters :other's daily life — a practical, helping dynamic."],
            ['key' => 'asc_house_7',  'text' => ":owner's Ascendant directly activates :other's partnership zone — this feels like a destined connection."],
            ['key' => 'asc_house_8',  'text' => ":owner's Ascendant penetrates :other's hidden depths — intensity and transformation."],
            ['key' => 'asc_house_9',  'text' => ":owner's Ascendant broadens :other's horizons — inspiration, travel, philosophical spark."],
            ['key' => 'asc_house_10', 'text' => ":owner's Ascendant lifts :other's public standing — career and reputation are stimulated."],
            ['key' => 'asc_house_11', 'text' => ":owner's Ascendant enlivens :other's social world and future visions."],
            ['key' => 'asc_house_12', 'text' => ":owner's Ascendant reaches :other's hidden realm — spiritual depth and quiet intimacy."],
        ];
    }

    // ── synastry_intro_short (78 sign pairs) ────────────────────────────

    private function introShort(): array
    {
        return [
            ['key' => 'aries_aries',         'text' => 'Two Aries create high-voltage energy — competitive spark, shared initiative, and a need to be first together.'],
            ['key' => 'aries_taurus',         'text' => 'Aries pushes forward while Taurus holds steady — tension between urgency and patience that can be deeply complementary.'],
            ['key' => 'aries_gemini',         'text' => 'Aries drive meets Gemini curiosity — quick, electric energy with plenty of spontaneity and mental stimulation.'],
            ['key' => 'aries_cancer',         'text' => 'Aries boldness meets Cancer sensitivity — a pairing of action and feeling that must learn mutual protection.'],
            ['key' => 'aries_leo',            'text' => 'Two fire signs — bold, expressive, and magnetic together, though each wants to lead.'],
            ['key' => 'aries_virgo',          'text' => 'Aries acts instinctively; Virgo thinks it through — productive tension between impulse and precision.'],
            ['key' => 'aries_libra',          'text' => 'Opposites in the zodiac — Aries directness and Libra diplomacy create fascinating push-pull.'],
            ['key' => 'aries_scorpio',        'text' => 'Both ruled by Mars — intense, determined, and capable of deep loyalty or fierce conflict.'],
            ['key' => 'aries_sagittarius',    'text' => 'Two fire signs seeking adventure — enthusiastic, forward-moving, and rarely bored together.'],
            ['key' => 'aries_capricorn',      'text' => 'Aries wants it now; Capricorn plays the long game — ambition shared, timelines different.'],
            ['key' => 'aries_aquarius',       'text' => 'Aries energy plus Aquarius originality — unconventional and fast-moving, with shared love of independence.'],
            ['key' => 'aries_pisces',         'text' => 'Aries strength meets Pisces sensitivity — a pairing that can inspire or overwhelm.'],
            ['key' => 'taurus_taurus',        'text' => 'Two Taurus Suns — deeply loyal, pleasure-loving, and stable, but both equally stubborn.'],
            ['key' => 'taurus_gemini',        'text' => 'Taurus steadiness meets Gemini variety — one craves security, the other thrives on change.'],
            ['key' => 'taurus_cancer',        'text' => 'Earth and water — naturally nurturing, security-oriented, and deeply attached to home and comfort.'],
            ['key' => 'taurus_leo',           'text' => 'Taurus loyalty meets Leo warmth — both love pleasure and beauty, both are quietly stubborn.'],
            ['key' => 'taurus_virgo',         'text' => 'Two earth signs — practical, grounded, and reliable, with genuine respect for each other\'s thoroughness.'],
            ['key' => 'taurus_libra',         'text' => 'Both ruled by Venus — beauty, harmony, and pleasure are shared values that bind them.'],
            ['key' => 'taurus_scorpio',       'text' => 'Opposite signs — Taurus stability and Scorpio intensity create deep magnetic pull and possible power struggle.'],
            ['key' => 'taurus_sagittarius',   'text' => 'Taurus prefers the familiar; Sagittarius craves expansion — loyalty versus freedom is the core tension.'],
            ['key' => 'taurus_capricorn',     'text' => 'Two earth signs building together — practical ambitions, shared values, and long-term thinking.'],
            ['key' => 'taurus_aquarius',      'text' => 'Taurus roots versus Aquarius wings — one values tradition, the other revolution.'],
            ['key' => 'taurus_pisces',        'text' => 'Earth and water in gentle harmony — sensual Taurus grounds dreamy Pisces beautifully.'],
            ['key' => 'gemini_gemini',        'text' => 'Two Gemini minds — witty, curious, and endlessly stimulating, though both may resist depth.'],
            ['key' => 'gemini_cancer',        'text' => 'Gemini intellect meets Cancer intuition — the mind and the heart need to learn each other\'s language.'],
            ['key' => 'gemini_leo',           'text' => 'Gemini wit sparks Leo warmth — playful, expressive energy with natural social chemistry.'],
            ['key' => 'gemini_virgo',         'text' => 'Both ruled by Mercury — sharp minds, analytical thinking, though one breezes where the other worries.'],
            ['key' => 'gemini_libra',         'text' => 'Two air signs — naturally communicative, harmonious, and socially at ease together.'],
            ['key' => 'gemini_scorpio',       'text' => 'Gemini lightness meets Scorpio depth — fascinating but requires both to stretch beyond comfort.'],
            ['key' => 'gemini_sagittarius',   'text' => 'Opposite signs — both love ideas and travel, but Gemini skims while Sagittarius seeks meaning.'],
            ['key' => 'gemini_capricorn',     'text' => 'Gemini agility versus Capricorn discipline — one plays, the other works, both can learn from this.'],
            ['key' => 'gemini_aquarius',      'text' => 'Two air signs — intellectually vibrant, future-oriented, and comfortable with space in the relationship.'],
            ['key' => 'gemini_pisces',        'text' => 'Gemini logic meets Pisces intuition — a dreamy blend of thought and feeling.'],
            ['key' => 'cancer_cancer',        'text' => 'Two Cancers — deeply nurturing, emotionally attuned, but prone to shared moodiness and insulation.'],
            ['key' => 'cancer_leo',           'text' => 'Cancer depth meets Leo brightness — one nurtures quietly, the other radiates openly.'],
            ['key' => 'cancer_virgo',         'text' => 'Cancer care meets Virgo service — both devoted, both prone to worry, deeply supportive.'],
            ['key' => 'cancer_libra',         'text' => 'Cancer feeling versus Libra thinking — heart-led meets head-led in a gentle dance.'],
            ['key' => 'cancer_scorpio',       'text' => 'Two water signs — emotionally intense, fiercely loyal, and capable of extraordinary depth.'],
            ['key' => 'cancer_sagittarius',   'text' => 'Cancer roots versus Sagittarius wings — home and adventure must find their balance.'],
            ['key' => 'cancer_capricorn',     'text' => 'Opposite signs — Cancer nurtures, Capricorn achieves; together they build security from inside and out.'],
            ['key' => 'cancer_aquarius',      'text' => 'Cancer intimacy meets Aquarius detachment — warmth versus distance, closeness versus freedom.'],
            ['key' => 'cancer_pisces',        'text' => 'Two water signs in quiet resonance — empathic, intuitive, and naturally attuned to each other.'],
            ['key' => 'leo_leo',              'text' => 'Two Leos — dramatic, generous, and radiant together, if both can share the spotlight.'],
            ['key' => 'leo_virgo',            'text' => 'Leo warmth meets Virgo precision — big gestures paired with careful attention to detail.'],
            ['key' => 'leo_libra',            'text' => 'Leo heart meets Libra charm — both love beauty, attention, and harmony in different ways.'],
            ['key' => 'leo_scorpio',          'text' => 'Leo radiance meets Scorpio intensity — powerful, magnetic, and prone to battles of will.'],
            ['key' => 'leo_sagittarius',      'text' => 'Two fire signs — enthusiastic, adventurous, and mutually inspiring, with natural creative spark.'],
            ['key' => 'leo_capricorn',        'text' => 'Leo warmth meets Capricorn ambition — heart versus strategy, yet both aim for the top.'],
            ['key' => 'leo_aquarius',         'text' => 'Opposite signs — Leo personal drama versus Aquarius collective vision; magnetic tension.'],
            ['key' => 'leo_pisces',           'text' => 'Leo clarity meets Pisces mystery — the performer and the dreamer, mutually enchanting.'],
            ['key' => 'virgo_virgo',          'text' => 'Two Virgos — dedicated, thoughtful, and quietly devoted, with shared perfectionist tendencies.'],
            ['key' => 'virgo_libra',          'text' => 'Virgo precision meets Libra harmony — both seek refinement, through different means.'],
            ['key' => 'virgo_scorpio',        'text' => 'Virgo analysis meets Scorpio depth — both investigative and private, intensely compatible.'],
            ['key' => 'virgo_sagittarius',    'text' => 'Virgo detail meets Sagittarius vision — practical versus philosophical, useful friction.'],
            ['key' => 'virgo_capricorn',      'text' => 'Two earth signs — reliable, hard-working, and mutually respectful of discipline and commitment.'],
            ['key' => 'virgo_aquarius',       'text' => 'Virgo groundedness meets Aquarius innovation — analytical minds with different orientations.'],
            ['key' => 'virgo_pisces',         'text' => 'Opposite signs — Virgo order and Pisces flow, practical and spiritual in mutual need.'],
            ['key' => 'libra_libra',          'text' => 'Two Libras — charming, harmonious, and gracious, though both may avoid conflict too long.'],
            ['key' => 'libra_scorpio',        'text' => 'Libra lightness meets Scorpio depth — the diplomat and the investigator, complex chemistry.'],
            ['key' => 'libra_sagittarius',    'text' => 'Two social signs — playful, open-minded, and naturally adventurous together.'],
            ['key' => 'libra_capricorn',      'text' => 'Libra idealism meets Capricorn realism — beauty and structure can build something lasting.'],
            ['key' => 'libra_aquarius',       'text' => 'Two air signs — intellectually alive, socially engaged, and comfortable with independence.'],
            ['key' => 'libra_pisces',         'text' => 'Libra harmony meets Pisces sensitivity — both romantic idealists, prone to avoidance.'],
            ['key' => 'scorpio_scorpio',      'text' => 'Two Scorpios — transformative depth, fierce loyalty, and volcanic potential for both intimacy and conflict.'],
            ['key' => 'scorpio_sagittarius',  'text' => 'Scorpio depth meets Sagittarius freedom — intensity versus lightness, drawn to and frustrated by each other.'],
            ['key' => 'scorpio_capricorn',    'text' => 'Two signs of power — Scorpio emotional depth and Capricorn strategic ambition form a formidable pair.'],
            ['key' => 'scorpio_aquarius',     'text' => 'Scorpio passion meets Aquarius detachment — both stubborn, both penetrating, fascinatingly mismatched.'],
            ['key' => 'scorpio_pisces',       'text' => 'Two water signs — mystical, intuitive, and capable of profound emotional union.'],
            ['key' => 'sagittarius_sagittarius', 'text' => 'Two Sagittarians — enthusiastic, philosophical, and adventurous, though commitment may feel like a cage.'],
            ['key' => 'sagittarius_capricorn', 'text' => 'Sagittarius freedom meets Capricorn structure — expansion and limitation in creative dialogue.'],
            ['key' => 'sagittarius_aquarius', 'text' => 'Two freedom-lovers — visionary, social, and mutually supportive of each other\'s independence.'],
            ['key' => 'sagittarius_pisces',   'text' => 'Two Jupiter-ruled signs — generous, spiritual, and idealistic, sometimes disconnected from practical reality.'],
            ['key' => 'capricorn_capricorn',  'text' => 'Two Capricorns — ambitious, loyal, and deeply committed, though emotional warmth may need cultivating.'],
            ['key' => 'capricorn_aquarius',   'text' => 'Capricorn tradition meets Aquarius revolution — building the future through different approaches.'],
            ['key' => 'capricorn_pisces',     'text' => 'Capricorn grounding meets Pisces dreaming — structure and imagination in productive contrast.'],
            ['key' => 'aquarius_aquarius',    'text' => 'Two Aquarians — intellectually alive, unconventional, and deeply committed to shared ideals.'],
            ['key' => 'aquarius_pisces',      'text' => 'Aquarius logic meets Pisces intuition — the visionary and the mystic in curious dialogue.'],
            ['key' => 'pisces_pisces',        'text' => 'Two Pisces — empathic, dreamy, and spiritually attuned, though both may lack a grounding anchor.'],
        ];
    }

    // ── synastry_partner_male (FULL — new section) ──────────────────────

    private function partnerMaleFull(): array
    {
        return [
            ['key' => 'venus_aries',       'text' => 'His <em>Venus in Aries</em> is drawn to bold, self-assured women who know what they want. He is attracted by confidence, directness, and a spark of competitive fire. The chase matters to him — he is magnetised by someone who does not wait. He craves a partner who is independent and unapologetically herself.'],
            ['key' => 'venus_taurus',      'text' => 'His <em>Venus in Taurus</em> is attracted to sensual, grounded women who embody comfort and beauty. He values loyalty, physical presence, and a slow, reliable warmth. He is drawn to someone who enjoys the good things in life — food, touch, nature — and who offers stability alongside pleasure.'],
            ['key' => 'venus_gemini',      'text' => 'His <em>Venus in Gemini</em> is charmed by witty, curious, versatile women who keep him mentally engaged. He falls for intelligence and playfulness. Boredom is his biggest turn-off — he needs someone who surprises him with conversation, ideas, and a light touch on life.'],
            ['key' => 'venus_cancer',      'text' => 'His <em>Venus in Cancer</em> is attracted to nurturing, emotionally attuned women who feel like home. He is drawn to someone soft, intuitive, and protective — a woman who creates warmth and safety. He wants to feel deeply cared for and will offer that same devotion in return.'],
            ['key' => 'venus_leo',         'text' => 'His <em>Venus in Leo</em> is attracted to radiant, warm-hearted women who light up a room. He loves someone with self-confidence, personal style, and a generous heart. He is drawn to women who carry themselves with dignity and who appreciate being adored.'],
            ['key' => 'venus_virgo',       'text' => 'His <em>Venus in Virgo</em> is drawn to intelligent, thoughtful women with refined taste and quiet depth. He values competence, reliability, and someone who pays attention to detail. He is attracted to women who take care of themselves and the world around them with grace and intelligence.'],
            ['key' => 'venus_libra',       'text' => 'His <em>Venus in Libra</em> is attracted to elegant, charming, and socially graceful women. He loves beauty, diplomacy, and someone who carries herself with ease in any situation. He is drawn to a partner who values fairness and aesthetic refinement — someone who makes everything more beautiful simply by being present.'],
            ['key' => 'venus_scorpio',     'text' => 'His <em>Venus in Scorpio</em> is magnetically drawn to intense, mysterious, and emotionally deep women. He craves someone who does not reveal everything at once — the hidden layers fascinate him. He is attracted to power, depth, and a woman who matches his all-or-nothing approach to love.'],
            ['key' => 'venus_sagittarius', 'text' => 'His <em>Venus in Sagittarius</em> is attracted to adventurous, free-spirited women with strong beliefs and a love of life. He wants someone who explores the world alongside him — intellectually, philosophically, physically. He is drawn to women who laugh easily and who do not try to tame him.'],
            ['key' => 'venus_capricorn',   'text' => 'His <em>Venus in Capricorn</em> is attracted to ambitious, composed, and quietly powerful women. He values substance over flash — a woman with goals, self-discipline, and elegance. He is drawn to someone who has built something real and who approaches life with the same seriousness he does.'],
            ['key' => 'venus_aquarius',    'text' => 'His <em>Venus in Aquarius</em> is attracted to unconventional, intellectually independent women who think for themselves. He is drawn to originality, social awareness, and a certain cool detachment. He wants a partner who is her own person — someone who challenges his mind and respects his need for freedom.'],
            ['key' => 'venus_pisces',      'text' => 'His <em>Venus in Pisces</em> is attracted to soft, intuitive, and spiritually inclined women who seem to exist in a world of their own. He is drawn to empathy, creativity, and a touch of mystery. He wants someone who sees beyond the surface — sensitive, compassionate, and deeply feeling.'],
            ['key' => 'moon_aries',        'text' => 'His <em>Moon in Aries</em> instinctively chooses women who are direct, decisive, and emotionally independent. He feels most comfortable with someone who does not demand constant reassurance — a woman who acts on her feelings rather than dwelling in them. He is nurtured by strength and spontaneity.'],
            ['key' => 'moon_taurus',       'text' => 'His <em>Moon in Taurus</em> seeks a woman who is steady, sensual, and reliably present. He feels emotionally safe with someone who values routine, comfort, and physical affection. He is nurtured by a woman who builds a beautiful, stable world and does not create unnecessary drama.'],
            ['key' => 'moon_gemini',       'text' => 'His <em>Moon in Gemini</em> is emotionally comfortable with women who are communicative, curious, and adaptable. He needs someone he can talk to — a woman who is light with feelings but rich in ideas. He is nurtured by playful connection and freedom from emotional heaviness.'],
            ['key' => 'moon_cancer',       'text' => 'His <em>Moon in Cancer</em> is drawn to nurturing, intuitive, and emotionally expressive women. He wants someone who creates a true home — physically and emotionally. He is nurtured by tenderness, care, and a woman who remembers the small things that make him feel seen.'],
            ['key' => 'moon_leo',          'text' => 'His <em>Moon in Leo</em> seeks a warm, generous, and expressive woman who celebrates him and enjoys being celebrated in return. He wants someone proud and playful — a woman who brings warmth and drama in equal measure. He is nurtured by admiration and a generous emotional presence.'],
            ['key' => 'moon_virgo',        'text' => 'His <em>Moon in Virgo</em> feels most comfortable with a thoughtful, capable, and attentive woman. He appreciates someone who expresses love through acts of care and attention to detail. He is nurtured by reliability, competence, and a woman who makes life run smoothly.'],
            ['key' => 'moon_libra',        'text' => 'His <em>Moon in Libra</em> is emotionally comfortable with a balanced, gracious, and peaceful woman. He needs harmony in his emotional world — someone who avoids extremes and approaches conflict with elegance. He is nurtured by fairness, beauty, and calm partnership.'],
            ['key' => 'moon_scorpio',      'text' => 'His <em>Moon in Scorpio</em> is drawn to emotionally intense, loyal, and perceptive women. He wants depth — someone who can handle his complexity without flinching. He is nurtured by total emotional honesty, loyalty beyond question, and a woman who sees all of him.'],
            ['key' => 'moon_sagittarius',  'text' => 'His <em>Moon in Sagittarius</em> is emotionally comfortable with an optimistic, adventurous, and philosophically open woman. He needs space — emotionally and physically. He is nurtured by a woman who is her own free spirit, who does not cling, and who keeps life feeling like an adventure.'],
            ['key' => 'moon_capricorn',    'text' => 'His <em>Moon in Capricorn</em> is emotionally secure with a responsible, self-sufficient, and composed woman. He needs reliability over romance. He is nurtured by a woman who is capable and serious about life — someone who takes care of herself and honours commitments.'],
            ['key' => 'moon_aquarius',     'text' => 'His <em>Moon in Aquarius</em> is comfortable with an independent, intellectually alive, and emotionally unpredictable woman. He needs space and respects the same in her. He is nurtured by a woman who does not try to possess him emotionally — friendship is the foundation of love for him.'],
            ['key' => 'moon_pisces',       'text' => 'His <em>Moon in Pisces</em> is drawn to gentle, empathic, and imaginative women who feel deeply. He is nurtured by someone who has a rich inner world — intuitive, creative, and spiritually oriented. He wants a woman who softens reality and makes him feel understood without words.'],
        ];
    }

    // ── synastry_partner_male_short ─────────────────────────────────────

    private function partnerMaleShort(): array
    {
        return [
            ['key' => 'venus_aries',       'text' => 'Drawn to bold, direct, and self-assured women — he wants someone who does not wait.'],
            ['key' => 'venus_taurus',      'text' => 'Attracted to sensual, grounded women who embody loyalty, comfort, and quiet beauty.'],
            ['key' => 'venus_gemini',      'text' => 'Charmed by witty, curious women who keep him mentally stimulated and surprised.'],
            ['key' => 'venus_cancer',      'text' => 'Attracted to nurturing, emotionally attuned women who feel like home.'],
            ['key' => 'venus_leo',         'text' => 'Drawn to radiant, warm-hearted women who carry themselves with confidence and style.'],
            ['key' => 'venus_virgo',       'text' => 'Attracted to thoughtful, competent women with refined taste and quiet intelligence.'],
            ['key' => 'venus_libra',       'text' => 'Drawn to elegant, gracious women who bring beauty and diplomacy to every situation.'],
            ['key' => 'venus_scorpio',     'text' => 'Magnetically drawn to intense, mysterious women — depth and hidden layers fascinate him.'],
            ['key' => 'venus_sagittarius', 'text' => 'Attracted to adventurous, free-spirited women who match his love of life and ideas.'],
            ['key' => 'venus_capricorn',   'text' => 'Drawn to ambitious, composed women who have built something real and carry themselves with quiet power.'],
            ['key' => 'venus_aquarius',    'text' => 'Attracted to unconventional, intellectually independent women who think for themselves.'],
            ['key' => 'venus_pisces',      'text' => 'Drawn to soft, intuitive, and spiritually inclined women who see beyond the surface.'],
            ['key' => 'moon_aries',        'text' => 'Emotionally comfortable with direct, independent women who act on feelings without drama.'],
            ['key' => 'moon_taurus',       'text' => 'Seeks steady, sensual women who offer comfort, routine, and reliable affection.'],
            ['key' => 'moon_gemini',       'text' => 'Nurtured by communicative, light women who bring curiosity and freedom from emotional weight.'],
            ['key' => 'moon_cancer',       'text' => 'Drawn to nurturing, intuitive women who create a true emotional home.'],
            ['key' => 'moon_leo',          'text' => 'Nurtured by warm, expressive women who celebrate and are celebrated in equal measure.'],
            ['key' => 'moon_virgo',        'text' => 'Comfortable with thoughtful, capable women who express love through careful attention.'],
            ['key' => 'moon_libra',        'text' => 'Nurtured by balanced, gracious women who bring harmony and calm to the emotional world.'],
            ['key' => 'moon_scorpio',      'text' => 'Drawn to intensely loyal and perceptive women who can handle his full emotional depth.'],
            ['key' => 'moon_sagittarius',  'text' => 'Nurtured by optimistic, free-spirited women who do not cling and keep life adventurous.'],
            ['key' => 'moon_capricorn',    'text' => 'Emotionally secure with responsible, composed women who honour commitments and take care of themselves.'],
            ['key' => 'moon_aquarius',     'text' => 'Comfortable with independent, intellectually alive women — friendship is the foundation.'],
            ['key' => 'moon_pisces',       'text' => 'Drawn to gentle, empathic women who understand without words and soften reality.'],
        ];
    }

    // ── synastry_partner_female (FULL — new section) ────────────────────

    private function partnerFemaleFull(): array
    {
        return [
            ['key' => 'mars_aries',       'text' => 'Her <em>Mars in Aries</em> is drawn to assertive, courageous men who take initiative. She is attracted by confidence, directness, and decisive action. She wants someone who goes after what he wants — including her. A man who hesitates loses her attention.'],
            ['key' => 'mars_taurus',      'text' => 'Her <em>Mars in Taurus</em> is attracted to steady, sensual, and physically present men. She wants someone solid and reliable — a man who works with his hands and his heart, who builds things slowly and well. Patience and consistency ignite her.'],
            ['key' => 'mars_gemini',      'text' => 'Her <em>Mars in Gemini</em> is attracted to intellectually agile, witty, and verbally expressive men. She wants someone who can keep up with her mind — quick, curious, and full of ideas. A man who can make her laugh and think simultaneously is irresistible.'],
            ['key' => 'mars_cancer',      'text' => 'Her <em>Mars in Cancer</em> is attracted to emotionally available, protective, and nurturing men. She wants someone who shows his feelings and makes her feel safe. A man who builds a home and expresses vulnerability without shame draws her deeply.'],
            ['key' => 'mars_leo',         'text' => 'Her <em>Mars in Leo</em> is drawn to confident, generous, and charismatic men who know how to lead. She wants someone who takes pride in himself and makes her feel chosen. A man with presence, style, and the courage to express himself fully attracts her.'],
            ['key' => 'mars_virgo',       'text' => 'Her <em>Mars in Virgo</em> is attracted to capable, intelligent, and detail-oriented men. She wants someone who takes care of things — who is reliable and skilled. A man who is quietly competent and does not need to boast about his abilities is deeply appealing.'],
            ['key' => 'mars_libra',       'text' => 'Her <em>Mars in Libra</em> is attracted to charming, socially intelligent men who know how to treat her well. She wants elegance and fairness — a man who listens, considers, and acts with grace. She is drawn to those who make relationships feel effortless.'],
            ['key' => 'mars_scorpio',     'text' => 'Her <em>Mars in Scorpio</em> is drawn to powerful, magnetic, and emotionally intense men. She wants depth over surface — a man who does not scare easily and who can match her passion. Control, mystery, and unwavering desire are her greatest attractions.'],
            ['key' => 'mars_sagittarius', 'text' => 'Her <em>Mars in Sagittarius</em> is attracted to adventurous, enthusiastic men with a strong sense of purpose. She wants someone with vision — a man who travels, learns, and lives expansively. Intellectual boldness and a sense of humour are essential.'],
            ['key' => 'mars_capricorn',   'text' => 'Her <em>Mars in Capricorn</em> is attracted to ambitious, disciplined, and accomplished men. She respects drive and competence. A man who has clear goals and the self-control to pursue them — quietly and consistently — is profoundly attractive to her.'],
            ['key' => 'mars_aquarius',    'text' => 'Her <em>Mars in Aquarius</em> is attracted to intellectually original, independent men who stand apart from the crowd. She wants someone who challenges convention and has his own vision. Magnetic eccentricity and respect for her autonomy are essential.'],
            ['key' => 'mars_pisces',      'text' => 'Her <em>Mars in Pisces</em> is drawn to sensitive, imaginative, and spiritually inclined men. She is attracted by gentleness and depth of feeling — a man in touch with his inner world. Creativity, compassion, and a touch of the mystical draw her.'],
            ['key' => 'sun_aries',        'text' => 'Her <em>Sun in Aries</em> respects men who are bold, decisive, and unafraid of risk. She wants someone who meets her fire with his own — direct, courageous, and competitive in the best sense.'],
            ['key' => 'sun_taurus',       'text' => 'Her <em>Sun in Taurus</em> values men who are grounded, dependable, and sensually present. She wants substance — a man who builds, provides, and stays.'],
            ['key' => 'sun_gemini',       'text' => 'Her <em>Sun in Gemini</em> is inspired by clever, communicative men who engage her intellectually. She wants a partner who keeps her curious — quick, witty, and never predictable.'],
            ['key' => 'sun_cancer',       'text' => 'Her <em>Sun in Cancer</em> chooses men who are emotionally present, protective, and capable of deep attachment. She wants a man who makes home sacred.'],
            ['key' => 'sun_leo',          'text' => 'Her <em>Sun in Leo</em> is drawn to men with genuine confidence, warmth, and a flair for life. She wants someone who sees her brilliance and matches it with his own.'],
            ['key' => 'sun_virgo',        'text' => 'Her <em>Sun in Virgo</em> values men who are thoughtful, responsible, and quietly capable. She is attracted to intelligence applied to real-world problems.'],
            ['key' => 'sun_libra',        'text' => 'Her <em>Sun in Libra</em> is drawn to refined, considerate men who value harmony and mutual respect. She wants a true partner — someone who balances her gracefully.'],
            ['key' => 'sun_scorpio',      'text' => 'Her <em>Sun in Scorpio</em> is drawn to men of depth, loyalty, and unwavering intensity. She wants total commitment or nothing.'],
            ['key' => 'sun_sagittarius',  'text' => 'Her <em>Sun in Sagittarius</em> values adventurous, philosophically alive men who embrace life\'s expanse. She wants a companion for the journey, not just the destination.'],
            ['key' => 'sun_capricorn',    'text' => 'Her <em>Sun in Capricorn</em> respects men who are ambitious, responsible, and quietly powerful. She wants someone building something — including a future with her.'],
            ['key' => 'sun_aquarius',     'text' => 'Her <em>Sun in Aquarius</em> is drawn to intellectually independent, unconventional men with vision and principle. She wants a partner in ideas, not just in life.'],
            ['key' => 'sun_pisces',       'text' => 'Her <em>Sun in Pisces</em> is drawn to men who are empathic, creative, and spiritually oriented. She wants someone who understands feeling as a language.'],
        ];
    }

    // ── synastry_partner_female_short ────────────────────────────────────

    private function partnerFemaleShort(): array
    {
        return [
            ['key' => 'mars_aries',       'text' => 'Drawn to assertive, courageous men who take initiative — hesitation loses her.'],
            ['key' => 'mars_taurus',      'text' => 'Attracted to steady, sensual men who build slowly and reliably.'],
            ['key' => 'mars_gemini',      'text' => 'Drawn to witty, intellectually agile men who make her laugh and think.'],
            ['key' => 'mars_cancer',      'text' => 'Attracted to emotionally available, protective men who show vulnerability without shame.'],
            ['key' => 'mars_leo',         'text' => 'Drawn to confident, charismatic men with presence, warmth, and natural leadership.'],
            ['key' => 'mars_virgo',       'text' => 'Attracted to quietly competent men who take care of things without needing praise.'],
            ['key' => 'mars_libra',       'text' => 'Drawn to charming, fair men who make relationships feel graceful and effortless.'],
            ['key' => 'mars_scorpio',     'text' => 'Drawn to powerful, magnetic men — depth, mystery, and unwavering desire attract her.'],
            ['key' => 'mars_sagittarius', 'text' => 'Attracted to adventurous men with vision, intellectual boldness, and a sense of humour.'],
            ['key' => 'mars_capricorn',   'text' => 'Drawn to ambitious, disciplined men who pursue their goals quietly and consistently.'],
            ['key' => 'mars_aquarius',    'text' => 'Attracted to original, independent men who challenge convention and respect her autonomy.'],
            ['key' => 'mars_pisces',      'text' => 'Drawn to sensitive, imaginative men with a rich inner world and compassionate presence.'],
            ['key' => 'sun_aries',        'text' => 'Respects bold, decisive men who meet her fire — directness and courage attract her.'],
            ['key' => 'sun_taurus',       'text' => 'Values grounded, dependable men who build, provide, and stay.'],
            ['key' => 'sun_gemini',       'text' => 'Inspired by clever, communicative men who keep her curious and never predictable.'],
            ['key' => 'sun_cancer',       'text' => 'Chooses emotionally present, protective men who make home sacred.'],
            ['key' => 'sun_leo',          'text' => 'Drawn to warm, confident men who see her brilliance and match it with their own.'],
            ['key' => 'sun_virgo',        'text' => 'Values thoughtful, responsible men who apply intelligence to real-world problems.'],
            ['key' => 'sun_libra',        'text' => 'Drawn to refined, considerate men who value harmony and true partnership.'],
            ['key' => 'sun_scorpio',      'text' => 'Drawn to intensely loyal, deep men — she wants total commitment or nothing.'],
            ['key' => 'sun_sagittarius',  'text' => 'Values adventurous, philosophically alive men who embrace life\'s full expanse.'],
            ['key' => 'sun_capricorn',    'text' => 'Respects ambitious, quietly powerful men who are building something lasting.'],
            ['key' => 'sun_aquarius',     'text' => 'Drawn to intellectually independent, visionary men with genuine principle.'],
            ['key' => 'sun_pisces',       'text' => 'Drawn to empathic, creative men who understand feeling as a language.'],
        ];
    }

    // ── synastry_seventh_lord_sign (FULL — new section) ─────────────────

    private function seventhLordSignFull(): array
    {
        return [
            ['key' => 'aries',       'text' => 'With the ruler of the 7th house in <em>Aries</em>, the ideal partner is energetic, self-directed, and confident. They value independence in love — someone who leads from a place of strength, acts decisively, and meets challenges head-on. A passive partner rarely holds their long-term interest.'],
            ['key' => 'taurus',      'text' => 'With the ruler of the 7th house in <em>Taurus</em>, the ideal partner is loyal, sensual, and deeply consistent. They want someone who stays — who loves through actions rather than words, values physical comfort, and approaches life with patience and a grounded presence.'],
            ['key' => 'gemini',      'text' => 'With the ruler of the 7th house in <em>Gemini</em>, the ideal partner is witty, versatile, and mentally stimulating. They need someone they can truly talk to — quick-thinking, socially adaptable, and curious about the world. A dull mind is the greatest intimacy killer for them.'],
            ['key' => 'cancer',      'text' => 'With the ruler of the 7th house in <em>Cancer</em>, the ideal partner is nurturing, emotionally intelligent, and home-oriented. They want someone who creates safety through feeling — attentive to emotional needs, protective, and capable of real intimacy.'],
            ['key' => 'leo',         'text' => 'With the ruler of the 7th house in <em>Leo</em>, the ideal partner is warm, generous, and boldly expressive. They want someone who brings energy and celebration into the relationship — confident in their identity, proud to be loved, and capable of great warmth.'],
            ['key' => 'virgo',       'text' => 'With the ruler of the 7th house in <em>Virgo</em>, the ideal partner is thoughtful, reliable, and attentive to detail. They are drawn to someone who shows love through acts of service and careful attention — practical, intelligent, and genuinely helpful.'],
            ['key' => 'libra',       'text' => 'With the ruler of the 7th house in <em>Libra</em>, the ideal partner is elegant, fair-minded, and socially refined. They want balance, beauty, and mutual respect in their relationships — someone who values harmony and approaches life with grace and consideration.'],
            ['key' => 'scorpio',     'text' => 'With the ruler of the 7th house in <em>Scorpio</em>, the ideal partner is intense, loyal, and emotionally unafraid. They want depth over comfort — someone who engages with the full complexity of love including its shadows. Complete trust, or nothing.'],
            ['key' => 'sagittarius', 'text' => 'With the ruler of the 7th house in <em>Sagittarius</em>, the ideal partner is adventurous, philosophically alive, and genuinely free in spirit. They need someone who expands their world — open to growth, honest to a fault, and enthusiastic about life\'s possibilities.'],
            ['key' => 'capricorn',   'text' => 'With the ruler of the 7th house in <em>Capricorn</em>, the ideal partner is ambitious, reliable, and long-term minded. They respect someone who has built something — responsible, self-possessed, and serious about what commitments mean.'],
            ['key' => 'aquarius',    'text' => 'With the ruler of the 7th house in <em>Aquarius</em>, the ideal partner is intellectually independent, unconventional, and socially conscious. They want a companion who is their equal in thinking — someone with their own ideas, who respects freedom as a core relationship value.'],
            ['key' => 'pisces',      'text' => 'With the ruler of the 7th house in <em>Pisces</em>, the ideal partner is empathic, spiritually inclined, and emotionally fluid. They want someone who connects through feeling and intuition — gentle, imaginative, and capable of love that transcends the practical.'],
        ];
    }

    // ── synastry_seventh_lord_sign_short ─────────────────────────────────

    private function seventhLordSignShort(): array
    {
        return [
            ['key' => 'aries',       'text' => '7th lord in Aries — drawn to independent, decisive partners who lead with energy and confidence.'],
            ['key' => 'taurus',      'text' => '7th lord in Taurus — drawn to loyal, sensual partners who love through consistent presence.'],
            ['key' => 'gemini',      'text' => '7th lord in Gemini — drawn to witty, curious partners who provide real mental stimulation.'],
            ['key' => 'cancer',      'text' => '7th lord in Cancer — drawn to nurturing, emotionally attuned partners who create true safety.'],
            ['key' => 'leo',         'text' => '7th lord in Leo — drawn to warm, expressive partners who celebrate love with generosity.'],
            ['key' => 'virgo',       'text' => '7th lord in Virgo — drawn to thoughtful, capable partners who show love through service and care.'],
            ['key' => 'libra',       'text' => '7th lord in Libra — drawn to fair, elegant partners who bring balance and harmony.'],
            ['key' => 'scorpio',     'text' => '7th lord in Scorpio — drawn to intensely loyal partners who engage with love\'s full depth.'],
            ['key' => 'sagittarius', 'text' => '7th lord in Sagittarius — drawn to adventurous, honest partners who expand the world.'],
            ['key' => 'capricorn',   'text' => '7th lord in Capricorn — drawn to ambitious, reliable partners who take commitment seriously.'],
            ['key' => 'aquarius',    'text' => '7th lord in Aquarius — drawn to intellectually independent partners who value freedom in love.'],
            ['key' => 'pisces',      'text' => '7th lord in Pisces — drawn to empathic, spiritually inclined partners who love beyond the practical.'],
        ];
    }

    // ── synastry_seventh_lord_house (FULL — new section) ────────────────

    private function seventhLordHouseFull(): array
    {
        return [
            ['key' => 'h1',  'text' => 'The 7th house ruler falls in the <strong>1st house</strong> — partnership is central to identity. They find themselves most fully through relationship, and significant partners often mirror key aspects of who they are or are becoming.'],
            ['key' => 'h2',  'text' => 'The 7th house ruler falls in the <strong>2nd house</strong> — partnership is linked to material security and shared values. Important relationships often involve shared resources, financial interdependence, or a deep alignment of what each person holds precious.'],
            ['key' => 'h3',  'text' => 'The 7th house ruler falls in the <strong>3rd house</strong> — communication is the lifeblood of partnership. They need a partner they can genuinely talk to — intellectual rapport, shared curiosity, and active dialogue are as important as emotional connection.'],
            ['key' => 'h4',  'text' => 'The 7th house ruler falls in the <strong>4th house</strong> — partnerships are rooted in home and family. They seek a partner with whom they can build a true domestic life — someone who values roots, continuity, and the safety of a shared private world.'],
            ['key' => 'h5',  'text' => 'The 7th house ruler falls in the <strong>5th house</strong> — love is meant to be joyful. They are drawn to partnerships with creative spark, romance, and play. The relationship needs to feel alive — not just stable, but genuinely fun and expressive.'],
            ['key' => 'h6',  'text' => 'The 7th house ruler falls in the <strong>6th house</strong> — partnership often develops through shared work, daily life, or health. They show love through doing and being useful. Important relationships may begin in professional settings or through acts of practical devotion.'],
            ['key' => 'h7',  'text' => 'The 7th house ruler falls in the <strong>7th house</strong> — partnership is a defining life theme. Relationships carry great significance and often a sense of fate. They have a strong capacity for commitment and tend to attract powerful, formative partners.'],
            ['key' => 'h8',  'text' => 'The 7th house ruler falls in the <strong>8th house</strong> — partnerships are intense and transformative. They tend to attract deep, sometimes complicated bonds that fundamentally change who they are. Shared finances or crises often play a significant role.'],
            ['key' => 'h9',  'text' => 'The 7th house ruler falls in the <strong>9th house</strong> — partnerships are vehicles for growth, exploration, and meaning. They are drawn to partners with strong philosophies, foreign backgrounds, or broad horizons. Love expands their world.'],
            ['key' => 'h10', 'text' => 'The 7th house ruler falls in the <strong>10th house</strong> — partnership intersects with public life and ambition. Important relationships often support or shape their career path. Partners tend to be visible, accomplished, or socially significant.'],
            ['key' => 'h11', 'text' => 'The 7th house ruler falls in the <strong>11th house</strong> — friendship is the foundation of love. They are drawn to partners who are also true friends and allies — people who share their ideals, social values, and vision for the future.'],
            ['key' => 'h12', 'text' => 'The 7th house ruler falls in the <strong>12th house</strong> — partnerships carry a deep spiritual or hidden dimension. Important bonds often involve sacrifice, seclusion, or profound inner work. Love may feel fated, mysterious, or tied to past lives.'],
        ];
    }

    // ── synastry_seventh_lord_house_short ────────────────────────────────

    private function seventhLordHouseShort(): array
    {
        return [
            ['key' => 'h1',  'text' => '7th lord in H1 — partnership is central to identity; partners often mirror who they are becoming.'],
            ['key' => 'h2',  'text' => '7th lord in H2 — partnerships connect to shared values, security, and material interdependence.'],
            ['key' => 'h3',  'text' => '7th lord in H3 — intellectual rapport and genuine communication are essential in partnership.'],
            ['key' => 'h4',  'text' => '7th lord in H4 — partners are rooted in home and family; they seek someone to build a life with.'],
            ['key' => 'h5',  'text' => '7th lord in H5 — love needs joy, romance, and creative spark; playfulness is essential.'],
            ['key' => 'h6',  'text' => '7th lord in H6 — love is expressed through service; partners often meet through work or daily life.'],
            ['key' => 'h7',  'text' => '7th lord in H7 — partnerships are a defining life theme; commitment and depth come naturally.'],
            ['key' => 'h8',  'text' => '7th lord in H8 — partnerships are intense and transformative; shared crises deepen bonds.'],
            ['key' => 'h9',  'text' => '7th lord in H9 — partners expand the worldview; love is tied to growth, travel, and meaning.'],
            ['key' => 'h10', 'text' => '7th lord in H10 — partnerships intersect with career; partners tend to be accomplished and visible.'],
            ['key' => 'h11', 'text' => '7th lord in H11 — friendship is the foundation of love; partners share ideals and social vision.'],
            ['key' => 'h12', 'text' => '7th lord in H12 — partnerships have a hidden or spiritual dimension; love may feel fated.'],
        ];
    }

    // ── synastry_partner_male_same ───────────────────────────────────────

    private function partnerMaleSame(): array
    {
        return [
            ['key' => 'moon_aquarius',   'text' => 'He is drawn to an independent, intellectually alive, and emotionally unpredictable man. He needs space and respects the same in his partner. He is nurtured by a man who does not try to possess him emotionally — friendship is the foundation of love for him.'],
            ['key' => 'moon_aries',      'text' => 'He is drawn to men who are direct, decisive, and emotionally independent. He feels most comfortable with someone who does not demand constant reassurance — a man who acts on his feelings rather than dwelling in them. He is nurtured by strength and spontaneity.'],
            ['key' => 'moon_cancer',     'text' => 'He is drawn to nurturing, intuitive, and emotionally expressive men. He wants someone who creates a true home — physically and emotionally. He is nurtured by tenderness, care, and a man who remembers the small things that make him feel seen.'],
            ['key' => 'moon_capricorn',  'text' => 'He is drawn to men who are emotionally grounded, responsible, self-sufficient, and composed. He needs reliability over spontaneous affection. He is nurtured by a man who is capable and serious about life — someone who takes care of himself and honours commitments. This partnership thrives on mutual respect for emotional restraint and practical stability.'],
            ['key' => 'moon_gemini',     'text' => 'He is drawn to men who are communicative, curious, and adaptable. He needs someone he can talk to — a man who is light with feelings but rich in ideas. He is nurtured by playful connection and freedom from emotional heaviness.'],
            ['key' => 'moon_leo',        'text' => 'He is drawn to warm, generous, and expressive men who celebrate him and enjoy being celebrated in return. He wants someone proud and playful — a man who brings warmth and drama in equal measure. He is nurtured by admiration and a generous emotional presence.'],
            ['key' => 'moon_libra',      'text' => 'He is drawn to a balanced, gracious, and peaceful man who brings harmony to his emotional world. He needs someone who avoids extremes and approaches conflict with elegance and diplomacy. He is nurtured by fairness, beauty, and the calm stability of an equitable partnership.'],
            ['key' => 'moon_pisces',     'text' => 'He is drawn to gentle, empathic, and imaginative men who feel deeply. He is nurtured by someone who has a rich inner world — intuitive, creative, and spiritually oriented. He wants a man who softens reality and makes him feel understood without words.'],
            ['key' => 'moon_sagittarius','text' => 'He is drawn to optimistic, adventurous, and philosophically open men who share his emotional comfort with exploration. He needs space — emotionally and physically. He is nurtured by a man who is his own free spirit, who does not cling, and who keeps life feeling like an adventure.'],
            ['key' => 'moon_scorpio',    'text' => 'He is drawn to emotionally intense, loyal, and perceptive men. He wants depth — someone who can handle his complexity without flinching. He is nurtured by total emotional honesty, loyalty beyond question, and a man who sees all of him.'],
            ['key' => 'moon_taurus',     'text' => 'He is drawn to men who are steady, sensual, and reliably present. He feels emotionally safe with someone who values routine, comfort, and physical affection. He is nurtured by a man who builds a beautiful, stable world and does not create unnecessary drama.'],
            ['key' => 'moon_virgo',      'text' => 'He is drawn to a thoughtful, capable, and attentive man who expresses affection through acts of care and attention to detail. He appreciates someone whose presence brings order and competence to shared life. He is nurtured by reliability and a man who makes life run smoothly through his consistent, grounded nature.'],
            ['key' => 'venus_aquarius',  'text' => 'He is drawn to unconventional, intellectually independent men who think for themselves. He appreciates originality, social awareness, and a certain cool detachment in a partner. He wants someone who is his own person — a man who challenges his mind and respects his need for freedom. His ideal match shares his progressive values and refuses to conform to traditional relationship expectations.'],
            ['key' => 'venus_aries',     'text' => 'He is drawn to bold, self-assured men who know what they want. He is attracted by confidence, directness, and a spark of competitive fire. The chase matters to him — he is magnetised by someone who does not wait. He craves a partner who is independent and unapologetically himself.'],
            ['key' => 'venus_cancer',    'text' => 'He is drawn to nurturing, emotionally attuned men who feel like home. He gravitates toward someone soft, intuitive, and protective — a man who creates warmth and safety. He wants to feel deeply cared for and will offer that same devotion in return.'],
            ['key' => 'venus_capricorn', 'text' => 'He is drawn to ambitious, composed, and quietly powerful men. He values substance over flash — a man with goals, self-discipline, and elegance. He is attracted to someone who has built something real and who approaches life with the same seriousness he does.'],
            ['key' => 'venus_gemini',    'text' => 'He is drawn to witty, curious, versatile men who keep him mentally engaged. He falls for intelligence and playfulness. Boredom is his biggest turn-off — he needs someone who surprises him with conversation, ideas, and a light touch on life.'],
            ['key' => 'venus_leo',       'text' => 'His Leo placement is attracted to radiant, warm-hearted men who light up a room. He loves someone with self-confidence, personal style, and a generous heart. He is drawn to men who carry themselves with dignity and who appreciate being adored.'],
            ['key' => 'venus_libra',     'text' => 'He is drawn to elegant, charming, and socially graceful men. He loves beauty, diplomacy, and someone who carries himself with ease in any situation. He is attracted to a partner who values fairness and aesthetic refinement — someone who makes everything more beautiful simply by being present.'],
            ['key' => 'venus_pisces',    'text' => 'His Pisces placement is attracted to soft, intuitive, and spiritually inclined men who seem to exist in a world of their own. He is drawn to empathy, creativity, and a touch of mystery. He wants someone who sees beyond the surface — sensitive, compassionate, and deeply feeling.'],
            ['key' => 'venus_sagittarius','text'=> 'He is drawn to adventurous, free-spirited men with strong beliefs and a love of life. He wants someone who explores the world alongside him — intellectually, philosophically, physically. He is attracted to men who laugh easily and who do not try to tame him. His ideal partner shares his passion for growth, travel, and philosophical inquiry, meeting him as an equal adventurer rather than a constraint.'],
            ['key' => 'venus_scorpio',   'text' => 'He is drawn to intense, mysterious, and emotionally deep men. He craves someone who does not reveal everything at once — the hidden layers fascinate him. He is attracted to power, depth, and a man who matches his all-or-nothing approach to love.'],
            ['key' => 'venus_taurus',    'text' => 'His Taurus placement draws him to sensual, grounded men who embody comfort and beauty. He values loyalty, physical presence, and a slow, reliable warmth. He is drawn to someone who enjoys the good things in life — food, touch, nature — and who offers stability alongside pleasure.'],
            ['key' => 'venus_virgo',     'text' => 'He is drawn to intelligent, thoughtful men with refined taste and quiet depth. He values competence, reliability, and someone who pays attention to detail. He is attracted to men who take care of themselves and the world around them with grace and intelligence.'],
        ];
    }

    // ── synastry_partner_female_same ─────────────────────────────────────

    private function partnerFemaleSame(): array
    {
        return [
            ['key' => 'mars_aquarius',   'text' => 'She is drawn to intellectually original, independent women who stand apart from the crowd. She wants someone who challenges convention and has her own vision. Magnetic eccentricity and respect for her autonomy are essential.'],
            ['key' => 'mars_aries',      'text' => 'She is drawn to assertive, courageous women who take initiative. She is attracted by confidence, directness, and decisive action. She wants someone who goes after what she wants — including her. A woman who hesitates loses her attention.'],
            ['key' => 'mars_cancer',     'text' => 'She is drawn to emotionally available, protective, and nurturing women. She wants someone who shows her feelings and makes her feel safe. A woman who builds a home and expresses vulnerability without shame draws her deeply.'],
            ['key' => 'mars_capricorn',  'text' => 'She is drawn to ambitious, disciplined, and accomplished women. She respects drive and competence. A woman who has clear goals and the self-control to pursue them — quietly and consistently — is profoundly attractive to her.'],
            ['key' => 'mars_gemini',     'text' => 'She is drawn to intellectually agile, witty, and verbally expressive women. She wants someone who can keep up with her mind — quick, curious, and full of ideas. A woman who can make her laugh and think simultaneously is irresistible.'],
            ['key' => 'mars_leo',        'text' => 'She is drawn to confident, generous, and charismatic women who know how to lead. She wants someone who takes pride in herself and makes her feel chosen. A woman with presence, style, and the courage to express herself fully attracts her.'],
            ['key' => 'mars_libra',      'text' => 'She is drawn to charming, socially intelligent women who know how to treat her well. She wants elegance and fairness — a woman who listens, considers, and acts with grace. She is attracted to those who make relationships feel effortless and balanced.'],
            ['key' => 'mars_pisces',     'text' => 'She is drawn to sensitive, imaginative, and spiritually inclined women. She is attracted by gentleness and depth of feeling — a woman in touch with her inner world. Creativity, compassion, and a touch of the mystical draw her.'],
            ['key' => 'mars_sagittarius','text' => 'She is drawn to adventurous, enthusiastic women with a strong sense of purpose. She wants someone with vision — a woman who travels, learns, and lives expansively. Intellectual boldness and a sense of humour are essential to her romantic connections.'],
            ['key' => 'mars_scorpio',    'text' => 'She is drawn to powerful, magnetic, and emotionally intense women. She wants depth over surface — a woman who does not scare easily and who can match her passion. Control, mystery, and unwavering desire are her greatest attractions.'],
            ['key' => 'mars_taurus',     'text' => 'She is drawn to steady, sensual, and physically present women. She wants someone solid and reliable — a woman who works with her hands and her heart, who builds things slowly and well. Patience and consistency ignite her passion.'],
            ['key' => 'mars_virgo',      'text' => 'She is drawn to capable, intelligent, and detail-oriented women. She wants someone who takes care of things — who is reliable and skilled. A woman who is quietly competent and does not need to boast about her abilities is deeply appealing. Her partner should be practical, organized, and genuinely interested in self-improvement and helping others thrive.'],
            ['key' => 'sun_aquarius',    'text' => 'Her Sun in Aquarius is drawn to intellectually independent, unconventional women with vision and principle. She wants a partner in ideas, not just in life.'],
            ['key' => 'sun_aries',       'text' => 'Her Sun in Aries respects women who are bold, decisive, and unafraid of risk. She is drawn to partners who meet her fire with their own — direct, courageous, and competitive in the best sense. She thrives alongside women who inspire her through action rather than words, and who aren\'t afraid to challenge her when needed. An independent woman who can stand her ground makes her feel truly alive in love.'],
            ['key' => 'sun_cancer',      'text' => 'Her Sun in Cancer chooses women who are emotionally present, protective, and capable of deep attachment. She wants a woman who makes home sacred.'],
            ['key' => 'sun_capricorn',   'text' => 'Her Sun in Capricorn respects women who are ambitious, responsible, and quietly powerful. She is drawn to someone building something — including a future with her.'],
            ['key' => 'sun_gemini',      'text' => 'Her Sun in Gemini is drawn to clever, communicative women who engage her intellectually. She wants a partner who keeps her curious — quick, witty, and never predictable.'],
            ['key' => 'sun_leo',         'text' => 'Her Sun in Leo is drawn to women with genuine confidence, warmth, and a flair for life. She wants someone who sees her brilliance and matches it with her own. This partner should be naturally magnetic, comfortable in the spotlight, and willing to celebrate her achievements without dimming her shine. Together, they create a dynamic where both can express their creative power and radiate their authentic selves.'],
            ['key' => 'sun_libra',       'text' => 'Her Sun in Libra is drawn to refined, considerate women who value harmony and mutual respect. She wants a true partner — someone who balances her gracefully.'],
            ['key' => 'sun_pisces',      'text' => 'Her Sun in Pisces is drawn to women who are empathic, creative, and spiritually oriented. She wants someone who understands feeling as a language.'],
            ['key' => 'sun_sagittarius', 'text' => 'She is drawn to adventurous, philosophically alive women who embrace life\'s expanse. Her Sagittarius Sun seeks a companion for the journey, not just the destination. She values partners who share her enthusiasm for exploration and growth. A woman who can engage with big ideas and spontaneous experiences captivates her. She needs someone willing to expand horizons together rather than settle into routine.'],
            ['key' => 'sun_scorpio',     'text' => 'Her Sun in Scorpio is drawn to women of depth, loyalty, and unwavering intensity. She wants total commitment or nothing.'],
            ['key' => 'sun_taurus',      'text' => 'Her Sun in Taurus is drawn to women who are grounded, dependable, and sensually present. She wants substance — a woman who builds, provides, and stays.'],
            ['key' => 'sun_virgo',       'text' => 'Her Sun in Virgo values women who are thoughtful, responsible, and quietly capable. She is attracted to intelligence applied to real-world problems. This woman seeks a partner who demonstrates competence through actions rather than words, someone whose practical wisdom solves everyday challenges with ease. Her ideal match is grounded, organized, and able to manage life\'s complexities without drama. She is drawn to women whose reliability and genuine care for details mirror her own exacting standards.'],
        ];
    }
}
