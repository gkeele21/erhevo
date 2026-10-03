<?php

namespace App\Http\Controllers;

use App\Services\FamilyHistory\FamilyTreeImporter;
use App\Services\FamilyHistory\GedcomException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FamilyTreeImportController extends Controller
{
    /** Largest GEDCOM we accept, even where PHP would allow more. */
    private const MAX_UPLOAD_KB = 50 * 1024;

    /**
     * Parsing peaks at roughly 12× the file size (a 13 MB tree used ~150 MB),
     * which a 50 MB upload would push well past PHP's usual 128 MB.
     */
    private const IMPORT_MEMORY = '1024M';

    private const IMPORT_SECONDS = 180;

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

        $this->raiseLimitsForImport();

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

    /**
     * Our 50 MB cap, or less where the server's PHP limits are lower — set
     * upload_max_filesize and post_max_size (and nginx's
     * client_max_body_size) to at least 50M on the server.
     */
    private function maxUploadKb(): int
    {
        // post_max_size of 0 means unlimited.
        $limits = array_filter([
            self::toKb((string) ini_get('upload_max_filesize')),
            self::toKb((string) ini_get('post_max_size')),
        ]);

        return min([self::MAX_UPLOAD_KB, ...$limits]);
    }

    /** More memory and time for this request only; never lowers either. */
    private function raiseLimitsForImport(): void
    {
        $current = (string) ini_get('memory_limit');
        if ($current !== '-1' && self::toKb($current) < self::toKb(self::IMPORT_MEMORY)) {
            ini_set('memory_limit', self::IMPORT_MEMORY);
        }

        $seconds = (int) ini_get('max_execution_time');
        if ($seconds !== 0 && $seconds < self::IMPORT_SECONDS) {
            set_time_limit(self::IMPORT_SECONDS);
        }
    }

    /** PHP shorthand ("2M", "1G", "512K", bytes) to kilobytes. */
    private static function toKb(string $size): int
    {
        $number = (int) $size;

        return match (strtoupper(substr(trim($size), -1))) {
            'G' => $number * 1024 * 1024,
            'M' => $number * 1024,
            'K' => $number,
            default => intdiv($number, 1024),
        };
    }
}
