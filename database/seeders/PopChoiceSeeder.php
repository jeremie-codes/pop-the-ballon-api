<?php

namespace Database\Seeders;

use App\Models\PopChoice;
use Illuminate\Database\Seeder;

class PopChoiceSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [

            // PERSONALITY
            [
                'question' => 'Ton week-end idéal ?',
                'option_a' => '🏠 Rester tranquillement à la maison',
                'option_b' => '🎉 Sortir et profiter',
                'type' => 'discovery',
                'category' => 'personality',
                'weight' => 2,
            ],

            [
                'question' => 'Tu préfères...',
                'option_a' => '🗓️ Tout planifier',
                'option_b' => '🎲 Improviser',
                'type' => 'discovery',
                'category' => 'personality',
                'weight' => 3,
            ],

            [
                'question' => 'Quand tu dois prendre une décision...',
                'option_a' => '🧠 Je réfléchis longtemps',
                'option_b' => '🔥 Je suis mon instinct',
                'type' => 'discovery',
                'category' => 'personality',
                'weight' => 3,
            ],

            // COMMUNICATION
            [
                'question' => 'Tu préfères communiquer par...',
                'option_a' => '💬 Messages',
                'option_b' => '📞 Appels',
                'type' => 'discovery',
                'category' => 'communication',
                'weight' => 2,
            ],

            [
                'question' => 'Quand quelque chose ne va pas...',
                'option_a' => '🗣️ J’en parle immédiatement',
                'option_b' => '🤫 Je prends d’abord du recul',
                'type' => 'discovery',
                'category' => 'communication',
                'weight' => 5,
            ],

            [
                'question' => 'Tu préfères recevoir...',
                'option_a' => '🎙️ Une note vocale',
                'option_b' => '⌨️ Un message écrit',
                'type' => 'discovery',
                'category' => 'communication',
                'weight' => 2,
            ],

            // ROMANCE
            [
                'question' => 'Pour un premier date...',
                'option_a' => '🍽️ Un bon restaurant',
                'option_b' => '🌅 Une activité originale',
                'type' => 'discovery',
                'category' => 'romance',
                'weight' => 3,
            ],

            [
                'question' => 'Tu préfères recevoir...',
                'option_a' => '🌹 Des fleurs',
                'option_b' => '🎁 Un cadeau surprise',
                'type' => 'discovery',
                'category' => 'romance',
                'weight' => 2,
            ],

            [
                'question' => 'Tu préfères...',
                'option_a' => '❤️ Beaucoup d’affection',
                'option_b' => '😊 Beaucoup de complicité',
                'type' => 'discovery',
                'category' => 'romance',
                'weight' => 4,
            ],

            // RELATIONSHIP
            [
                'question' => 'Dans une relation, tu préfères...',
                'option_a' => '👫 Beaucoup de temps ensemble',
                'option_b' => '🧘 Garder beaucoup d’espace personnel',
                'type' => 'discovery',
                'category' => 'relationship',
                'weight' => 5,
            ],

            [
                'question' => 'En cas de désaccord...',
                'option_a' => '🗣️ En parler immédiatement',
                'option_b' => '⏳ Attendre de se calmer',
                'type' => 'discovery',
                'category' => 'relationship',
                'weight' => 5,
            ],

            [
                'question' => 'Dans un couple...',
                'option_a' => '❤️ Faire beaucoup de choses ensemble',
                'option_b' => '🧍 Chacun garde ses activités',
                'type' => 'discovery',
                'category' => 'relationship',
                'weight' => 5,
            ],

            // LIFESTYLE
            [
                'question' => 'Ton voyage idéal ?',
                'option_a' => '🏖️ Plage',
                'option_b' => '🏔️ Montagne',
                'type' => 'discovery',
                'category' => 'lifestyle',
                'weight' => 1,
            ],

            [
                'question' => 'Une soirée parfaite ?',
                'option_a' => '🍿 Film à la maison',
                'option_b' => '🌃 Sortie en ville',
                'type' => 'discovery',
                'category' => 'lifestyle',
                'weight' => 2,
            ],

            [
                'question' => 'Tu préfères voyager...',
                'option_a' => '🗺️ Avec un programme précis',
                'option_b' => '🎒 Sans trop planifier',
                'type' => 'discovery',
                'category' => 'lifestyle',
                'weight' => 3,
            ],

            // VALUES
            [
                'question' => 'Pour toi, le plus important dans une relation ?',
                'option_a' => '🤝 La confiance',
                'option_b' => '💬 La communication',
                'type' => 'discovery',
                'category' => 'values',
                'weight' => 5,
            ],

            [
                'question' => 'Tu préfères quelqu’un qui...',
                'option_a' => '🪞 Te ressemble beaucoup',
                'option_b' => '🧩 Te complète',
                'type' => 'discovery',
                'category' => 'values',
                'weight' => 5,
            ],

            // FUN
            [
                'question' => 'Tu choisis...',
                'option_a' => '🍕 Pizza',
                'option_b' => '🍔 Burger',
                'type' => 'discovery',
                'category' => 'fun',
                'weight' => 1,
            ],

            [
                'question' => 'Tu préfères...',
                'option_a' => '🐶 Chien',
                'option_b' => '🐱 Chat',
                'type' => 'discovery',
                'category' => 'fun',
                'weight' => 1,
            ],

            [
                'question' => 'Tu préfères pouvoir...',
                'option_a' => '🧠 Lire les pensées',
                'option_b' => '🔮 Voir le futur',
                'type' => 'discovery',
                'category' => 'fun',
                'weight' => 1,
            ],
        ];

        foreach ($questions as $question) {
            PopChoice::updateOrCreate(
                [
                    'question' => $question['question'],
                ],
                $question
            );
        }
    }
}
