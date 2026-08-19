<?php

namespace App\Http\Controllers;

use App\Models\Temple;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TempleController extends Controller
{
    /**
     * The full temple list (~220 rows) ships to the client in one lean
     * payload; state/country filtering, search, and radius math all happen
     * client-side so the list and map views share one filtered dataset.
     */
    public function index(Request $request)
    {
        // Closures, so a partial reload after starring a temple (`only:
        // ['favoriteTempleIds']`) skips the full temple query.
        return Inertia::render('Temples/Index', [
            'temples' => fn () => $this->allTemples(),
            'visitedTempleIds' => fn () => $this->visitedTempleIds($request),
            'favoriteTempleIds' => fn () => $this->favoriteTempleIds($request),
            'filters' => $request->only(['country', 'state', 'q', 'view']),
        ]);
    }

    public function show(Request $request, Temple $temple)
    {
        $visits = $request->user()->templeVisits()
            ->where('temple_id', $temple->id)
            ->orderByDesc('visited_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($visit) => [
                'id' => $visit->id,
                'visited_on' => $visit->visited_on->toDateString(),
                'ordinances' => $visit->ordinances,
                'notes' => $visit->notes,
            ]);

        return Inertia::render('Temples/Show', [
            'temple' => [
                'id' => $temple->id,
                'slug' => $temple->slug,
                'name' => $temple->name,
                'address' => $temple->address,
                'city' => $temple->city,
                'state' => $temple->state,
                'country' => $temple->country,
                'latitude' => $temple->latitude,
                'longitude' => $temple->longitude,
                'photo_url' => $temple->photo_url,
                'dedicated_on' => $temple->dedicated_on->toDateString(),
                'source_url' => $temple->source_url,
            ],
            'visits' => $visits,
            'isFavorite' => fn () => $request->user()->favoriteTemples()
                ->where('temples.id', $temple->id)
                ->exists(),
        ]);
    }

    public function explore(Request $request)
    {
        return Inertia::render('Temples/Explore', [
            'temples' => $this->allTemples(),
            'visitedTempleIds' => $this->visitedTempleIds($request),
            'trips' => $request->user()->templeTrips()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Turn a typed address into a map center via Nominatim. Hits are cached
     * for a month (the same "Provo, UT" resolves for everyone) and misses are
     * not, so a transient upstream failure doesn't stick.
     */
    public function geocode(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if (Str::length($query) < 3) {
            return response()->json(['message' => 'Enter an address, city, or ZIP code.'], 422);
        }

        $key = 'temple-geocode:'.md5(Str::lower($query));

        if ($cached = Cache::get($key)) {
            return response()->json($cached);
        }

        $place = $this->lookupPlace($query);

        if (! $place) {
            return response()->json(['message' => "Couldn't find that address — try adding a city or state."], 404);
        }

        Cache::put($key, $place, now()->addDays(30));

        return response()->json($place);
    }

    protected function lookupPlace(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ErhevoBot/1.0 (temple tracker; https://erhevo.com)',
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);
        } catch (\Exception) {
            return null;
        }

        $hit = $response->successful() ? $response->json('0') : null;

        if (! isset($hit['lat'], $hit['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $hit['lat'],
            'lng' => (float) $hit['lon'],
            'label' => $hit['display_name'] ?? $query,
        ];
    }

    /**
     * Star / unstar a temple. Only `favoriteTempleIds` is reloaded, so the
     * ~220-temple payload doesn't ride along on every click.
     */
    public function toggleFavorite(Request $request, Temple $temple)
    {
        $request->user()->favoriteTemples()->toggle($temple->id);

        return back();
    }

    protected function allTemples()
    {
        return Temple::orderBy('name')->get()->map(fn (Temple $temple) => [
            'id' => $temple->id,
            'slug' => $temple->slug,
            'name' => $temple->name,
            'city' => $temple->city,
            'state' => $temple->state,
            'country' => $temple->country,
            'latitude' => $temple->latitude,
            'longitude' => $temple->longitude,
            'photo_url' => $temple->photo_url,
            'dedicated_on' => $temple->dedicated_on->toDateString(),
        ]);
    }

    protected function favoriteTempleIds(Request $request): array
    {
        return $request->user()->favoriteTemples()->pluck('temples.id')->all();
    }

    protected function visitedTempleIds(Request $request): array
    {
        return $request->user()->templeVisits()
            ->distinct()
            ->pluck('temple_id')
            ->all();
    }
}
