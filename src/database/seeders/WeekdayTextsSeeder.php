<?php

namespace Database\Seeders;

use App\Models\WeekdayText;
use Illuminate\Database\Seeder;

class WeekdayTextsSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            1 => [
                'name'        => 'Monday',
                'colors'      => 'Silver · White · Green',
                'gem'         => 'Moonstone',
                'theme'       => 'Intuition · Home · Memory',
                'description' => 'Emotional receptivity is heightened and intuition runs closer to the surface than usual. Domestic matters and conversations with close family or friends feel more meaningful — this is a better day for care and attentiveness than for decisive action.',
            ],
            2 => [
                'name'        => 'Tuesday',
                'colors'      => 'Red',
                'gem'         => 'Ruby',
                'theme'       => 'Action · Courage · Drive',
                'description' => 'Physical energy and drive are more available today, making it well suited for tasks that require effort, initiative, or direct confrontation. Impatience and friction in interactions are more likely than usual — directness works better than diplomacy.',
            ],
            3 => [
                'name'        => 'Wednesday',
                'colors'      => 'Yellow',
                'gem'         => "Tiger's Eye",
                'theme'       => 'Communication · Learning · Wit',
                'description' => 'Mental agility is at its peak and communication of all kinds flows more easily. Good for writing, negotiations, short trips, and tasks requiring sharp thinking — scattered attention is the main risk.',
            ],
            4 => [
                'name'        => 'Thursday',
                'colors'      => 'Dark Blue',
                'gem'         => 'Amethyst',
                'theme'       => 'Expansion · Optimism · Wisdom',
                'description' => 'A natural sense of optimism and generosity characterizes the day, making it well suited for planning, teaching, travel, or anything involving expansion. Overconfidence is the shadow side — commitments made today can exceed what is realistic.',
            ],
            5 => [
                'name'        => 'Friday',
                'colors'      => 'Rose · Pink · Warm Cream',
                'gem'         => 'Rose Quartz',
                'theme'       => 'Beauty · Relationships · Pleasure',
                'description' => 'Social interactions and aesthetic sensibilities are sharpened today, making it the natural choice for meetings, events, purchases, or creative work. Relationships benefit from attention — small gestures of appreciation carry more weight than usual.',
            ],
            6 => [
                'name'        => 'Saturday',
                'colors'      => 'Violet',
                'gem'         => 'Obsidian',
                'theme'       => 'Discipline · Structure · Endurance',
                'description' => 'Discipline and structure are the energies available today — tasks requiring sustained effort, planning, or dealing with practical responsibilities respond well. Solitude and focused work are often more productive than group activity.',
            ],
            7 => [
                'name'        => 'Sunday',
                'colors'      => 'Gold · Amber · Warm Orange',
                'gem'         => 'Sunstone',
                'theme'       => 'Vitality · Expression · Leadership',
                'description' => 'Vitality and self-expression are naturally heightened, making this a good day for anything that requires confidence, visibility, or creative output. Pride and the need for recognition can surface more easily — both as strength and as sensitivity.',
            ],
        ];

        foreach ($days as $iso => $data) {
            WeekdayText::updateOrCreate(
                ['iso_day' => $iso, 'language' => 'en'],
                $data
            );
        }
    }
}
