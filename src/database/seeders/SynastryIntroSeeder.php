<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored synastry intro text blocks — Sun sign × Sun sign.
 *
 * Section: synastry_intro
 * Keys:    {sign_a}_{sign_b}  (sign_a always has lower zodiac index)
 *          e.g. aries_taurus, not taurus_aries
 * Total:   78 blocks (12 same-sign + 66 cross-sign pairs)
 *
 * Lookup convention: sort both signs by zodiac index (0=aries…11=pisces),
 * join with underscore → direction-agnostic key.
 */
class SynastryIntroSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'synastry_intro',
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
    }

    private function blocks(): array
    {
        return [

            // ── Aries ────────────────────────────────────────────────────────

            [
                'key'  => 'aries_aries',
                'text' => 'Two <em>Aries</em> Suns create a relationship of immediate intensity and shared drive. <strong>The energy between you is electric and competitive</strong> — you understand each other\'s need for speed, independence, and being first without explanation. The risk is friction when both insist on leading simultaneously, and a tendency to escalate conflict rather than yield. At its best, this pairing produces extraordinary momentum; at its most challenging, two fires without sufficient air can consume rather than warm.',
            ],
            [
                'key'  => 'aries_taurus',
                'text' => '<em>Aries</em> brings momentum and desire for the new; <em>Taurus</em> brings patience and the need for things to last. <strong>This is a pairing of initiator and sustainer</strong> — together you can start and finish what neither would complete alone. Friction arises around pace: Aries moves fast, Taurus deliberates. But beneath the difference in tempo lies a genuine complementarity, and when both people are secure enough to appreciate rather than resent what the other brings, the pairing has real staying power.',
            ],
            [
                'key'  => 'aries_gemini',
                'text' => 'Two signs that share a love of movement, novelty, and engagement with the world. <strong>The conversation between you rarely goes quiet</strong>, and the mental stimulation each provides the other is a genuine source of pleasure. Aries provides direction and fire when <em>Gemini</em>\'s curiosity scatters; Gemini provides flexibility and wit when <em>Aries</em> becomes single-minded. The challenge is depth — both signs can skim the surface of experience, and the relationship needs deliberate cultivation of stillness and substance to mature.',
            ],
            [
                'key'  => 'aries_cancer',
                'text' => 'A pairing between two cardinal signs who both want to lead — but in entirely different arenas. <strong><em>Aries</em> leads outward; <em>Cancer</em> leads inward</strong>: one drives into the world, the other shapes the emotional home. Aries is direct where Cancer is indirect, impulsive where Cancer is protective. But there is a genuine attraction between their opposite approaches to need and desire, and the relationship often produces a kind of complementary wholeness when both feel genuinely secure.',
            ],
            [
                'key'  => 'aries_leo',
                'text' => 'Two fire signs who meet as equals in energy and ambition. <strong>The connection feels bright and vital</strong>, full of mutual admiration, creative enthusiasm, and a shared love of living fully. Both <em>Aries</em> and <em>Leo</em> know how to make the other feel seen and celebrated, which sustains the bond with unusual warmth. The challenge is ego: when both need to be the center simultaneously, sparks can turn to conflict. But the natural generosity of both signs usually finds a way through.',
            ],
            [
                'key'  => 'aries_virgo',
                'text' => 'A pairing between the impulse and the edit — <em>Aries</em> acts, <em>Virgo</em> refines. <strong>What Aries initiates, Virgo improves</strong>, and in the best version of this relationship each person becomes significantly more effective because of the other. The friction lies in different relationships to perfectionism: Aries finds Virgo\'s criticism deflating; Virgo finds Aries\'s impatience careless. Patience on both sides reveals a genuinely productive and complementary dynamic that can accomplish more than either would manage separately.',
            ],
            [
                'key'  => 'aries_libra',
                'text' => 'The classic opposition: <em>Aries</em> is self, <em>Libra</em> is other. <strong>Each embodies what the other most needs to develop</strong>, which creates a powerful magnetic pull alongside persistent tension. Aries teaches Libra decisiveness and individual desire; Libra teaches Aries diplomacy and the art of genuine partnership. The polarity that draws them together is the same force that creates friction. When both people engage the challenge honestly rather than insisting the other simply change, this becomes one of the most growth-producing pairings in synastry.',
            ],
            [
                'key'  => 'aries_scorpio',
                'text' => 'Two signs ruled by <em>Mars</em> — but expressing its energy in radically different ways. <strong><em>Aries</em> burns openly; <em>Scorpio</em> burns beneath the surface</strong>, and the combination creates an intensity that is difficult to ignore. Mutual attraction is almost guaranteed; sustained trust requires time and genuine transparency. The relationship can be deeply transformative or deeply destabilizing, depending on the maturity of both people. Neither sign backs away from a challenge, which means the relationship either goes very deep or ends very dramatically.',
            ],
            [
                'key'  => 'aries_sagittarius',
                'text' => 'Two fire signs united by love of freedom, adventure, and direct expression. <strong>The connection feels expansive and alive</strong>, full of shared enthusiasm for new experience and mutual respect for independence. Neither <em>Aries</em> nor <em>Sagittarius</em> clings or demands constant presence, which paradoxically makes the relationship feel safe for both. The challenge is grounding: without earth energy to anchor it, the relationship can burn brightly but fail to build anything lasting. The natural joy between these two signs is genuine and sustaining.',
            ],
            [
                'key'  => 'aries_capricorn',
                'text' => 'A cardinal sign square — two leaders who lead in opposite directions. <strong><em>Aries</em> wants to break ground; <em>Capricorn</em> wants to build something permanent</strong>, and the tension between these drives is both the challenge and the gift of the pairing. Capricorn finds Aries impulsive; Aries finds Capricorn risk-averse. But when the relationship matures, each provides exactly what the other lacks: Aries gives momentum and courage, Capricorn gives structure and staying power. The combination can be formidably effective.',
            ],
            [
                'key'  => 'aries_aquarius',
                'text' => 'A pairing with natural affinity for independence, originality, and forward motion. <strong>Both <em>Aries</em> and <em>Aquarius</em> resist conformity and value freedom</strong>, which creates an unusual mutual respect for the other\'s need for space. The relationship feels modern and uncomplicated by traditional expectations. The challenge is emotional depth: neither sign prioritizes feelings over action or ideas, and the bond can remain stimulating but somewhat thin without deliberate attention to vulnerability and intimacy. When both choose depth, this pairing is genuinely distinctive.',
            ],
            [
                'key'  => 'aries_pisces',
                'text' => 'A meeting between the first and last signs of the zodiac — beginning and completion. <strong><em>Aries</em> brings direction and energy to <em>Pisces</em>\'s diffuse world; Pisces brings depth and compassion to Aries\'s forward drive.</strong> The pairing can feel both nourishing and destabilizing: Aries can find Pisces evasive, Pisces can find Aries overwhelming. But there is a genuine tenderness in the connection that often surprises both parties, and the relationship has an archetypal quality — like something that needed to happen.',
            ],

            // ── Taurus ───────────────────────────────────────────────────────

            [
                'key'  => 'taurus_taurus',
                'text' => 'Two <em>Taurus</em> Suns create a relationship of profound stability, sensory pleasure, and shared values. <strong>The connection feels immediately safe and deeply comfortable</strong> — neither party demands sudden change or emotional intensity, and the pace of the relationship feels natural to both. The risk is stagnation: two fixed earth signs can resist necessary evolution and become too comfortable with the familiar. But the loyalty and physical warmth of this pairing are genuinely difficult to replicate.',
            ],
            [
                'key'  => 'taurus_gemini',
                'text' => 'The meeting of earthly steadiness and mental agility — one who wants things to stay, one who needs them to move. <strong><em>Gemini</em>\'s curiosity delights <em>Taurus</em>; Taurus\'s solidity grounds Gemini</strong> in ways neither fully expects. The friction is pacing and priorities: Taurus finds Gemini scattered, Gemini finds Taurus inflexible. But the complementarity is real: this pairing tends to produce relationships that are both interesting and lasting when both people learn to value what the other offers rather than demand they become more similar.',
            ],
            [
                'key'  => 'taurus_cancer',
                'text' => 'Two signs that share a deep commitment to security, warmth, and nurturing. <strong>The connection feels instinctively safe and emotionally nourishing</strong> — both <em>Taurus</em> and <em>Cancer</em> want to build something lasting and understand the language of comfort and care. There is a natural tenderness between them and a shared delight in the pleasures of home and physical life. The challenge is over-attachment: both signs can hold on too long when change is needed, and the relationship benefits from deliberate openness to growth.',
            ],
            [
                'key'  => 'taurus_leo',
                'text' => 'A fixed sign square — two strong wills meeting with genuine mutual admiration and stubborn resistance. <strong><em>Taurus</em> appreciates <em>Leo</em>\'s warmth; Leo appreciates Taurus\'s loyalty</strong>, and the connection has a steadiness and pleasure that both signs value. The friction appears when neither will yield: fixed signs have difficulty adapting, and both can dig in rather than find common ground. When both choose generosity over pride, this pairing has remarkable staying power.',
            ],
            [
                'key'  => 'taurus_virgo',
                'text' => 'Two earth signs who share a practical orientation to life and a deep appreciation for craftsmanship, reliability, and careful attention. <strong>The connection feels grounded, trustworthy, and quietly satisfying</strong> — neither person needs to perform or maintain a facade in the other\'s presence. <em>Taurus</em> and <em>Virgo</em> work well together and understand each other\'s need for competence and order. The challenge is over-caution: both signs can default to risk-aversion, and the relationship may need periodic encouragement to take the risks that allow it to grow.',
            ],
            [
                'key'  => 'taurus_libra',
                'text' => 'Both signs are ruled by <em>Venus</em> — one expresses it through the senses and the material world, the other through aesthetics, relationship, and social grace. <strong>The connection has an inherent beauty and ease</strong> — shared pleasure in art, comfort, and the good things in life comes naturally to both <em>Taurus</em> and <em>Libra</em>. The friction lies in values: Taurus is concrete and possessive, Libra is conceptual and changeable. But Venus\'s influence on both creates genuine warmth and a shared love of harmony.',
            ],
            [
                'key'  => 'taurus_scorpio',
                'text' => 'The most direct opposition in the zodiac — earth and water, possession and transformation, security and change. <strong>Each embodies what the other most fears and most needs</strong>, which creates a magnetic pull of unusual intensity. <em>Taurus</em> brings physical groundedness and steadfast loyalty; <em>Scorpio</em> brings psychological depth and transformative power. The tension is real and persistent, but the attraction is equally so. This pairing tends toward extremes: deeply bonding or deeply destabilizing, with little comfortable middle ground.',
            ],
            [
                'key'  => 'taurus_sagittarius',
                'text' => 'A pairing between the fixed and the free — one who wants roots, one who wants wings. <strong><em>Taurus</em> provides the stability and physical warmth that <em>Sagittarius</em> returns to; Sagittarius provides the broader horizons and adventurous perspective that Taurus sometimes craves.</strong> The friction is around commitment and change: Taurus holds on, Sagittarius moves forward. But the differences can be deeply complementary when both people are secure enough to appreciate rather than attempt to eliminate what the other brings.',
            ],
            [
                'key'  => 'taurus_capricorn',
                'text' => 'Two earth signs who share a fundamental orientation toward building, persistence, and material reality. <strong>The connection feels solid, purposeful, and quietly powerful</strong> — both <em>Taurus</em> and <em>Capricorn</em> understand long-term thinking, the value of effort, and the satisfaction of tangible results. Trust builds steadily and tends to last. The challenge is emotional expressiveness: two earth signs can create structures without creating vulnerability, and the relationship needs deliberate cultivation of tenderness and inner life to feel complete.',
            ],
            [
                'key'  => 'taurus_aquarius',
                'text' => 'A fixed sign square between earth and air — one grounded in the sensory and the familiar, one oriented toward the abstract and the future. <strong><em>Taurus</em> finds <em>Aquarius</em> destabilizing; Aquarius finds Taurus limiting</strong>, yet each is secretly curious about what the other has. At its best, Taurus grounds Aquarius\'s idealism in practical reality, while Aquarius expands Taurus\'s sense of what is possible. The relationship demands genuine tolerance of fundamental difference and rewards those who persist with something genuinely creative.',
            ],
            [
                'key'  => 'taurus_pisces',
                'text' => 'An earth-water combination of natural affinity and gentle complementarity. <strong><em>Taurus</em> provides the solid ground that <em>Pisces</em> needs; Pisces provides the emotional and imaginative depth that Taurus finds quietly nourishing.</strong> Both signs move slowly, value comfort, and bring genuine tenderness to their relationships. The challenge is boundary-setting: both can become too accommodating, avoiding necessary friction. But the warmth between these two signs is genuine and sustaining, and the relationship often has an unusually peaceful quality.',
            ],

            // ── Gemini ───────────────────────────────────────────────────────

            [
                'key'  => 'gemini_gemini',
                'text' => 'Two <em>Gemini</em> Suns create a relationship of extraordinary mental vitality, constant stimulation, and mutual appreciation for complexity. <strong>The conversation never runs dry, and the pleasure of being genuinely understood in one\'s restlessness is real.</strong> The risk is that two quicksilver minds can circle endlessly without landing — the relationship may feel alive but resist the depth and consistency that allow bonds to mature. Grounding forces elsewhere in both charts can make an enormous difference.',
            ],
            [
                'key'  => 'gemini_cancer',
                'text' => 'The meeting of mind and feeling — one who processes through language and ideas, one who processes through emotion and instinct. <strong><em>Gemini</em>\'s wit and curiosity delight <em>Cancer</em>; Cancer\'s depth and care quietly anchor Gemini.</strong> The friction is around different modes of understanding: Gemini can seem emotionally evasive to Cancer, Cancer can seem irrationally sensitive to Gemini. But beneath the difference lies a genuine complementarity, and the relationship often produces unusual intimacy when both people choose to learn the other\'s language.',
            ],
            [
                'key'  => 'gemini_leo',
                'text' => 'A natural affinity between air and fire — <em>Gemini</em> fans <em>Leo</em>\'s flame, Leo gives Gemini\'s restlessness a center. <strong>The connection feels lively, warm, and mutually appreciative</strong>, with Leo providing the charisma and direction that Gemini enjoys engaging, and Gemini providing the intellectual stimulation and social ease that Leo finds genuinely nourishing. Both signs love an audience and understand each other\'s need for engagement and admiration. The chemistry between them tends to be immediate and enjoyable.',
            ],
            [
                'key'  => 'gemini_virgo',
                'text' => 'Two <em>Mercury</em>-ruled signs who approach the world through analysis, communication, and attention to detail — but with entirely different styles. <strong><em>Gemini</em> roams widely; <em>Virgo</em> goes deep</strong> — one collects information, the other refines it. The intellectual chemistry is often strong, and both signs appreciate precision and nuance. The friction lies in different relationships to commitment: Virgo finds Gemini superficial, Gemini finds Virgo over-critical. Mutual respect for the other\'s form of intelligence is the key to this pairing.',
            ],
            [
                'key'  => 'gemini_libra',
                'text' => 'Two air signs who share a natural affinity for ideas, communication, and social grace. <strong>The connection feels easy, stimulating, and delightful</strong> — both <em>Gemini</em> and <em>Libra</em> are at home in the world of language, relationship, and the exchange of perspectives. Neither demands emotional heaviness or dramatic intensity, which can feel like relief. The challenge is depth: two air signs in harmony can create a relationship that is pleasant but avoids the difficult emotional terrain that allows genuine intimacy to develop.',
            ],
            [
                'key'  => 'gemini_scorpio',
                'text' => 'A meeting between surface and depth — two signs that couldn\'t approach the world more differently. <strong><em>Gemini</em>\'s lightness intrigues <em>Scorpio</em>; Scorpio\'s intensity draws Gemini toward territory it rarely visits.</strong> Scorpio finds Gemini evasive and emotionally shallow; Gemini finds Scorpio overwhelming and controlling. But the polarity creates genuine fascination, and when both people are willing to be changed by the encounter, the relationship can open up dimensions in each person that had been previously inaccessible.',
            ],
            [
                'key'  => 'gemini_sagittarius',
                'text' => 'The polarity axis of mind and meaning — two signs united by insatiable curiosity and the love of ideas, but approaching from opposite ends. <strong><em>Gemini</em> collects; <em>Sagittarius</em> synthesizes</strong> — together they span an extraordinary range of experience and perspective. The opposition creates both attraction and friction: Gemini finds Sagittarius dogmatic, Sagittarius finds Gemini scattered. But the intellectual chemistry is almost always strong, and the relationship has a quality of ongoing adventure and discovery that both signs genuinely value.',
            ],
            [
                'key'  => 'gemini_capricorn',
                'text' => 'An unlikely but potentially very productive meeting between mercurial adaptability and structured ambition. <strong><em>Gemini</em> brings flexibility, wit, and communicative ease to <em>Capricorn</em>\'s world; Capricorn brings focus, ambition, and the longer view to Gemini\'s.</strong> Each has something the other genuinely lacks. Capricorn finds Gemini uncommitted, Gemini finds Capricorn rigid. But the complementarity can be powerful when both people trust each other enough to let the other\'s strengths fill their gaps.',
            ],
            [
                'key'  => 'gemini_aquarius',
                'text' => 'Two air signs with natural affinity for the unconventional, the intellectual, and the future-oriented. <strong>The mental connection between <em>Gemini</em> and <em>Aquarius</em> is strong and immediate</strong> — ideas flow freely, neither demands emotional conformity, and there is a mutual respect for originality and independent thinking. Both signs value freedom, which creates unusual ease. The challenge, as with all air pairings, is emotional depth. The relationship can remain brilliantly stimulating while avoiding the vulnerability that allows it to truly bond.',
            ],
            [
                'key'  => 'gemini_pisces',
                'text' => 'A mutable sign square between air and water — both signs are changeable, imaginative, and difficult to pin down. <strong>The connection has a dreamlike quality</strong>, full of creative potential and mutual fascination. <em>Gemini</em>\'s intellect and <em>Pisces</em>\'s intuition can create something genuinely distinctive between them. The friction lies in different relationships to reality: Gemini seeks clarity, Pisces seeks meaning, and these orientations sometimes talk past each other. But the imagination each brings to the other\'s world is a genuine gift.',
            ],

            // ── Cancer ───────────────────────────────────────────────────────

            [
                'key'  => 'cancer_cancer',
                'text' => 'Two <em>Cancer</em> Suns create a relationship of profound emotional resonance, mutual nurturing, and deep instinctive understanding. <strong>The connection feels like coming home in the most essential sense</strong> — both people understand the language of feeling, care, and protection without explanation. The risk is emotional amplification: two water signs without sufficient air can create a closed emotional world, and the relationship may need outside stimulation to avoid becoming insular. But the tenderness between them is genuine and rare.',
            ],
            [
                'key'  => 'cancer_leo',
                'text' => 'A pairing between water and fire — one who nurtures from the inside, one who radiates outward. <strong><em>Cancer</em>\'s care and emotional depth touch <em>Leo</em> in ways that are quietly transformative; Leo\'s warmth and generosity make Cancer feel genuinely cherished.</strong> The friction lies in different emotional languages: Cancer processes inward and privately, Leo expresses outward and publicly. But when both signs feel secure, the combination produces unusual warmth and genuine devotion. Each has what the other most deeply wants.',
            ],
            [
                'key'  => 'cancer_virgo',
                'text' => 'Two signs that share a deep orientation toward service, care, and the practical details of everyday life. <strong>The connection has a natural warmth and reliability</strong> — both <em>Cancer</em> and <em>Virgo</em> are genuinely invested in the other\'s wellbeing, and neither is drawn to drama for its own sake. The emotional safety between them builds steadily. The challenge is that both signs can default to anxiety and self-criticism, and the relationship benefits from deliberate cultivation of joy and self-compassion alongside the care they naturally provide.',
            ],
            [
                'key'  => 'cancer_libra',
                'text' => 'A cardinal sign square — two signs who want to create harmony and beauty, but through entirely different means. <strong><em>Cancer</em> creates home; <em>Libra</em> creates relationship</strong> — one works inward, one works between. Cancer finds Libra emotionally unavailable, Libra finds Cancer too demanding. But both signs genuinely value beauty, care, and the quality of connection, and when each person feels secure enough to appreciate rather than resist the other\'s approach, the pairing has a particular grace.',
            ],
            [
                'key'  => 'cancer_scorpio',
                'text' => 'A deep water-to-water connection — two signs who speak the language of feeling, depth, and emotional intensity. <strong>The understanding between <em>Cancer</em> and <em>Scorpio</em> is immediate and goes below the surface</strong> in ways that are difficult to explain to those outside the relationship. Both signs feel genuinely safe in the depths with each other, which is rare. The challenge is mutual intensity: when both people are in emotional difficulty simultaneously, there is no one to provide the grounded perspective. Outside influences help.',
            ],
            [
                'key'  => 'cancer_sagittarius',
                'text' => 'A meeting between emotional depth and philosophical breadth — one who moves inward, one who moves outward. <strong><em>Cancer</em>\'s nurturing creates a home base from which <em>Sagittarius</em> ventures further; Sagittarius\'s adventurousness expands Cancer\'s often narrow emotional world.</strong> The tension is around freedom versus security: Sagittarius needs to roam, Cancer needs to feel certain the other will return. When both people are honest about their needs and generous in their responses, the pairing offers genuine growth for each.',
            ],
            [
                'key'  => 'cancer_capricorn',
                'text' => 'The axis of personal and professional, private and public. <strong><em>Cancer</em> holds the emotional interior; <em>Capricorn</em> holds the structural exterior</strong> — together they can create something unusually complete and stable. The opposition creates both attraction and challenge: Cancer finds Capricorn cold, Capricorn finds Cancer irrational. But what each person lacks, the other provides, and the relationship often develops into a genuine partnership of complementary strengths when both people commit to truly knowing each other.',
            ],
            [
                'key'  => 'cancer_aquarius',
                'text' => 'An unusual pairing between deeply personal emotional need and impersonal idealism. <strong><em>Cancer</em> needs to feel uniquely seen and cherished; <em>Aquarius</em> extends its care broadly and somewhat abstractly.</strong> Cancer finds Aquarius emotionally unavailable, Aquarius finds Cancer clingingly personal. But when both people genuinely respect the other\'s way of caring rather than demanding they become more similar, the relationship can be genuinely broadening — each opens up dimensions of experience the other would not access alone.',
            ],
            [
                'key'  => 'cancer_pisces',
                'text' => 'Two water signs in natural harmony — deeply empathic, imaginative, and attuned to the unseen dimensions of experience. <strong>The connection feels fluid, nourishing, and spiritually resonant</strong> — both <em>Cancer</em> and <em>Pisces</em> understand the language of feeling and the importance of the invisible. The risk is boundary dissolution: two water signs without grounding can lose themselves in each other in ways that ultimately diminish rather than support both. Maintaining distinct identities is the primary work of this otherwise deeply nourishing pairing.',
            ],

            // ── Leo ──────────────────────────────────────────────────────────

            [
                'key'  => 'leo_leo',
                'text' => 'Two <em>Leo</em> Suns create a relationship of extraordinary warmth, mutual admiration, and shared radiance. <strong>The connection feels celebratory and vital</strong> — both people understand the language of generosity, recognition, and the desire to shine. The challenge is ego: two Suns in the same room both want the light, and when generosity gives way to competition for admiration, the warmth can become friction. The solution is what both signs are capable of at their best — genuine, wholehearted celebration of the other\'s gifts.',
            ],
            [
                'key'  => 'leo_virgo',
                'text' => 'A meeting between radiance and refinement — <em>Leo</em> brings warmth, confidence, and the instinct to express; <em>Virgo</em> brings precision, care, and the instinct to improve. <strong>What Leo creates, Virgo perfects</strong>, and the combination can be genuinely productive when both people appreciate rather than resent what the other offers. Leo finds Virgo\'s criticism deflating; Virgo finds Leo\'s self-promotion excessive. Beneath the different styles is a shared desire for excellence, and the relationship often achieves things neither would manage independently.',
            ],
            [
                'key'  => 'leo_libra',
                'text' => 'Two signs that share a love of beauty, pleasure, and the social world — fire and air combining to create warmth, style, and genuine enjoyment of each other. <strong>The connection has a natural elegance and mutual appreciation</strong> that makes it easy and pleasurable. <em>Leo</em>\'s warmth draws out <em>Libra</em>\'s social grace; Libra\'s aesthetic sense and diplomacy bring out the best in Leo\'s public presentation. Both signs are naturally generous with admiration, which sustains the relationship with unusual ease.',
            ],
            [
                'key'  => 'leo_scorpio',
                'text' => 'A fixed sign square between two signs of extraordinary power and intensity. <strong>Neither <em>Leo</em> nor <em>Scorpio</em> yields easily</strong>, and the friction between their different expressions of strength is both the challenge and the extraordinary potential of the pairing. Leo burns openly; Scorpio burns beneath the surface. When the power struggle gives way to mutual respect, the relationship becomes one of remarkable depth and vitality. Both signs are capable of profound loyalty when they feel genuinely met.',
            ],
            [
                'key'  => 'leo_sagittarius',
                'text' => 'Two fire signs in harmonious trine — radiance and adventure, warmth and freedom combining in a connection that feels immediately vital and sustaining. <strong>The mutual appreciation between <em>Leo</em> and <em>Sagittarius</em> is genuine and lasting</strong>, and neither demands that the other be smaller than they are. Leo provides the warmth and creative center; Sagittarius provides the expansive vision and philosophical depth. The challenge is follow-through: two fire signs can generate enormous enthusiasm and then find the practical work less compelling.',
            ],
            [
                'key'  => 'leo_capricorn',
                'text' => 'A meeting between radiance and structure — <em>Leo</em> brings warmth, creativity, and the instinct to celebrate; <em>Capricorn</em> brings discipline, ambition, and the long view. <strong>Together they can build something genuinely impressive</strong> — Capricorn provides the structure Leo\'s creativity needs, Leo provides the vitality and warmth that Capricorn\'s ambition sometimes lacks. Leo needs public appreciation, Capricorn is privately driven. But the mutual respect between two strong signs can sustain a genuinely productive partnership.',
            ],
            [
                'key'  => 'leo_aquarius',
                'text' => 'The polarity of individual radiance and collective vision — <em>Leo</em> shines for itself and those it loves, <em>Aquarius</em> shines for the world. <strong>Each embodies what the other most needs to develop</strong>, creating the characteristic mix of magnetic attraction and persistent friction of an opposition. Leo teaches Aquarius the value of the personal, intimate, and heartfelt; Aquarius teaches Leo the larger context in which individual expression finds meaning. This pairing asks both people to hold their opposite simultaneously — difficult and genuinely transformative.',
            ],
            [
                'key'  => 'leo_pisces',
                'text' => 'A meeting between solar warmth and oceanic depth — fire and water in a combination that is both nourishing and challenging. <strong><em>Leo</em>\'s radiance illuminates <em>Pisces</em>\'s interior world; Pisces\'s compassion and depth touch Leo in ways that are quietly transformative.</strong> Leo can find Pisces evasive, Pisces can find Leo overwhelming. But the genuine care each extends to the other often creates a bond of unusual tenderness that surprises both parties with its staying power.',
            ],

            // ── Virgo ────────────────────────────────────────────────────────

            [
                'key'  => 'virgo_virgo',
                'text' => 'Two <em>Virgo</em> Suns create a relationship of deep mutual understanding, shared precision, and genuine intellectual respect. <strong>The connection feels unusually safe from judgment</strong> — both people understand the other\'s need for order, competence, and careful attention without explanation. The risk is mutual criticism: two Virgo minds directed at the same relationship can find more to improve than to simply enjoy. The relationship benefits greatly from deliberate cultivation of appreciation, lightness, and the acceptance of imperfection.',
            ],
            [
                'key'  => 'virgo_libra',
                'text' => 'A meeting between the analytical and the aesthetic — <em>Virgo</em> refines through precision, <em>Libra</em> refines through beauty and balance. <strong>Both signs share a genuine love of excellence and a desire for things to be right</strong>, though they define "right" through different lenses. Virgo finds Libra indecisive and superficial; Libra finds Virgo overly critical and unromantic. But the shared orientation toward refinement creates genuine common ground, and the relationship can produce a quietly satisfying combination of quality and grace.',
            ],
            [
                'key'  => 'virgo_scorpio',
                'text' => 'Two signs that share a taste for depth, precision, and the hidden layers of experience. <strong>The intellectual and psychological chemistry between <em>Virgo</em> and <em>Scorpio</em> is often remarkable</strong> — both minds operate below the surface, and both appreciate what is rarely seen. There is a quality of mutual recognition in this pairing that is difficult to describe to those outside it. The challenge is mutual intensity: both signs can be exacting and withholding, and the relationship needs deliberate cultivation of warmth and the willingness to be wrong.',
            ],
            [
                'key'  => 'virgo_sagittarius',
                'text' => 'A mutable sign square — both signs are adaptable and intellectually engaged, but in genuinely different ways. <strong><em>Virgo</em> narrows; <em>Sagittarius</em> expands</strong> — one seeks precision, the other seeks the big picture. The friction between these orientations is constant but productive: Virgo brings the critical thinking that tests Sagittarius\'s broad claims, Sagittarius brings the expansive perspective that rescues Virgo from excessive detail. When both people appreciate the other\'s contribution, this pairing produces unusually well-rounded thinking.',
            ],
            [
                'key'  => 'virgo_capricorn',
                'text' => 'Two earth signs in natural harmony — both practical, ambitious, and oriented toward quality and achievement. <strong>The connection feels grounded, purposeful, and quietly reliable</strong> — both <em>Virgo</em> and <em>Capricorn</em> understand the satisfaction of work done well and the value of building something that lasts. Trust comes relatively easily here. The challenge, as with all earth pairings, is emotional depth: the relationship can function beautifully on the practical level while remaining emotionally unexplored. Choosing vulnerability is the active work.',
            ],
            [
                'key'  => 'virgo_aquarius',
                'text' => 'An unusual meeting between earthy precision and airy idealism — two signs who both care about doing things correctly, but with radically different definitions of correct. <strong><em>Virgo</em> works from the specific; <em>Aquarius</em> works from the universal</strong>, and the gap between these orientations can be either deeply stimulating or deeply frustrating. Virgo finds Aquarius impractical, Aquarius finds Virgo limited. But when both people respect the other\'s intelligence, the combination produces remarkably creative problem-solving.',
            ],
            [
                'key'  => 'virgo_pisces',
                'text' => 'The polarity of the concrete and the infinite — earth and water, analysis and imagination, practicality and vision. <strong><em>Virgo</em> provides the grounding and discernment that <em>Pisces</em> genuinely needs; Pisces provides the compassion, depth, and spiritual perspective that Virgo secretly craves.</strong> Virgo finds Pisces evasive, Pisces finds Virgo critical. But the opposites attract with unusual force, and the relationship often has a healing dimension — each person finds in the other a missing piece of themselves.',
            ],

            // ── Libra ────────────────────────────────────────────────────────

            [
                'key'  => 'libra_libra',
                'text' => 'Two <em>Libra</em> Suns create a relationship of exquisite social grace, mutual consideration, and shared appreciation for beauty and harmony. <strong>The connection feels elegant and agreeable</strong> — both people understand the language of fairness, aesthetic pleasure, and the desire for peace without explanation. The challenge is decision-making and directness: two Libras can endlessly defer to the other\'s preferences, avoiding necessary conflict so thoroughly that genuine connection is replaced by pleasant avoidance. Choosing honesty over harmony is the central work of this pairing.',
            ],
            [
                'key'  => 'libra_scorpio',
                'text' => 'A meeting between surface and depth, lightness and intensity, social ease and psychological power. <strong><em>Libra</em>\'s beauty and social grace intrigue <em>Scorpio</em>; Scorpio\'s depth and intensity transform Libra in ways it doesn\'t always invite but often needs.</strong> Libra seeks harmony and avoids intensity; Scorpio seeks depth and distrusts ease. When the relationship is allowed to go deeper than its elegant exterior, it reveals an unexpected richness and genuine mutual transformation that neither anticipated.',
            ],
            [
                'key'  => 'libra_sagittarius',
                'text' => 'Two signs that share a love of ideas, freedom, and engagement with the larger world. <strong>The connection feels lively, generous, and mutually appreciative</strong> — <em>Libra</em> brings social intelligence and aesthetic refinement, <em>Sagittarius</em> brings philosophical breadth and adventurous spirit. Neither sign is particularly interested in emotional heaviness, which can make the relationship feel easy and delightful. The challenge is depth: both signs can keep things pleasantly light and avoid the emotional intimacy that allows a bond to truly mature.',
            ],
            [
                'key'  => 'libra_capricorn',
                'text' => 'A cardinal sign square — two ambitious signs who want to achieve, but through entirely different means. <strong><em>Libra</em> works through relationship and diplomacy; <em>Capricorn</em> works through structure and discipline.</strong> Capricorn finds Libra indecisive and too concerned with approval, Libra finds Capricorn emotionally remote and overly focused on status. But both signs share genuine ambition and an appreciation for quality and success, and the relationship can produce a formidable combination when the differences are respected rather than resented.',
            ],
            [
                'key'  => 'libra_aquarius',
                'text' => 'Two air signs in natural trine — socially engaged, intellectually alive, and sharing a genuine commitment to fairness and idealism. <strong>The connection feels easy, stimulating, and mutually respectful</strong>, with both <em>Libra</em> and <em>Aquarius</em> appreciating the other\'s intelligence, social values, and need for freedom. Neither sign is demanding or emotionally heavy, which creates unusual ease. The challenge is emotional depth and the willingness to be vulnerable. Choosing that depth together is the active work that allows this natural affinity to become genuine intimacy.',
            ],
            [
                'key'  => 'libra_pisces',
                'text' => 'A meeting between social grace and oceanic depth — two signs that both seek beauty, harmony, and the avoidance of harshness, but in very different registers. <strong><em>Libra</em>\'s desire for elegant balance meets <em>Pisces</em>\'s desire for spiritual union</strong>, and the combination can be genuinely beautiful. Libra is social and mental, Pisces is emotional and spiritual. Libra finds Pisces evasive, Pisces finds Libra superficial. But the shared longing for beauty and peace creates real common ground that sustains the bond through its differences.',
            ],

            // ── Scorpio ──────────────────────────────────────────────────────

            [
                'key'  => 'scorpio_scorpio',
                'text' => 'Two <em>Scorpio</em> Suns create a relationship of extraordinary psychological depth, mutual recognition, and intense emotional bond. <strong>The understanding between them goes to places others rarely reach</strong> — both people know the language of depth, transformation, and the refusal of surface. The challenge is power: two Scorpios can become locked in unspoken struggles for control that erode the trust that makes their depth possible. Radical honesty, which both signs value in theory, becomes the essential practice that keeps this pairing alive and growing.',
            ],
            [
                'key'  => 'scorpio_sagittarius',
                'text' => 'A meeting between depth and freedom — one who goes in, one who goes out. <strong><em>Scorpio</em>\'s intensity and emotional depth are simultaneously fascinating and challenging to <em>Sagittarius</em>; Sagittarius\'s freedom and philosophical breadth are both liberating and destabilizing to Scorpio.</strong> Scorpio wants to possess, Sagittarius wants to be free. When both people are genuinely committed to the relationship, the combination produces unusual depth alongside unusual expansiveness.',
            ],
            [
                'key'  => 'scorpio_capricorn',
                'text' => 'Two signs that share a fundamental orientation toward power, discipline, and the long game. <strong>The connection feels serious, purposeful, and quietly intense</strong> — both <em>Scorpio</em> and <em>Capricorn</em> understand ambition, self-mastery, and the value of building something that lasts. Trust builds slowly but becomes formidably strong. The challenge is emotional warmth: both signs can be withholding and guarded, and the relationship needs deliberate cultivation of tenderness and vulnerability to feel complete rather than merely powerful.',
            ],
            [
                'key'  => 'scorpio_aquarius',
                'text' => 'A fixed sign square between two signs of unusual intensity — one driven by emotional depth and psychological power, one driven by detachment, intellect, and collective vision. <strong><em>Scorpio</em> finds <em>Aquarius</em> emotionally unavailable; Aquarius finds Scorpio suffocating.</strong> The friction is genuine and persistent. But the mutual recognition of two extraordinarily strong wills creates a respect that is difficult to dismiss. When both people commit to genuine understanding rather than reformation, the relationship can be transformative in unexpected ways.',
            ],
            [
                'key'  => 'scorpio_pisces',
                'text' => 'Two water signs in deep harmony — intuitive, emotionally attuned, and oriented toward the invisible dimensions of experience. <strong>The connection has a quality of soul recognition</strong> — both <em>Scorpio</em> and <em>Pisces</em> feel immediately understood in their depth and their darkness in ways that are rare. Pisces\'s compassion meets Scorpio\'s intensity with unusual grace. The challenge is boundary maintenance: both signs can dissolve into each other in ways that ultimately serve neither. Distinct identities are the essential work.',
            ],

            // ── Sagittarius ──────────────────────────────────────────────────

            [
                'key'  => 'sagittarius_sagittarius',
                'text' => 'Two <em>Sagittarius</em> Suns create a relationship of extraordinary freedom, adventure, and shared love of the big picture. <strong>The connection feels expansive and alive</strong>, with both people understanding the language of independence, philosophical exploration, and the refusal of limitation. The risk is rootlessness: two signs oriented toward the horizon can fail to build the continuity and depth that allow a relationship to become truly intimate. Choosing to stay — genuinely, not just geographically — is the active work this pairing requires.',
            ],
            [
                'key'  => 'sagittarius_capricorn',
                'text' => 'A meeting between the explorer and the builder — one oriented toward expansion, one toward structure. <strong><em>Sagittarius</em> brings the vision and enthusiasm that <em>Capricorn</em>\'s ambitions need; Capricorn brings the discipline and persistence that gives Sagittarius\'s vision a real chance of landing.</strong> Sagittarius resists limits, Capricorn works within them. But the complementarity is genuine, and when both people are secure, the pairing produces an unusually complete combination of ambition and adventure.',
            ],
            [
                'key'  => 'sagittarius_aquarius',
                'text' => 'Two signs that share a love of freedom, ideas, and the unconventional. <strong>The connection feels immediate and easy</strong> — both <em>Sagittarius</em> and <em>Aquarius</em> understand the language of independence, idealism, and the refusal to be limited by convention. Neither crowds the other or demands conformity, which creates unusual mutual ease. The challenge is emotional depth and long-term grounding: two signs oriented toward freedom and abstraction can create a relationship that is consistently stimulating but never quite arrives at the vulnerable intimacy that completes a bond.',
            ],
            [
                'key'  => 'sagittarius_pisces',
                'text' => 'A mutable sign square — two signs that are both imaginative, idealistic, and somewhat resistant to fixed reality, but approaching life\'s larger questions from opposite ends. <strong><em>Sagittarius</em> seeks truth through philosophy and adventure; <em>Pisces</em> seeks truth through feeling and spiritual dissolution.</strong> The creative potential between them is real, and both signs understand the language of meaning and transcendence. The friction is practical: both can struggle with the concrete details of a shared life, which can destabilize the relationship\'s foundations.',
            ],

            // ── Capricorn ────────────────────────────────────────────────────

            [
                'key'  => 'capricorn_capricorn',
                'text' => 'Two <em>Capricorn</em> Suns create a relationship of profound mutual respect, shared ambition, and quiet, lasting loyalty. <strong>The connection feels purposeful and serious</strong> — both people understand the language of discipline, achievement, and the satisfaction of building something that endures. The risk is emotional austerity: two Capricorns can create an efficient partnership that is emotionally undernourished, with neither person initiating the vulnerability that allows genuine intimacy. Choosing warmth and tenderness alongside achievement is the central work.',
            ],
            [
                'key'  => 'capricorn_aquarius',
                'text' => 'A meeting between tradition and innovation — one who builds on what has been proven, one who envisions what has not yet existed. <strong><em>Capricorn</em>\'s practicality and <em>Aquarius</em>\'s vision can produce a remarkably effective combination</strong> when both people respect the other\'s orientation rather than dismissing it. Capricorn finds Aquarius impractical, Aquarius finds Capricorn resistant to necessary change. But when both commit to a shared goal, the combination of structure and innovation is genuinely powerful.',
            ],
            [
                'key'  => 'capricorn_pisces',
                'text' => 'A meeting between earth and water — one who builds in the material world, one who lives in the invisible. <strong><em>Capricorn</em> provides the structure and direction that <em>Pisces</em> genuinely needs; Pisces provides the compassion, intuition, and spiritual depth that softens and enriches Capricorn\'s often austere world.</strong> Both signs are serious in their own way — one about achievement, one about depth — and the combination can produce a relationship of unusual completeness when both people feel genuinely valued for what they bring.',
            ],

            // ── Aquarius ─────────────────────────────────────────────────────

            [
                'key'  => 'aquarius_aquarius',
                'text' => 'Two <em>Aquarius</em> Suns create a relationship of extraordinary intellectual freedom, mutual respect for independence, and shared commitment to the unconventional. <strong>The connection feels genuinely equal and remarkably free from the usual relationship dynamics</strong> — neither person demands conformity or submission. The challenge is intimacy: two signs that both keep emotional distance and prize freedom can create a relationship that is intellectually alive but emotionally thin. The work of this pairing is choosing to be personally, vulnerably present alongside the freedom.',
            ],
            [
                'key'  => 'aquarius_pisces',
                'text' => 'The final pairing of the zodiac — air and water, detachment and compassion, the visionary and the mystic. <strong><em>Aquarius</em> brings intellectual clarity and future orientation to <em>Pisces</em>\'s diffuse world; Pisces brings empathy, depth, and spiritual resonance to Aquarius\'s often abstract idealism.</strong> Both signs are in some ways oriented beyond the personal, which creates unusual mutual understanding alongside a shared difficulty with the ordinary demands of daily life. The relationship has a quality of the otherworldly — distinctive, occasionally impractical, always interesting.',
            ],

            // ── Pisces ───────────────────────────────────────────────────────

            [
                'key'  => 'pisces_pisces',
                'text' => 'Two <em>Pisces</em> Suns create a relationship of extraordinary emotional depth, spiritual resonance, and mutual compassion. <strong>The connection feels like dissolving into something larger</strong> — both people speak the language of feeling, imagination, and the desire to merge with something beyond the self. The risk is profound: two signs with permeable boundaries can lose themselves in each other in ways that ultimately serve neither person. The relationship asks both people to maintain the paradox of genuine union alongside genuine individuality — difficult, but deeply nourishing when achieved.',
            ],

        ];
    }
}
