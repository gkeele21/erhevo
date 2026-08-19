<?php

namespace App\Http\Controllers;

use App\Enums\Ordinance;
use App\Models\Temple;
use App\Models\TempleVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TempleVisitController extends Controller
{
    public function index(Request $request)
    {
        $visits = $request->user()->templeVisits()
            ->with('temple:id,slug,name,city,state,country')
            ->orderByDesc('visited_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TempleVisit $visit) => [
                'id' => $visit->id,
                'visited_on' => $visit->visited_on->toDateString(),
                'ordinances' => $visit->ordinances,
                'notes' => $visit->notes,
                'temple' => [
                    'id' => $visit->temple->id,
                    'slug' => $visit->temple->slug,
                    'name' => $visit->temple->name,
                    'city' => $visit->temple->city,
                    'state' => $visit->temple->state,
                    'country' => $visit->temple->country,
                ],
            ]);

        return Inertia::render('Temples/MyVisits', [
            'visits' => $visits,
            'temples' => Temple::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateVisit($request);

        $request->user()->templeVisits()->create($validated);

        return back()->with('success', 'Visit logged.');
    }

    public function update(Request $request, TempleVisit $visit)
    {
        Gate::authorize('update', $visit);

        $visit->update($this->validateVisit($request));

        return back()->with('success', 'Visit updated.');
    }

    public function destroy(TempleVisit $visit)
    {
        Gate::authorize('delete', $visit);

        $visit->delete();

        return back()->with('success', 'Visit deleted.');
    }

    protected function validateVisit(Request $request): array
    {
        $validated = $request->validate([
            'temple_id' => ['required', 'exists:temples,id'],
            'visited_on' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'ordinances' => ['present', 'array'],
            'ordinances.*' => [Rule::enum(Ordinance::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // MySQL JSON columns can't have defaults; an empty array means
        // "just a visit".
        $validated['ordinances'] = array_values(array_unique($validated['ordinances']));

        return $validated;
    }
}
