<?php

namespace App\Services;

/**
 * Scores synastry categories based on cross-chart aspects.
 *
 * Category planet mappings match the project plan (Section 4.11).
 * Positive aspects (conj/trine/sextile) raise the score;
 * tense aspects (square/opposition/quincunx) lower it.
 * Result normalized to 1–5 stars.
 */
class SynastryScorer
{
    // Category definitions per relationship type:
    // key => ['label', 'emoji', 'bodies' => [body_ids...]]
    // An aspect scores for a category when either body_a or body_b is in the planet set.
    private const CATEGORIES = [
        'general' => [
            'romantic'      => ['label' => 'Romantic',     'emoji' => '❤️',  'bodies' => [3, 4]],
            'business'      => ['label' => 'Business',     'emoji' => '🤝',  'bodies' => [6, 5, 0]],
            'spiritual'     => ['label' => 'Spiritual',    'emoji' => '🔮',  'bodies' => [8, 9, 11]],
            'communication' => ['label' => 'Communication','emoji' => '💬',  'bodies' => [2, 0]],
            'emotional'     => ['label' => 'Emotional',    'emoji' => '🌙',  'bodies' => [1, 3]],
        ],
        'romantic' => [
            'attraction'    => ['label' => 'Attraction & Romance',        'emoji' => '❤️',  'bodies' => [3, 4]],
            'communication' => ['label' => 'Communication',               'emoji' => '💬',  'bodies' => [2, 0]],
            'stability'     => ['label' => 'Stability & Growth',          'emoji' => '🌱',  'bodies' => [6, 5, 0, 1]],
            'spiritual'     => ['label' => 'Spiritual Connection',        'emoji' => '🔮',  'bodies' => [8, 9, 1]],
            'passion'       => ['label' => 'Passion & Drive',             'emoji' => '🔥',  'bodies' => [4, 9]],
        ],
        'business' => [
            'communication' => ['label' => 'Communication & Negotiation', 'emoji' => '💬',  'bodies' => [2]],
            'trust'         => ['label' => 'Trust & Reliability',         'emoji' => '🤝',  'bodies' => [6]],
            'drive'         => ['label' => 'Drive & Ambition',            'emoji' => '⚡',  'bodies' => [4, 0]],
            'financial'     => ['label' => 'Financial Alignment',         'emoji' => '💰',  'bodies' => [3, 5]],
            'leadership'    => ['label' => 'Leadership & Power',          'emoji' => '👑',  'bodies' => [0, 9]],
            'vision'        => ['label' => 'Long-term Vision',            'emoji' => '🚀',  'bodies' => [5, 6, 11]],
        ],
        'friends' => [
            'chemistry'     => ['label' => 'Chemistry & Ease',            'emoji' => '🫂',  'bodies' => [1, 3]],
            'communication' => ['label' => 'Communication & Wit',         'emoji' => '💬',  'bodies' => [2]],
            'fun'           => ['label' => 'Fun & Adventure',             'emoji' => '🎉',  'bodies' => [4, 5, 7]],
            'loyalty'       => ['label' => 'Loyalty & Trust',             'emoji' => '💙',  'bodies' => [6]],
            'support'       => ['label' => 'Emotional Support',           'emoji' => '🌿',  'bodies' => [1, 8]],
            'intellectual'  => ['label' => 'Intellectual Connection',     'emoji' => '🧠',  'bodies' => [2, 7]],
        ],
        'family' => [
            'bond'          => ['label' => 'Bond & Attachment',           'emoji' => '🔗',  'bodies' => [1, 3]],
            'understanding' => ['label' => 'Understanding',               'emoji' => '💬',  'bodies' => [2, 1]],
            'support'       => ['label' => 'Support & Care',              'emoji' => '🛡️', 'bodies' => [1, 5, 6]],
            'karmic'        => ['label' => 'Karmic Ties',                 'emoji' => '🔮',  'bodies' => [9, 11]],
            'power'         => ['label' => 'Power & Boundaries',          'emoji' => '⚖️', 'bodies' => [6, 9]],
            'growth'        => ['label' => 'Shared Growth',               'emoji' => '🌱',  'bodies' => [5, 11]],
        ],
        'spiritual' => [
            'connection'    => ['label' => 'Karmic Connection',           'emoji' => '🔮',  'bodies' => [8, 9, 11]],
            'intuition'     => ['label' => 'Intuition & Empathy',         'emoji' => '🌊',  'bodies' => [1, 8]],
            'growth'        => ['label' => 'Shared Growth',               'emoji' => '✨',  'bodies' => [5, 11]],
        ],
        'communication' => [
            'dialogue'      => ['label' => 'Daily Dialogue',              'emoji' => '💬',  'bodies' => [2]],
            'understanding' => ['label' => 'Mental Rapport',              'emoji' => '🧠',  'bodies' => [2, 0]],
            'expression'    => ['label' => 'Expression & Style',          'emoji' => '🎨',  'bodies' => [2, 3]],
        ],
        'emotion' => [
            'empathy'       => ['label' => 'Empathy & Compassion',        'emoji' => '🌙',  'bodies' => [1, 8]],
            'security'      => ['label' => 'Emotional Security',          'emoji' => '🏠',  'bodies' => [1, 6]],
            'depth'         => ['label' => 'Depth & Intensity',           'emoji' => '🌊',  'bodies' => [1, 9]],
        ],
        'sexual' => [
            'attraction'    => ['label' => 'Attraction',                  'emoji' => '🔥',  'bodies' => [3, 4]],
            'intensity'     => ['label' => 'Passion & Intensity',         'emoji' => '⚡',  'bodies' => [4, 9]],
            'desire'        => ['label' => 'Desire & Magnetism',          'emoji' => '🌹',  'bodies' => [3, 9]],
        ],
        'creative' => [
            'expression'    => ['label' => 'Self-Expression',             'emoji' => '🎨',  'bodies' => [3, 0]],
            'inspiration'   => ['label' => 'Inspiration & Vision',        'emoji' => '✨',  'bodies' => [3, 5]],
            'collaboration' => ['label' => 'Creative Dialogue',           'emoji' => '💡',  'bodies' => [2, 3]],
        ],
    ];

    private const ASPECT_SCORES = [
        'conjunction'  =>  3,
        'trine'        =>  2,
        'sextile'      =>  1,
        'opposition'   => -1,
        'square'       => -1,
        'quincunx'     => -1,
        'semi_sextile' =>  0,
    ];

    public static function types(): array
    {
        return array_keys(self::CATEGORIES);
    }

    /**
     * Score all categories for the given relationship type.
     *
     * @param  array  $aspects  From SynastryCalculator::calculate()
     * @param  string $type     romantic|business|friendship|family
     * @return array  category_key => ['label', 'emoji', 'stars', 'raw']
     */
    public function score(array $aspects, string $type): array
    {
        $defs   = self::CATEGORIES[$type] ?? self::CATEGORIES['romantic'];
        $result = [];

        foreach ($defs as $key => $def) {
            $raw            = $this->scoreCategory($aspects, $def['bodies']);
            $result[$key]   = [
                'label' => $def['label'],
                'emoji' => $def['emoji'],
                'stars' => $this->toStars($raw),
                'raw'   => $raw,
            ];
        }

        return $result;
    }

    private function scoreCategory(array $aspects, array $bodies): int
    {
        $raw = 0;
        foreach ($aspects as $asp) {
            if (in_array($asp['body_a'], $bodies) || in_array($asp['body_b'], $bodies)) {
                $raw += self::ASPECT_SCORES[$asp['aspect']] ?? 0;
            }
        }
        return $raw;
    }

    /**
     * Normalize raw score to 1–5 stars.
     * Baseline: 0 raw = 3 stars. Each ±1 raw = ±0.5 stars.
     */
    private function toStars(int $raw): int
    {
        return (int) max(1, min(5, round(3 + $raw * 0.5)));
    }
}
