<?php

namespace App\Support;

/**
 * Frequently asked questions, shown on the home page and published in structured data and llms.txt.
 */
class Faqs
{
    /**
     * Get the questions and answers.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function all(): array
    {
        return [
            [
                'question' => 'How does it work?',
                'answer' => 'Fill in the quote request form with a few details about the job. We review it, match it with the right tradesperson and send you a quote. Once you log in, you can add photos and follow updates on your request.',
            ],
            [
                'question' => 'Which areas do you cover?',
                'answer' => 'We cover metropolitan Melbourne, including the inner city and the northern, eastern, south-eastern, bayside and western suburbs. We can only take jobs at Victorian addresses.',
            ],
            [
                'question' => 'Do I need an account to request a quote?',
                'answer' => 'No. Anyone can submit a request. To add photos or see quotes and updates, create an account or log in with the same email address you used on the form, and your request is linked automatically.',
            ],
            [
                'question' => 'Why do I need to log in to add photos?',
                'answer' => 'Photos of your home are private. Keeping them behind a login means only you and our team can see them.',
            ],
            [
                'question' => 'Can I change my request after submitting it?',
                'answer' => 'Yes. While your request is submitted or under review, you can update the details, add or remove photos, or delete it.',
            ],
            [
                'question' => 'What size jobs do you take on?',
                'answer' => 'Anything from a single repair, like a leaking tap or a sticking door, through to full kitchen and bathroom renovations.',
            ],
            [
                'question' => 'What if it\'s an emergency?',
                'answer' => 'Choose "Emergency (ASAP)" as the urgency and we\'ll prioritise your request. If there\'s immediate danger, such as a gas leak, exposed wiring or flooding, call 000 first.',
            ],
        ];
    }
}
