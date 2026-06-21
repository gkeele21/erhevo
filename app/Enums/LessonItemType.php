<?php

namespace App\Enums;

enum LessonItemType: string
{
    case Scripture = 'scripture';
    case Talk = 'talk';
    case Video = 'video';
    case Text = 'text';
    case Question = 'question';

    public function label(): string
    {
        return match ($this) {
            self::Scripture => 'Scripture',
            self::Talk => 'Talk / Quote',
            self::Video => 'Video / Link',
            self::Text => 'My Words',
            self::Question => 'Question',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scripture => 'A scripture reference or passage',
            self::Talk => 'A talk or quote from the library',
            self::Video => 'A video or external link',
            self::Text => 'Your own writing',
            self::Question => 'A question to ask the class',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scripture => 'book-open',
            self::Talk => 'chat-bubble-bottom-center-text',
            self::Video => 'film',
            self::Text => 'document-text',
            self::Question => 'question-mark-circle',
        };
    }
}
