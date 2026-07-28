<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanItem extends Model
{
    protected $fillable = [
        'study_plan_id',
        'session_number',
        'sort_order',
        'scripture_chapter_id',
        'talk_id',
        'scheduled_date',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ScriptureChapter::class, 'scripture_chapter_id');
    }

    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }
}
