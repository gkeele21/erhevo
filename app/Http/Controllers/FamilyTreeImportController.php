<?php

namespace App\Http\Controllers;

use App\Services\FamilyHistory\FamilyTreeImporter;
use App\Services\FamilyHistory\GedcomException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FamilyTreeImportController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('FamilyHistory/Import', [
            'tree' => $request->user()->familyTree?->only('root_name', 'root_fs_id', 'people_count', 'ancestor_count', 'generation_count', 'source', 'imported_at'),
            'maxUploadMb' => (int) floor($this->maxUploadKb() / 1024),
        ]);
    }

    public function store(Request $request, FamilyTreeImporter $importer)
    {
        $validated = $request->validate([
            // GEDCOM has no reliable mime type; check the extension, and let
            // the parser reject anything that isn't really GEDCOM.
            'file' => ['required', 'file', 'max:'.$this->maxUploadKb(), function ($attribute, $value, $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'ged') {
                    $fail('Choose a GEDCOM file (ending in .ged).');
                }
            }],
            'root_fs_id' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]{4}-[A-Z0-9]{3,4}$/i'],
        ], [
            'root_fs_id.regex' => 'A FamilySearch ID looks like KWCM-8HC.',
        ]);

        // Parsed straight from the upload's temp file and never stored — the
        // file includes living relatives, and only direct ancestors are kept.
        try {
            $tree = $importer->import($request->user(), $validated['file']->getRealPath(), $validated['root_fs_id'] ?? null);
        } catch (GedcomException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return redirect()->route('family-history.index')
            ->with('success', "Imported {$tree->ancestor_count} ancestors across {$tree->generation_count} generations.");
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->ancestors()->delete();
        $user->ancestorResearch()->delete();
        $user->familyTree()->delete();

        return redirect()->route('family-history.import')->with('success', 'Your family tree and research notes were deleted.');
    }

    private function maxUploadKb(): int
    {
        $toKb = function (string $size): int {
            $number = (int) $size;

            return match (strtoupper(substr(trim($size), -1))) {
                'G' => $number * 1024 * 1024,
                'M' => $number * 1024,
                'K' => $number,
                default => intdiv($number, 1024),
            };
        };

        // Whichever PHP limit bites first; post_max_size of 0 means unlimited.
        $limits = array_filter([$toKb((string) ini_get('upload_max_filesize')), $toKb((string) ini_get('post_max_size'))]);

        return $limits ? min($limits) : 50 * 1024;
    }
}
