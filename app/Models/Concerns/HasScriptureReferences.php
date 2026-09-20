<?php

namespace App\Models\Concerns;

use App\Models\ScriptureReference;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lets a model point at passages of scripture, so it can be found from them.
 */
trait HasScriptureReferences
{
    public static function bootHasScriptureReferences(): void
    {
        static::deleted(function ($model) {
            // A soft delete keeps its references — the model can come back.
            // Only a real delete takes them with it.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->scriptureReferences()->delete();
        });
    }

    public function scriptureReferences(): MorphMany
    {
        return $this->morphMany(ScriptureReference::class, 'referenceable')->orderBy('sort_order');
    }

    /**
     * Replace this model's references with the given rows.
     *
     * Each row needs a start_chapter_id; start_verse, end_chapter_id and
     * end_verse are optional and describe how far the passage runs. Extra keys
     * are ignored, so callers can pass through their own display labels.
     */
    public function syncScriptureReferences(array $references): void
    {
        $this->scriptureReferences()->delete();

        foreach (array_values($references) as $index => $ref) {
            if (empty($ref['start_chapter_id'])) {
                continue;
            }

            $this->scriptureReferences()->create([
                'start_chapter_id' => $ref['start_chapter_id'],
                'start_verse' => $ref['start_verse'] ?? null,
                'end_chapter_id' => $ref['end_chapter_id'] ?? null,
                'end_verse' => $ref['end_verse'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }
}
