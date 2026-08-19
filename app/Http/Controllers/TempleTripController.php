<?php

namespace App\Http\Controllers;

use App\Models\Temple;
use App\Models\TempleTrip;
use App\Models\TempleTripItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TempleTripController extends Controller
{
    public function index(Request $request)
    {
        $trips = $request->user()->templeTrips()
            ->withCount([
                'items',
                'items as completed_items_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->latest()
            ->get()
            ->map(fn (TempleTrip $trip) => [
                'id' => $trip->id,
                'name' => $trip->name,
                'notes' => $trip->notes,
                'items_count' => $trip->items_count,
                'completed_items_count' => $trip->completed_items_count,
            ]);

        return Inertia::render('Temples/Trips/Index', [
            'trips' => $trips,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'temple_ids' => ['sometimes', 'array'],
            'temple_ids.*' => ['integer', 'exists:temples,id'],
        ]);

        $trip = $request->user()->templeTrips()->create([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->syncItems($trip, array_unique($validated['temple_ids'] ?? []));

        return redirect()->route('temple-trips.show', $trip)->with('success', 'Trip created.');
    }

    public function show(Request $request, TempleTrip $trip)
    {
        Gate::authorize('view', $trip);

        $trip->load('items.temple');

        return Inertia::render('Temples/Trips/Show', [
            'trip' => [
                'id' => $trip->id,
                'name' => $trip->name,
                'notes' => $trip->notes,
                'items' => $trip->items->map(fn (TempleTripItem $item) => [
                    'id' => $item->id,
                    'completed_at' => $item->completed_at?->toIso8601String(),
                    'temple' => [
                        'id' => $item->temple->id,
                        'slug' => $item->temple->slug,
                        'name' => $item->temple->name,
                        'city' => $item->temple->city,
                        'state' => $item->temple->state,
                        'country' => $item->temple->country,
                        'latitude' => $item->temple->latitude,
                        'longitude' => $item->temple->longitude,
                    ],
                ]),
            ],
            // Coordinates ride along so the trip page can plot (and offer)
            // temples near the ones already planned.
            'allTemples' => Temple::orderBy('name')
                ->get(['id', 'slug', 'name', 'city', 'state', 'country', 'latitude', 'longitude']),
            'visitedTempleIds' => $request->user()->templeVisits()->distinct()->pluck('temple_id')->all(),
        ]);
    }

    public function update(Request $request, TempleTrip $trip)
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'temple_ids' => ['sometimes', 'array'],
            'temple_ids.*' => ['integer', 'exists:temples,id'],
        ]);

        $trip->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->has('temple_ids')) {
            $this->syncItems($trip, array_unique($validated['temple_ids']));
        }

        return back()->with('success', 'Trip updated.');
    }

    public function destroy(TempleTrip $trip)
    {
        Gate::authorize('delete', $trip);

        $trip->delete();

        return redirect()->route('temple-trips.index')->with('success', 'Trip deleted.');
    }

    public function addItem(Request $request, TempleTrip $trip)
    {
        Gate::authorize('update', $trip);

        $validated = $request->validate([
            'temple_id' => ['required', 'integer', 'exists:temples,id'],
        ]);

        $trip->items()->firstOrCreate(
            ['temple_id' => $validated['temple_id']],
            ['sort_order' => ($trip->items()->max('sort_order') ?? -1) + 1]
        );

        return back()->with('success', 'Temple added to trip.');
    }

    public function removeItem(TempleTrip $trip, TempleTripItem $item)
    {
        Gate::authorize('update', $trip);
        abort_unless($item->temple_trip_id === $trip->id, 404);

        $item->delete();

        return back()->with('success', 'Temple removed from trip.');
    }

    public function toggleItem(TempleTrip $trip, TempleTripItem $item)
    {
        Gate::authorize('update', $trip);
        abort_unless($item->temple_trip_id === $trip->id, 404);

        $item->update(['completed_at' => $item->completed_at ? null : now()]);

        return back();
    }

    /**
     * Make the trip's items match $templeIds in that order, preserving
     * completed_at on temples that stay in the trip.
     */
    protected function syncItems(TempleTrip $trip, array $templeIds): void
    {
        $trip->items()->whereNotIn('temple_id', $templeIds)->delete();

        foreach (array_values($templeIds) as $order => $templeId) {
            $trip->items()->updateOrCreate(
                ['temple_id' => $templeId],
                ['sort_order' => $order]
            );
        }
    }
}
