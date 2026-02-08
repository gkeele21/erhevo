<?php

namespace App\Enums;

enum PostType: string
{
    case Story = 'story';
    case Thought = 'thought';
    case Note = 'note';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Story => 'Story',
            self::Thought => 'Thought',
            self::Note => 'Note',
            self::Quote => 'Quote',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Story => 'A narrative or longer piece of writing',
            self::Thought => 'A brief reflection or idea',
            self::Note => 'A reference or reminder',
            self::Quote => 'Words from someone else',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Story => 'book-open',
            self::Thought => 'lightbulb',
            self::Note => 'document-text',
            self::Quote => 'chat-bubble-bottom-center-text',
        };
    }
}
