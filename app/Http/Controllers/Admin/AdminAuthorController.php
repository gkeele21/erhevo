<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\AuthorCalling;
use App\Models\ChurchCalling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthorController extends Controller
{
    public function index(Request $request): Response
    {
        $authors = Author::query()
            ->with('calling')
            ->withCount(['posts', 'callings'])
            ->when($request->search, fn ($q, $s) => $q->search($s))
            ->when($request->calling, fn ($q, $c) => $q->where('church_calling_id', $c))
            ->orderBy('last_name')
            ->orderBy('display_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Authors/Index', [
            'authors' => $authors,
            'filters' => $request->only(['search', 'calling']),
            'callings' => $this->callingOptions(),
        ]);
    }

    public function edit(Author $author): Response
    {
        $author->load(['user', 'callings.calling']);

        return Inertia::render('Admin/Authors/Edit', [
            'author' => $author,
            'callings' => $this->callingOptions(),
        ]);
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'display_name' => 'nullable|string|max:255',
            'church_calling_id' => 'nullable|exists:church_callings,id',
            'calling_started_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $author->update($validated);

        return back()->with('success', 'Author updated.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $author->delete();

        return redirect()->route('admin.authors.index')->with('success', 'Author deleted.');
    }

    /** Add a calling stint to an author's history. */
    public function storeCalling(Request $request, Author $author): RedirectResponse
    {
        $validated = $request->validate([
            'church_calling_id' => 'required|exists:church_callings,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $author->callings()->create($validated);

        return back()->with('success', 'Calling added.');
    }

    public function updateCalling(Request $request, Author $author, AuthorCalling $calling): RedirectResponse
    {
        abort_unless($calling->author_id === $author->id, 404);

        $validated = $request->validate([
            'church_calling_id' => 'required|exists:church_callings,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $calling->update($validated);

        return back()->with('success', 'Calling updated.');
    }

    public function destroyCalling(Author $author, AuthorCalling $calling): RedirectResponse
    {
        abort_unless($calling->author_id === $author->id, 404);

        $calling->delete();

        return back()->with('success', 'Calling removed.');
    }

    /** Callings for select menus, labelled with their organization. */
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
