<?php

namespace App\Enums;

enum PostType: string
{
    case Story = 'story';
    case Thought = 'thought';
    case Note = 'note';
    case Quote = 'quote';
    case MeetingNotes = 'meeting_notes';

    public function label(): string
    {
        return match ($this) {
            self::Story => 'Story',
            self::Thought => 'Thought',
            self::Note => 'Note',
            self::Quote => 'Quote',
            self::MeetingNotes => 'Meeting Notes',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Story => 'A narrative or longer piece of writing',
            self::Thought => 'A brief reflection or idea',
            self::Note => 'A reference or reminder',
            self::Quote => 'Words from someone else',
            self::MeetingNotes => 'Notes from a church meeting or class',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Story => 'book-open',
            self::Thought => 'lightbulb',
            self::Note => 'document-text',
            self::Quote => 'chat-bubble-bottom-center-text',
            self::MeetingNotes => 'clipboard-document-list',
        };
    }
}
