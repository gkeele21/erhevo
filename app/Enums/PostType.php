<?php

namespace App\Enums;

enum PostType: string
{
    case Story = 'story';
    case Thought = 'thought';
    case Note = 'note';
    case Quote = 'quote';
    case Video = 'video';
    case Image = 'image';
    case ScriptureHelp = 'scripture_help';
    case MeetingNotes = 'meeting_notes';

    public function label(): string
    {
        return match ($this) {
            self::Story => 'Story',
            self::Thought => 'Thought',
            self::Note => 'Note',
            self::Quote => 'Quote',
            self::Video => 'Video / Link',
            self::Image => 'Image',
            self::ScriptureHelp => 'Scripture Help',
            self::MeetingNotes => 'Meeting Notes',
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Story => 'Stories',
            self::Thought => 'Thoughts',
            self::Note => 'Notes',
            self::Quote => 'Quotes',
            self::Video => 'Videos / Links',
            self::Image => 'Images',
            self::ScriptureHelp => 'Scripture Helps',
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
            self::Video => 'A video or link worth keeping, with your notes',
            self::Image => 'A picture worth keeping, with your notes',
            self::ScriptureHelp => 'Insight, context, or explanation for a scripture',
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
            self::Video => 'film',
            self::Image => 'photo',
            self::ScriptureHelp => 'academic-cap',
            self::MeetingNotes => 'clipboard-document-list',
        };
    }
}
