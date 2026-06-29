<?php

namespace App\Enums;

enum LessonItemType: string
{
    case Scripture = 'scripture';
    case Talk = 'talk';
    case Video = 'video';
    case Image = 'image';
    case Text = 'text';
    case Question = 'question';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Scripture => 'Scripture',
            self::Talk => 'Talk / Quote',
            self::Video => 'Video / Link',
            self::Image => 'Image',
            self::Text => 'My Words',
            self::Question => 'Question',
            self::Group => 'Group',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scripture => 'A scripture reference or passage',
            self::Talk => 'A talk or quote from the library',
            self::Video => 'A video or external link',
            self::Image => 'A picture, with an optional caption',
            self::Text => 'Your own writing',
            self::Question => 'A question to ask the class',
            self::Group => 'A named group of items',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scripture => 'book-open',
            self::Talk => 'chat-bubble-bottom-center-text',
            self::Video => 'film',
            self::Image => 'photo',
            self::Text => 'document-text',
            self::Question => 'question-mark-circle',
            self::Group => 'folder',
        };
    }

    /**
     * Content block types (everything except the Group container).
     */
    public static function contentCases(): array
    {
        return array_filter(self::cases(), fn (self $t) => $t !== self::Group);
    }
}
