<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Manually authored retrograde text blocks.
 *
 * Section: retrograde
 * Key format: {planet}_rx_{sign}  (e.g. mercury_rx_pisces)
 * 96 blocks: 8 planets (Mercury–Pluto) × 12 signs
 *
 * Style rules:
 *   - 3 sentences, plain text
 *   - Address as "you/your"
 *   - Concrete behaviour — no spiritual/psychological jargon
 *   - Varied sentence openings
 *   - Forbidden: journey, path, soul, essence, force, pull, tension, dance, dissolves
 */
class RetrogradeTextsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = array_map(fn ($r) => array_merge($r, [
            'section'    => 'retrograde',
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

        $this->command->info('Retrograde texts seeded — ' . count($rows) . ' blocks.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function blocks(): array
    {
        return [

            // ── Mercury Rx ────────────────────────────────────────────────

            ['key' => 'mercury_rx_aries', 'text' =>
                'Communication gets impulsive and then regretted during this period. '
                . 'You may speak before thinking, send messages in haste, or make decisions based on incomplete information. '
                . 'Revisiting plans rather than rushing forward is more productive than it feels right now.'],

            ['key' => 'mercury_rx_taurus', 'text' =>
                'Mental processes slow noticeably and a stubbornness in thinking makes it harder than usual to change your mind. '
                . 'These days you may return to financial decisions, possessions, or value-related questions that seemed settled. '
                . 'Take your time — the careful review you do now will save costly corrections later.'],

            ['key' => 'mercury_rx_gemini', 'text' =>
                'Information overload and communication mix-ups are more likely right now than at other times. '
                . 'You may find yourself re-reading, misunderstanding, or having to repeat conversations that should have been straightforward. '
                . 'Slow down with written communications in particular — errors sneak through when you feel most certain.'],

            ['key' => 'mercury_rx_cancer', 'text' =>
                'Your thinking turns inward and emotionally colored during this period, making objective analysis harder. '
                . 'Past conversations or unresolved family matters resurface and demand attention. '
                . 'Revisiting emotional decisions is more useful now than making new ones while this lasts.'],

            ['key' => 'mercury_rx_leo', 'text' =>
                'Self-expression feels less fluid right now and there is a tendency to overthink how you come across to others. '
                . 'Conversations about recognition, creative projects, or past performances may resurface during this period. '
                . 'Creative work benefits more from revision than from new starts right now.'],

            ['key' => 'mercury_rx_virgo', 'text' =>
                'Your attention sharpens on errors and inefficiencies right now, sometimes to the point of paralysis. '
                . 'Details that were overlooked resurface, and work or health plans made earlier may need correction. '
                . 'This period rewards meticulous review but punishes perfectionism that prevents completion.'],

            ['key' => 'mercury_rx_libra', 'text' =>
                'Relationship negotiations, contracts, and pending decisions become tangled right now. '
                . 'You may revisit agreements or feel pressure to reconsider commitments you thought were settled. '
                . 'Avoid finalizing important partnerships or legal matters until Mercury stations direct — clarifying existing arrangements is more productive.'],

            ['key' => 'mercury_rx_scorpio', 'text' =>
                'Communication about sensitive or private matters is particularly prone to misunderstanding during this period. '
                . 'Past secrets, investigations, or unresolved trust issues may resurface and demand honest attention. '
                . 'What you hesitate to say is often more important than what you actually say right now.'],

            ['key' => 'mercury_rx_sagittarius', 'text' =>
                'Plans for travel, education, or big-picture goals hit unexpected snags right now. '
                . 'Opinions expressed with too much confidence may need to be walked back, and long-distance communications often get garbled. '
                . 'This is a better period for reviewing beliefs than for broadcasting them.'],

            ['key' => 'mercury_rx_capricorn', 'text' =>
                'Professional communications and career-related decisions are prone to misalignment right now. '
                . 'Contracts, applications, or plans that seemed finalized may need revisiting before they are acted on. '
                . 'The careful checking you do now prevents public errors later.'],

            ['key' => 'mercury_rx_aquarius', 'text' =>
                'Technical systems, digital communications, and group coordination hit unexpected friction during this period. '
                . 'Plans made with communities or organizations may need revision, and innovative ideas benefit from reconsideration before being shared. '
                . 'Logic that felt solid on paper often reveals gaps when tested right now.'],

            ['key' => 'mercury_rx_pisces', 'text' =>
                'Thinking becomes impressionistic rather than precise during this period, making concrete decisions harder than usual. '
                . 'Miscommunications arise from vagueness — what you think you said and what others heard often differ. '
                . 'Creative and intuitive work benefits from this diffuse quality; contracts and deadlines do not.'],

            // ── Venus Rx ──────────────────────────────────────────────────

            ['key' => 'venus_rx_aries', 'text' =>
                'Relationships feel competitive or impatient right now, and old romantic patterns tend to resurface. '
                . 'Impulsive decisions about love, money, or self-presentation made during this period often need correction afterward. '
                . 'What feels like a fresh start now is more likely a return to something unresolved.'],

            ['key' => 'venus_rx_taurus', 'text' =>
                'Values and self-worth are under quiet but persistent review right now. '
                . 'Financial habits, relationship comfort levels, or standards for beauty and pleasure may feel suddenly inadequate. '
                . 'Use this period to examine what you genuinely value rather than what habit or expectation has told you to want.'],

            ['key' => 'venus_rx_gemini', 'text' =>
                'Social connections from the past resurface and existing relationships require more honest communication than usual. '
                . 'Flirtation and surface-level charm lose their usual effect during this period. '
                . 'What you actually want from relationships — rather than what seems appealing — becomes clearer if you pay attention.'],

            ['key' => 'venus_rx_cancer', 'text' =>
                'Emotional security in close relationships is being reassessed right now, often without clear language to describe the process. '
                . 'Family-related values, domestic arrangements, and old relationship patterns from earlier in life come up for review. '
                . 'Nurturing yourself is more productive now than seeking reassurance from others.'],

            ['key' => 'venus_rx_leo', 'text' =>
                'The need for admiration and recognition in relationships surfaces more strongly right now, sometimes in ways that surprise you. '
                . 'Past romantic connections or unresolved questions about self-worth may return during this period. '
                . 'Examining what you genuinely offer in relationships is more useful than seeking external validation.'],

            ['key' => 'venus_rx_virgo', 'text' =>
                'Criticism in close relationships — both given and received — is sharper than usual right now. '
                . 'Financial habits and relationship rituals that were working may suddenly feel insufficient. '
                . 'Small adjustments to how you give and receive care will matter more than dramatic changes during this period.'],

            ['key' => 'venus_rx_libra', 'text' =>
                'The foundations of your most significant partnerships are under review during this period. '
                . 'Imbalances in give-and-take that were tolerated before become harder to ignore. '
                . 'Avoid making major relationship decisions — finalizing commitments or ending things — until you have had enough time to see the full picture.'],

            ['key' => 'venus_rx_scorpio', 'text' =>
                'Deep-seated relationship patterns involving trust, control, or intimacy resurface during this period. '
                . 'Old jealousies, power imbalances, or unresolved emotional debts demand honest attention. '
                . 'What you have been avoiding about your closest connections is exactly what needs examination right now.'],

            ['key' => 'venus_rx_sagittarius', 'text' =>
                'Expectations in relationships — what you hoped others would provide and what you freely give — come under scrutiny right now. '
                . 'Past connections formed through shared ideals or travel may resurface during this period. '
                . 'Honest examination of whether your values still align with those closest to you is more productive than optimism.'],

            ['key' => 'venus_rx_capricorn', 'text' =>
                'Commitments made out of practicality rather than genuine connection are being quietly examined right now. '
                . 'Relationships with authority figures, professional partnerships, or long-term romantic arrangements may feel more burdensome than rewarding. '
                . 'This is a useful period for honest assessment — not for ending things prematurely.'],

            ['key' => 'venus_rx_aquarius', 'text' =>
                'The role of freedom and individuality in your closest relationships comes into sharp focus during this period. '
                . 'Connections that require too much compromise of your independence feel unsatisfying now. '
                . 'Use this time to clarify what you genuinely need in partnership rather than settling for what is available.'],

            ['key' => 'venus_rx_pisces', 'text' =>
                'The boundary between romantic idealization and real connection is particularly thin right now. '
                . 'Old relationships may seem more appealing than they actually were, and new connections made during this period often need reassessment after it ends. '
                . 'Compassion for yourself and others serves better than high expectations right now.'],

            // ── Mars Rx ───────────────────────────────────────────────────

            ['key' => 'mars_rx_aries', 'text' =>
                'Drive and initiative are available but misfire easily right now. '
                . 'You may start things with conviction and then lose momentum, or feel frustrated that effort does not produce the results it normally would. '
                . 'Avoid starting major new projects; redirecting existing efforts is more productive than launching fresh ones.'],

            ['key' => 'mars_rx_taurus', 'text' =>
                'Motivation runs slower and more stubborn than usual during this period. '
                . 'Physical energy is inconsistent and the desire to maintain existing routines outweighs any push toward change. '
                . 'Patience with your own pace matters more than forcing output that the body or circumstances are not ready for.'],

            ['key' => 'mars_rx_gemini', 'text' =>
                'Energy scatters across too many directions right now, making sustained effort on a single goal unusually difficult. '
                . 'Arguments or miscommunications can flare up without clear cause, and the frustration of divided attention builds quickly. '
                . 'Choose one or two priorities and protect them from the rest during this period.'],

            ['key' => 'mars_rx_cancer', 'text' =>
                'Aggression and frustration tend to be expressed passively or indirectly right now rather than directly. '
                . 'Domestic tensions that have been suppressed may surface during this period. '
                . 'Identifying what you actually want from a situation — before reacting — is more useful than it normally is.'],

            ['key' => 'mars_rx_leo', 'text' =>
                'The desire for recognition and creative output is present but blocked or redirected during this period. '
                . 'Ambitions that felt exciting may feel suddenly hollow, or effortful performance yields less response than expected. '
                . 'Reassessing your goals is more honest and productive now than seeking visible results.'],

            ['key' => 'mars_rx_virgo', 'text' =>
                'Critical energy turns inward during this period, and self-criticism tends to be more active than productive action. '
                . 'Health, daily routines, and work habits come under scrutiny in ways that may feel more burdensome than useful. '
                . 'Small consistent efforts now build toward genuine improvement more reliably than sweeping overhauls.'],

            ['key' => 'mars_rx_libra', 'text' =>
                'Asserting your needs in relationships feels harder than usual right now, and unresolved conflicts tend to resurface. '
                . 'Avoiding direct confrontation can build passive tension that eventually requires release. '
                . 'Honest conversations about what is and is not working in close partnerships are more useful now than they feel.'],

            ['key' => 'mars_rx_scorpio', 'text' =>
                'Intense, driven energy is available right now but often feels blocked or turned inward. '
                . 'Old resentments, power struggles, or buried ambitions resurface and demand honest acknowledgment. '
                . 'This period supports deep investigative work but makes impulsive confrontations more harmful than productive.'],

            ['key' => 'mars_rx_sagittarius', 'text' =>
                'The drive to expand, travel, or pursue big goals hits practical obstacles during this period. '
                . 'Restlessness and impatience with limitations are higher than usual, but action taken from frustration tends to misfire. '
                . 'Reviewing your longer-term direction is more rewarding now than forcing movement toward it.'],

            ['key' => 'mars_rx_capricorn', 'text' =>
                'Ambition is present but progress feels blocked or slowed in frustrating ways right now. '
                . 'Efforts toward professional goals seem to require more than they return during this period. '
                . 'Reassessing the methods you are using rather than simply increasing effort is the more productive response.'],

            ['key' => 'mars_rx_aquarius', 'text' =>
                'The drive to contribute to group goals or act on principle feels tangled with personal frustration right now. '
                . 'Rebellious impulses and the desire to challenge existing structures are stronger than usual but harder to channel effectively. '
                . 'Reviewing what you actually believe is worth acting on matters more than responding to every impulse.'],

            ['key' => 'mars_rx_pisces', 'text' =>
                'Physical energy and direction are both diffuse right now, making focused sustained action harder than usual. '
                . 'Motivation may feel unclear or tied to emotional undercurrents rather than concrete goals. '
                . 'Effort focused on what genuinely matters yields more during this period than pushing against the prevailing fatigue.'],

            // ── Jupiter Rx ────────────────────────────────────────────────

            ['key' => 'jupiter_rx_aries', 'text' =>
                'Expansion and confidence retreat inward during this period, and bold initiatives launched recently may feel overextended. '
                . 'The impulse to charge forward is replaced by a quieter, more honest review of where your genuine optimism is warranted. '
                . 'Reassessment serves you better than new beginnings right now.'],

            ['key' => 'jupiter_rx_taurus', 'text' =>
                'Material growth and financial expansion slow during this period, encouraging a more careful review of resources and values. '
                . 'Over-commitments made in a spirit of optimism may now feel burdensome. '
                . 'Consolidating what you have is more productive now than reaching for more.'],

            ['key' => 'jupiter_rx_gemini', 'text' =>
                'Learning and intellectual expansion turn inward during this period — ideas that felt promising when conceived now require honest evaluation. '
                . 'Information gathered quickly may benefit from slower, deeper processing. '
                . 'Connecting existing knowledge serves you better right now than gathering new material.'],

            ['key' => 'jupiter_rx_cancer', 'text' =>
                'Emotional generosity and the expansion of close bonds may feel less available right now. '
                . 'Past connections, family relationships, or unresolved emotional commitments return to the foreground. '
                . 'Honest reflection on where your genuine care and loyalty are directed yields more than performing warmth you do not feel.'],

            ['key' => 'jupiter_rx_leo', 'text' =>
                'Creative confidence and the desire to be seen or celebrated retreat inward during this period. '
                . 'Projects that were expanding boldly may need a quieter phase of consolidation and honest self-review. '
                . 'Evaluating what you are genuinely proud of versus what requires more work is the most honest use of this time.'],

            ['key' => 'jupiter_rx_virgo', 'text' =>
                'The expansion of practical systems, health routines, and work habits slows during this period in ways that demand honest assessment. '
                . 'Growth that has been accumulating may need to be reviewed for sustainability. '
                . 'Quality over quantity in your work and health practices rewards you more right now.'],

            ['key' => 'jupiter_rx_libra', 'text' =>
                'The growth of partnerships and social connections pauses for internal review during this period. '
                . 'Beliefs about fairness, collaboration, and what constitutes a good agreement are being quietly re-examined. '
                . 'Clarifying what you genuinely want from close relationships matters more right now than what seems reasonable on the surface.'],

            ['key' => 'jupiter_rx_scorpio', 'text' =>
                'Expansion in areas involving depth, transformation, and shared resources pauses for honest reassessment right now. '
                . 'Past investments — financial, emotional, or psychological — return to attention and require evaluation. '
                . 'Research and deeper understanding serve you better during this period than new initiatives.'],

            ['key' => 'jupiter_rx_sagittarius', 'text' =>
                'Beliefs, philosophies, and long-held worldviews are under more rigorous self-examination than usual right now. '
                . 'Optimism that has been unquestioned may suddenly reveal its assumptions. '
                . 'This period is genuinely useful for distinguishing between what you believe and what you actually know.'],

            ['key' => 'jupiter_rx_capricorn', 'text' =>
                'Ambitions and long-term structures built on optimistic assumptions are being tested right now. '
                . 'Career growth or institutional commitments that seemed on track may require more realistic adjustment than you had planned. '
                . 'Reviewing what is genuinely working in your long-term strategy before continuing to build is the most productive use of this period.'],

            ['key' => 'jupiter_rx_aquarius', 'text' =>
                'Ideas about social progress, collective improvement, and future-oriented goals turn inward during this period. '
                . 'Idealism that has been driving group efforts may need grounding in more concrete assessment. '
                . 'Reviewing rather than promoting your vision of how things could improve serves you better right now.'],

            ['key' => 'jupiter_rx_pisces', 'text' =>
                'Spiritual, creative, and compassionate expansions slow and turn inward right now. '
                . 'Beliefs about meaning, forgiveness, and transcendence that felt clear may now seem uncertain. '
                . 'Deeper honesty about what you genuinely believe rewards you more during this period than what feels comforting to think.'],

            // ── Saturn Rx ─────────────────────────────────────────────────

            ['key' => 'saturn_rx_aries', 'text' =>
                'Structures built on impulse rather than genuine foundation are being tested right now. '
                . 'Rules and commitments that felt constraining may now feel simply necessary. '
                . 'This period asks you to distinguish between the discipline you are avoiding and the limits that are genuinely serving your growth.'],

            ['key' => 'saturn_rx_taurus', 'text' =>
                'Long-term financial structures, material commitments, and patterns of security are under review during this period. '
                . 'What you have been relying on for stability may require more active maintenance than you had assumed. '
                . 'Honest reckoning with resources and long-term obligations is the most useful work you can do right now.'],

            ['key' => 'saturn_rx_gemini', 'text' =>
                'Mental discipline and consistency in communication are being tested right now. '
                . 'Projects that require sustained intellectual effort may expose areas where your commitment has been shallow. '
                . 'Returning to half-finished work rewards you more during this period than starting fresh.'],

            ['key' => 'saturn_rx_cancer', 'text' =>
                'The structures that support emotional security — family obligations, domestic arrangements, and habitual patterns of care — are being reassessed right now. '
                . 'Boundaries in close relationships may feel either too rigid or insufficiently maintained. '
                . 'Honest reflection on what genuinely sustains you yields more now than seeking external reassurance.'],

            ['key' => 'saturn_rx_leo', 'text' =>
                'Ambition and the structures supporting creative or public expression face an honest review during this period. '
                . 'Recognition that has been slow to arrive may be prompting important reassessment of your goals or methods. '
                . 'Internal validation serves you more right now than seeking external acknowledgment.'],

            ['key' => 'saturn_rx_virgo', 'text' =>
                'The systems and routines you rely on for health, work, and daily functioning are being tested for their genuine usefulness right now. '
                . 'Habits maintained out of routine rather than benefit deserve honest evaluation. '
                . 'Small corrections made now to health or work practices tend to produce more durable improvement than sweeping changes.'],

            ['key' => 'saturn_rx_libra', 'text' =>
                'Commitments, contracts, and relationship structures are being reviewed for their genuine fairness and sustainability. '
                . 'Long-standing agreements that have been tolerated rather than honored may surface for honest reassessment. '
                . 'This period asks you to hold yourself to the same standards you expect from others.'],

            ['key' => 'saturn_rx_scorpio', 'text' =>
                'Deep psychological structures — the ways you manage power, control, and shared resources — are under quiet but significant review right now. '
                . 'Commitments made at depth, financial obligations, or emotional contracts with others demand careful attention. '
                . 'Unacknowledged patterns of control or avoidance are being surfaced for honest examination.'],

            ['key' => 'saturn_rx_sagittarius', 'text' =>
                'Beliefs that have been functioning as unexamined rules are being tested for their actual utility right now. '
                . 'Long-term plans based on optimistic assumptions may need grounding in more realistic evaluation. '
                . 'Honest review of what you have committed to — in education, philosophy, or travel — is more productive than continuing forward without looking back.'],

            ['key' => 'saturn_rx_capricorn', 'text' =>
                'Ambitions, career structures, and long-term goals are under the most rigorous review possible during this period. '
                . 'Work that has been sustained through discipline alone may now reveal where genuine motivation is missing. '
                . 'This is a demanding but genuinely clarifying period for honest assessment of your long-term direction.'],

            ['key' => 'saturn_rx_aquarius', 'text' =>
                'The structures supporting group goals, social commitments, and future-oriented plans are being honestly tested right now. '
                . 'Rules and systems that seemed useful may prove unnecessarily rigid under pressure. '
                . 'Distinguishing between structures worth preserving and those worth updating is the most useful work of this period.'],

            ['key' => 'saturn_rx_pisces', 'text' =>
                'The limits that protect your energy, compassion, and creative focus are being reconsidered right now. '
                . 'Boundaries that have been dissolved in the name of flexibility or kindness may be creating hidden costs. '
                . 'Building more deliberate structure into the areas of your life that feel most diffuse rewards you more than continued openness.'],

            // ── Uranus Rx ─────────────────────────────────────────────────

            ['key' => 'uranus_rx_aries', 'text' =>
                'The drive for radical personal change and independence turns inward during this period. '
                . 'Disruptions that felt externally driven may now appear as expressions of your own resistance to constraint. '
                . 'Distinguishing between genuine need for freedom and simple avoidance of responsibility is the honest work of this time.'],

            ['key' => 'uranus_rx_taurus', 'text' =>
                'The disruption of material security, financial systems, and habitual comforts pauses for internal processing right now. '
                . 'Changes to resources or values that seemed certain may now appear more uncertain — or more necessary — than before. '
                . 'This period invites reconsideration of what stability genuinely means for you.'],

            ['key' => 'uranus_rx_gemini', 'text' =>
                'Mental restlessness and the urge to break out of repetitive thinking patterns turn inward during this period. '
                . 'Ideas that seemed innovative may benefit from more careful review before being acted upon. '
                . 'The impulse to communicate everything immediately is worth restraining right now.'],

            ['key' => 'uranus_rx_cancer', 'text' =>
                'Disruptions to home, family, and emotional security are being processed internally during this period. '
                . 'Changes to domestic arrangements or deep emotional patterns that have been unsettling may now feel less urgent. '
                . 'Processing what has already shifted serves you better right now than forcing further change.'],

            ['key' => 'uranus_rx_leo', 'text' =>
                'The urge for radical self-expression, creative reinvention, or dramatic change to identity slows and turns inward. '
                . 'What felt like necessary rebellion may now appear as productive redirection. '
                . 'Revising your creative direction rather than abandoning it entirely is the more honest response right now.'],

            ['key' => 'uranus_rx_virgo', 'text' =>
                'The impulse to overhaul work systems, health routines, or daily patterns turns inward during this period. '
                . 'Changes that seemed urgently necessary may benefit from slower, more incremental adjustment. '
                . 'Reviewing which innovations are genuinely improving your efficiency — and which are creating new complications — is the useful work of this time.'],

            ['key' => 'uranus_rx_libra', 'text' =>
                'The disruption of relationship patterns and social norms pauses for internal reassessment right now. '
                . 'Changes to partnerships or social structures that have been destabilizing may need quieter processing before further action. '
                . 'Examining what kind of freedom you genuinely need within close relationships is more productive than acting on every impulse.'],

            ['key' => 'uranus_rx_scorpio', 'text' =>
                'Deep psychological and structural disruptions turn inward during this period, often surfacing in unexpected ways. '
                . 'The urge to break down hidden power structures — in yourself or relationships — is better served by reflection than by action right now. '
                . 'Examining what you have been refusing to acknowledge matters more than pushing further change.'],

            ['key' => 'uranus_rx_sagittarius', 'text' =>
                'The impulse to shatter old beliefs and embrace radical new philosophies pauses for honest review. '
                . 'Conviction that felt liberating may reveal itself as impulsive when examined more carefully. '
                . 'Distinguishing between genuine insight and the appeal of novelty is the most productive work of this period.'],

            ['key' => 'uranus_rx_capricorn', 'text' =>
                'Disruptions to career structures, long-term goals, and established authority turn inward during this period. '
                . 'Changes to professional direction that seemed inevitable may now benefit from more careful evaluation. '
                . 'Questioning which structures are genuinely outdated — versus which are simply inconvenient — is the honest work of this time.'],

            ['key' => 'uranus_rx_aquarius', 'text' =>
                'The drive for radical social change, innovation, and collective transformation turns sharply inward during this period. '
                . 'Revolutionary impulses that have been externally directed now demand internal review. '
                . 'Asking whether your vision of collective change reflects genuine insight or an escape from personal constraint is the honest work of this time.'],

            ['key' => 'uranus_rx_pisces', 'text' =>
                'The disruption of inner boundaries, creative breakthroughs, and spiritual awakenings turns inward during this period. '
                . 'Disruptive changes to your inner world or creative life that have been unsettling may now begin to integrate. '
                . 'Processing what has already been loosened serves you better right now than seeking further disruption.'],

            // ── Neptune Rx ────────────────────────────────────────────────

            ['key' => 'neptune_rx_aries', 'text' =>
                'Illusions connected to personal identity, initiative, and self-sufficiency are being quietly dissolved during this period. '
                . 'The drive to act with complete independence may be covering a more honest need for support or direction. '
                . 'Clearer perception of your actual motivations — rather than idealized ones — is the useful work of this time.'],

            ['key' => 'neptune_rx_taurus', 'text' =>
                'Idealized attachments to security, material comfort, and financial stability are being gently tested right now. '
                . 'The belief that certainty and safety are achievable through enough accumulation is being questioned. '
                . 'Examining the difference between genuine security and the avoidance of vulnerability is more honest work than it feels.'],

            ['key' => 'neptune_rx_gemini', 'text' =>
                'Illusions about knowledge, communication, and intellectual certainty are being dissolved during this period. '
                . 'Confidence that comes from gathering and sharing information may obscure the limits of what you actually understand. '
                . 'Admitting uncertainty rewards you more right now than maintaining the appearance of knowing.'],

            ['key' => 'neptune_rx_cancer', 'text' =>
                'Idealized images of home, family, and emotional belonging are being quietly questioned right now. '
                . 'The gap between how close relationships feel and how they actually function may be more visible than usual. '
                . 'Honest acknowledgment of emotional needs — rather than their idealization — is the most useful response right now.'],

            ['key' => 'neptune_rx_leo', 'text' =>
                'Romantic or grandiose ideas about your own identity, creative power, or specialness are being tested right now. '
                . 'What felt like genuine self-expression may reveal itself as performance or wishful thinking during this period. '
                . 'Honest self-perception serves you more than an inspiring self-narrative right now.'],

            ['key' => 'neptune_rx_virgo', 'text' =>
                'The idealization of service, health, and perfection is being dissolved right now. '
                . 'Expectations that relentless self-improvement or giving will eventually be enough are being quietly questioned. '
                . 'Compassion for your own imperfections serves you more during this period than stricter standards.'],

            ['key' => 'neptune_rx_libra', 'text' =>
                'Illusions about harmony, perfect partnership, and effortless fairness in relationships are being dissolved during this period. '
                . 'The gap between the relationship you imagine and the one that actually exists becomes harder to overlook. '
                . 'Honest assessment of what is genuinely good about your relationships — and what requires real attention — is the work of this time.'],

            ['key' => 'neptune_rx_scorpio', 'text' =>
                'Deep illusions about power, transformation, and psychological depth are surfacing for examination right now. '
                . 'What has felt like profound insight may reveal itself as projection when viewed more honestly. '
                . 'Genuine confrontation with what you have been unwilling to face about yourself or others is the most honest use of this period.'],

            ['key' => 'neptune_rx_sagittarius', 'text' =>
                'Idealized beliefs, spiritual philosophies, and grand visions of meaning are being tested right now. '
                . 'The certainty that comes from a worldview that explains everything may be covering a more honest uncertainty. '
                . 'Intellectual humility rewards you more during this period than inspirational conviction.'],

            ['key' => 'neptune_rx_capricorn', 'text' =>
                'The idealization of achievement, structure, and long-term control is being quietly dissolved during this period. '
                . 'The belief that enough discipline and ambition will eventually yield certainty is being questioned. '
                . 'Honest reckoning with what you cannot control — in spite of your best efforts — is more useful than redoubling those efforts.'],

            ['key' => 'neptune_rx_aquarius', 'text' =>
                'Idealized visions of collective progress, social improvement, and human innovation are being tested right now. '
                . 'The gap between how things could theoretically be and how they actually are becomes harder to paper over. '
                . 'Honest assessment of what is genuinely changing versus what is wishful projection is the useful work of this period.'],

            ['key' => 'neptune_rx_pisces', 'text' =>
                'Spiritual ideals, compassionate impulses, and the boundary between self and other are at their most permeable during this period. '
                . 'Illusions that have been comfortable companions are now ready for honest dissolution. '
                . 'Genuine spiritual honesty rewards you more right now than the comfort of belief.'],

            // ── Pluto Rx ──────────────────────────────────────────────────

            ['key' => 'pluto_rx_aries', 'text' =>
                'The transformation of personal identity and raw ambition turns inward during this period. '
                . 'Power that has been exercised outwardly — through assertion or force of will — is now being examined at its root. '
                . 'Identifying the fear or need that your drive toward control is actually serving is the honest work of this time.'],

            ['key' => 'pluto_rx_taurus', 'text' =>
                'The deep transformation of material values, resources, and attachment to security turns inward right now. '
                . 'What you are holding onto out of fear versus genuine need becomes clearer during this period. '
                . 'Honest reckoning with your relationship to money, possessions, and physical comfort is more productive than accumulation.'],

            ['key' => 'pluto_rx_gemini', 'text' =>
                'The transformation of how you think, communicate, and process information turns inward during this period. '
                . 'Deeply held mental habits or reflexive ways of interpreting experience are being examined at their foundation. '
                . 'Recognizing patterns in your own thinking that have outlived their usefulness rewards you more now than defending them.'],

            ['key' => 'pluto_rx_cancer', 'text' =>
                'Deep transformation of family patterns, emotional security, and the structures of belonging turns inward right now. '
                . 'Inherited emotional habits — ways of attaching, protecting, and withdrawing — surface for honest examination. '
                . 'Genuine healing of old patterns is more available during this period than creating new attachments.'],

            ['key' => 'pluto_rx_leo', 'text' =>
                'The transformation of ego, creative power, and the need for recognition turns inward during this period. '
                . 'What has felt like authentic self-expression may reveal deeper patterns of control or approval-seeking when examined honestly. '
                . 'Genuine creative renewal serves you more right now than performance.'],

            ['key' => 'pluto_rx_virgo', 'text' =>
                'The deep transformation of work, health, and service turns inward right now. '
                . 'Compulsive perfectionism or self-critical habits that have been driving daily functioning are being examined at their root. '
                . 'Identifying what fear is actually motivating the standards you hold yourself to is the most honest work of this period.'],

            ['key' => 'pluto_rx_libra', 'text' =>
                'The transformation of relationship patterns, power dynamics in partnerships, and concepts of fairness turns inward during this period. '
                . 'The ways you have been giving away power or demanding control in close relationships are being examined honestly. '
                . 'Genuine renegotiation of how you relate to others is more available now than it will be when this period ends.'],

            ['key' => 'pluto_rx_scorpio', 'text' =>
                'Transformation operates at its most intense and uncompromising level during this period. '
                . 'What has been hidden — psychologically, financially, or relationally — surfaces with unusual clarity and force. '
                . 'Honesty about the most uncomfortable aspects of your life is demanded now; avoidance is not a realistic option.'],

            ['key' => 'pluto_rx_sagittarius', 'text' =>
                'The transformation of belief systems, philosophical frameworks, and the structures of meaning turns inward during this period. '
                . 'Convictions that have functioned as identity rather than genuine understanding surface for honest examination. '
                . 'Distinguishing between what you know and what you merely believe because it has been convenient is the work of this time.'],

            ['key' => 'pluto_rx_capricorn', 'text' =>
                'The transformation of ambition, institutional authority, and long-term structures turns inward right now. '
                . 'Power that has been directed toward career or social position is being examined at its motivational root. '
                . 'Honestly asking whether what you have been building genuinely serves your values — or only your need for control — is the work of this period.'],

            ['key' => 'pluto_rx_aquarius', 'text' =>
                'The transformation of collective structures, social ideals, and the relationship between individual and group turns inward during this period. '
                . 'Revolutionary impulses that have been directed outward are now being examined for their actual cost and genuine motivation. '
                . 'Honest examination of the power dynamics within the communities you participate in is more useful right now than acting on principle.'],

            ['key' => 'pluto_rx_pisces', 'text' =>
                'The transformation of spiritual understanding, compassion, and the dissolution of self turns inward during this period. '
                . 'What has felt like transcendence may reveal itself as avoidance when examined honestly. '
                . 'The most genuine confrontation with the limits of self — not as loss, but as honest understanding — is available to you now.'],

        ];
    }
}
