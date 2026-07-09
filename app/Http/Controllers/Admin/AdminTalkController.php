<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChurchCalling;
use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTalkController extends Controller
{
    public function index(Request $request): Response
    {
        $talks = Talk::query()
            ->with(['calling.organization', 'source'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('speaker_name', 'like', "%{$s}%")))
            ->when($request->year, fn ($q, $y) => $q->whereYear('talk_date', $y))
            ->when($request->source, fn ($q, $s) => $q->where('source_id', $s))
            ->orderByDesc('talk_date')
            ->orderBy('display_order')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Talk $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'speaker' => trim(($t->speaker_title ? $t->speaker_title . ' ' : '') . $t->speaker_name),
                'calling' => $t->calling?->display_label,
                'date' => $t->talk_date?->toDateString(),
                'source' => $t->source?->name,
                'url' => $t->url,
            ]);

        return Inertia::render('Admin/Talks/Index', [
            'talks' => $talks,
            'filters' => $request->only(['search', 'year', 'source']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'years' => Talk::selectRaw('YEAR(talk_date) as y')->whereNotNull('talk_date')->distinct()->orderByDesc('y')->pluck('y'),
        ]);
    }

    public function edit(Talk $talk): Response
    {
        $talk->load(['calling', 'source', 'talkType', 'author']);

        return Inertia::render('Admin/Talks/Edit', [
            'talk' => $talk,
            'callings' => $this->callingOptions(),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'talkTypes' => TalkType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Talk $talk): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'speaker_name' => 'nullable|string|max:255',
            'author_id' => 'nullable|exists:authors,id',
            'speaker_title' => 'nullable|string|max:255',
            'church_calling_id' => 'nullable|exists:church_callings,id',
            'talk_date' => 'nullable|date',
            'url' => 'nullable|url|max:1000',
            'summary' => 'nullable|string',
            'source_id' => 'nullable|exists:sources,id',
            'talk_type_id' => 'nullable|exists:talk_types,id',
        ]);

        $talk->update($validated);

        return back()->with('success', 'Talk updated.');
    }

    public function destroy(Talk $talk): RedirectResponse
    {
        $talk->delete();

        return redirect()->route('admin.talks.index')->with('success', 'Talk deleted.');
    }

    protected function callingOptions(): array
    {
        return ChurchCalling::with('organization')
            ->orderBy('church_organization_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ChurchCalling $c) => [
                'id' => $c->id,
                'label' => $c->display_label,
                'active' => (bool) $c->is_active,
            ])
            ->toArray();
    }
}
