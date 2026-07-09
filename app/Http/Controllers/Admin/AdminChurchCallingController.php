<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChurchCalling;
use App\Models\ChurchOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminChurchCallingController extends Controller
{
    public function index(): Response
    {
        $callings = ChurchCalling::with('organization')
            ->withCount('authors')
            ->orderBy('church_organization_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/ChurchCallings/Index', [
            'callings' => $callings,
            'organizations' => ChurchOrganization::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ChurchCalling::create($request->validate($this->rules()));

        return back()->with('success', 'Calling created.');
    }

    public function update(Request $request, ChurchCalling $churchCalling): RedirectResponse
    {
        $churchCalling->update($request->validate($this->rules()));

        return back()->with('success', 'Calling updated.');
    }

    public function destroy(ChurchCalling $churchCalling): RedirectResponse
    {
        $churchCalling->delete();

        return back()->with('success', 'Calling deleted.');
    }

    protected function rules(): array
    {
        return [
            'church_organization_id' => 'nullable|exists:church_organizations,id',
            'name' => 'nullable|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }
}
