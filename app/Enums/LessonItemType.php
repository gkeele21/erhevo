<?php

namespace App\Enums;

enum LessonItemType: string
{
    case Scripture = 'scripture';
    case Talk = 'talk';
    case Quote = 'quote';
    case ScriptureHelp = 'scripture_help';
    case Video = 'video';
    case Image = 'image';
    case Text = 'text';
    case Question = 'question';
    case Group = 'group';

    public function label(): string
    {
        return match ($this) {
            self::Scripture => 'Scripture',
            self::Talk => 'Talk',
            self::Quote => 'Quote',
            self::ScriptureHelp => 'Scripture Help',
            self::Video => 'Video / Link',
            self::Image => 'Image',
            self::Text => 'My Writing',
            self::Question => 'Question',
            self::Group => 'Group',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scripture => 'A scripture reference or passage',
            self::Talk => 'A talk from the library',
            self::Quote => 'A quote you can save, tag, and reuse',
            self::ScriptureHelp => 'Insight or context for a scripture passage — new or from your posts',
            self::Video => 'A video or external link',
            self::Image => 'A picture, with an optional caption',
            self::Text => 'Write something new, or pull in one of your posts',
            self::Question => 'A question to ask the class',
            self::Group => 'A named group of items',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scripture => 'book-open',
            self::Talk => 'chat-bubble-bottom-center-text',
            self::Quote => 'chat-bubble-left-right',
            self::ScriptureHelp => 'academic-cap',
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
