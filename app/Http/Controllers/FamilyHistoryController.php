<?php

namespace App\Http\Controllers;

use App\Models\Ancestor;
use App\Services\FamilyHistory\ChurchHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FamilyHistoryController extends Controller
{
    public const FILTERS = ['all', 'unresearched', 'researched', 'baptized', 'pioneers', 'church_places'];

    private const SORTS = ['generation', 'name', 'birth', 'baptism'];

    /** Generations shown in the pedigree chart, counting the focus person. */
    private const PEDIGREE_GENERATIONS = 4;

    public function index(Request $request)
    {
        $tree = $request->user()->familyTree;
        if (! $tree) {
            return redirect()->route('family-history.import');
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', Rule::in(self::FILTERS)],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ]);
        $filter = $validated['filter'] ?? 'all';
        $sort = $validated['sort'] ?? 'generation';

        $query = $this->filtered($request, $filter);
        if ($search = trim($validated['q'] ?? '')) {
            $query->where('ancestors.name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%');
        }
        match ($sort) {
            'name' => $query->orderBy('ancestors.surname')->orderBy('ancestors.given_name'),
            'birth' => $query->orderByRaw('ancestors.birth_year IS NULL')->orderBy('ancestors.birth_year'),
            'baptism' => $query->orderByRaw(Ancestor::EFFECTIVE_BAPTISM_SORT.' IS NULL')->orderByRaw(Ancestor::EFFECTIVE_BAPTISM_SORT),
            default => $query->orderBy('ancestors.generation')->orderBy('ancestors.ahnentafel'),
        };

        $ancestors = $query->paginate(50)->withQueryString()
            ->through(fn (Ancestor $ancestor) => $ancestor->summary());

        return Inertia::render('FamilyHistory/Index', [
            'tree' => $tree->only('root_name', 'root_fs_id', 'ancestor_count', 'generation_count', 'imported_at'),
            'ancestors' => $ancestors,
            'filters' => ['q' => $validated['q'] ?? '', 'filter' => $filter, 'sort' => $sort],
            'stats' => $this->stats($request),
            'firstBaptized' => $this->ancestors($request)->baptizedWhileLiving()
                ->whereRaw(Ancestor::EFFECTIVE_BAPTISM_SORT.' IS NOT NULL')
                ->orderByRaw(Ancestor::EFFECTIVE_BAPTISM_SORT)
                ->first()?->summary(),
        ]);
    }

    public function show(Request $request, string $fsId)
    {
        $ancestor = $this->find($request, $fsId);
        $lineFsIds = array_filter([$ancestor->father_fs_id, $ancestor->mother_fs_id]);
        $related = $this->ancestors($request)
            ->where(fn (Builder $q) => $q
                ->whereIn('ancestors.fs_id', $lineFsIds)
                ->orWhere('ancestors.father_fs_id', $ancestor->fs_id)
                ->orWhere('ancestors.mother_fs_id', $ancestor->fs_id))
            ->orderBy('ancestors.generation')
            ->get();

        return Inertia::render('FamilyHistory/Show', [
            'ancestor' => $ancestor->summary() + [
                'given_name' => $ancestor->given_name,
                'birth_date' => $ancestor->birth_date,
                'death_date' => $ancestor->death_date,
                'death_place' => $ancestor->death_place,
                'deceased' => $ancestor->deceased,
                'burial_date' => $ancestor->burial_date,
                'burial_place' => $ancestor->burial_place,
                'gedcom_baptism_date' => $ancestor->lds_baptism_date,
                'gedcom_baptized_while_living' => $ancestor->baptized_while_living,
                'likely_pioneer' => $ancestor->likely_pioneer,
                'pioneer_signals' => $ancestor->pioneer_signals ?? [],
                // Full place records; summary()'s church_places is labels only.
                'church_place_details' => $ancestor->church_places ?? [],
                'lds_affiliation' => $ancestor->lds_affiliation,
                'events' => $ancestor->events ?? [],
                'links' => [
                    'familysearch' => $ancestor->familySearchUrl('details'),
                    'memories' => $ancestor->familySearchUrl('memories'),
                    'ordinances' => $ancestor->familySearchUrl('ordinances'),
                ],
            ],
            'research' => [
                'researched' => $ancestor->researched_at !== null,
                'researched_at' => $ancestor->researched_at,
                'notes' => $ancestor->research_notes,
                'lds_baptism_on' => $ancestor->research_baptism_on,
                'baptized_while_living' => $ancestor->research_baptized === null ? null : (bool) $ancestor->research_baptized,
                'pioneer' => $ancestor->research_pioneer === null ? null : (bool) $ancestor->research_pioneer,
            ],
            'parents' => $related->whereIn('fs_id', $lineFsIds)->map->summary()->values(),
            'child' => $related->first(fn ($a) => in_array($ancestor->fs_id, [$a->father_fs_id, $a->mother_fs_id], true))?->summary(),
        ]);
    }

    public function pedigree(Request $request, ?string $fsId = null)
    {
        $tree = $request->user()->familyTree;
        if (! $tree) {
            return redirect()->route('family-history.import');
        }
        $focus = $this->find($request, $fsId ?? $tree->root_fs_id);

        // Heap layout: slot 1 is the focus, slot n's parents are 2n and 2n+1.
        $slots = [1 => $focus];
        $wanted = [1 => $focus];
        for ($generation = 1; $generation < self::PEDIGREE_GENERATIONS; $generation++) {
            $parentIds = [];
            foreach ($wanted as $slot => $person) {
                if ($person->father_fs_id) {
                    $parentIds[2 * $slot] = $person->father_fs_id;
                }
                if ($person->mother_fs_id) {
                    $parentIds[2 * $slot + 1] = $person->mother_fs_id;
                }
            }
            if (! $parentIds) {
                break;
            }
            $found = $this->ancestors($request)->whereIn('ancestors.fs_id', array_values($parentIds))->get()->keyBy('fs_id');
            $wanted = [];
            foreach ($parentIds as $slot => $parentId) {
                if ($parent = $found->get($parentId)) {
                    $slots[$slot] = $wanted[$slot] = $parent;
                }
            }
        }

        return Inertia::render('FamilyHistory/Pedigree', [
            'generations' => self::PEDIGREE_GENERATIONS,
            'slots' => collect($slots)->map(fn (Ancestor $a) => $a->summary() + [
                'has_parents' => (bool) ($a->father_fs_id || $a->mother_fs_id),
            ]),
            'focusChild' => $focus->generation > 0
                ? $this->ancestors($request)
                    ->where(fn (Builder $q) => $q->where('ancestors.father_fs_id', $focus->fs_id)->orWhere('ancestors.mother_fs_id', $focus->fs_id))
                    ->orderBy('ancestors.generation')
                    ->first()?->summary()
                : null,
        ]);
    }

    /** "Surprise me": a random unresearched ancestor, within the current filter. */
    public function random(Request $request)
    {
        $filter = in_array($request->query('filter'), self::FILTERS, true) ? $request->query('filter') : 'all';
        $query = $this->filtered($request, $filter);
        if ($filter !== 'researched') {
            $query->whereNull('ancestor_research.researched_at');
        }
        $ancestor = $query->inRandomOrder()->first();

        if (! $ancestor) {
            return back()->with('success', 'You have researched everyone here — nice work!');
        }

        return redirect()->route('family-history.show', $ancestor->fs_id);
    }

    public function updateResearch(Request $request, string $fsId)
    {
        $ancestor = $this->find($request, $fsId);

        $validated = $request->validate([
            'researched' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'lds_baptism_on' => ['nullable', 'date', 'date_format:Y-m-d'],
            'baptized_while_living' => ['nullable', 'boolean'],
            'pioneer' => ['nullable', 'boolean'],
        ]);

        $research = $request->user()->ancestorResearch()->firstOrNew(['fs_id' => $ancestor->fs_id]);
        $research->fill([
            'notes' => $validated['notes'] ?? null,
            'lds_baptism_on' => $validated['lds_baptism_on'] ?? null,
            'baptized_while_living' => $validated['baptized_while_living'] ?? null,
            'pioneer' => $validated['pioneer'] ?? null,
            'researched_at' => $validated['researched'] ? ($research->researched_at ?? now()) : null,
        ])->save();

        return back()->with('success', 'Research saved.');
    }

    private function ancestors(Request $request): Builder
    {
        return Ancestor::query()->withResearch()->where('ancestors.user_id', $request->user()->id);
    }

    private function find(Request $request, string $fsId): Ancestor
    {
        return $this->ancestors($request)->where('ancestors.fs_id', $fsId)->firstOrFail();
    }

    /** The list's population: ancestors only (not the root), narrowed by filter. */
    private function filtered(Request $request, string $filter): Builder
    {
        $query = $this->ancestors($request)->where('ancestors.generation', '>', 0);

        return match ($filter) {
            'unresearched' => $query->whereNull('ancestor_research.researched_at'),
            'researched' => $query->whereNotNull('ancestor_research.researched_at'),
            'baptized' => $query->baptizedWhileLiving(),
            'pioneers' => $query->pioneers(),
            'church_places' => $query->where('ancestors.has_church_places', true),
            default => $query,
        };
    }

    private function stats(Request $request): array
    {
        $row = $this->ancestors($request)
            ->where('ancestors.generation', '>', 0)
            ->reorder()
            ->select([])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN ancestor_research.researched_at IS NOT NULL THEN 1 ELSE 0 END) as researched')
            ->selectRaw('SUM(CASE WHEN '.Ancestor::EFFECTIVE_BAPTIZED.' = 1 THEN 1 ELSE 0 END) as baptized')
            ->selectRaw('SUM(CASE WHEN '.Ancestor::EFFECTIVE_PIONEER.' = 1 THEN 1 ELSE 0 END) as pioneers')
            ->selectRaw('SUM(CASE WHEN ancestors.has_church_places = 1 THEN 1 ELSE 0 END) as church_places')
            ->toBase()
            ->first();

        // Per-place counts; only a few dozen rows ever have places.
        $places = collect(ChurchHistory::PLACES)->map(fn ($p) => ['label' => $p['label'], 'years' => $p['from'].'–'.$p['to'], 'count' => 0]);
        DB::table('ancestors')->where('user_id', $request->user()->id)->where('has_church_places', true)
            ->pluck('church_places')
            ->each(function ($json) use ($places) {
                foreach (json_decode($json, true) ?? [] as $place) {
                    if ($places->has($place['key'])) {
                        $places->put($place['key'], ['count' => $places[$place['key']]['count'] + 1] + $places[$place['key']]);
                    }
                }
            });

        return [
            'total' => (int) $row->total,
            'researched' => (int) $row->researched,
            'baptized' => (int) $row->baptized,
            'pioneers' => (int) $row->pioneers,
            'church_places' => (int) $row->church_places,
            'places' => $places->filter(fn ($p) => $p['count'] > 0)->values(),
        ];
    }
}
