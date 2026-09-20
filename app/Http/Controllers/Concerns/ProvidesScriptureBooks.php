<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ScriptureVolume;

trait ProvidesScriptureBooks
{
    /**
     * Volumes → books → chapters (with verse_count) for the scripture picker.
     */
    protected function scriptureBooksTree(): array
    {
        return ScriptureVolume::query()
            ->orderBy('sort_order')
            ->with(['books' => fn ($q) => $q->orderBy('sort_order')
                ->with(['chapters' => fn ($q2) => $q2->orderBy('chapter_number')])])
            ->get()
            ->map(fn ($volume) => [
                'name' => $volume->name,
                'books' => $volume->books->map(fn ($book) => [
                    'id' => $book->id,
                    'name' => $book->name,
                    'chapters' => $book->chapters->map(fn ($c) => [
                        'id' => $c->id,
                        'number' => $c->chapter_number,
                        'verse_count' => $c->verse_count,
                    ])->values(),
                ])->values(),
            ])->values()->toArray();
    }
}
