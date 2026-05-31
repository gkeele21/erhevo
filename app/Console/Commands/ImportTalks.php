<?php

namespace App\Console\Commands;

use App\Models\ChurchCalling;
use App\Models\ChurchOrganization;
use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ImportTalks extends Command
{
    protected $signature = 'talks:import
                            {source : The source slug (e.g., byu-speeches, ensign)}
                            {file? : Path to JSON file with talk data}';

    protected $description = 'Import talks from a JSON file for a specific source';

    private Collection $callings;
    private Collection $organizations;
    private Collection $talkTypes;
    private ?Source $source = null;

    public function handle(): int
    {
        $sourceSlug = $this->argument('source');
        $file = $this->argument('file') ?? database_path("data/{$sourceSlug}_talks.json");

        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $data = json_decode(File::get($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $this->loadLookupData($sourceSlug);

        if (!$this->source) {
            $this->error("Source \"{$sourceSlug}\" not found. Run SourceSeeder first.");
            return Command::FAILURE;
        }

        $totalTalks = 0;
        $newTalks = 0;

        foreach ($data as $year => $talks) {
            foreach ($talks as $order => $talkData) {
                $totalTalks++;

                $created = $this->importTalk($talkData, $order + 1);
                if ($created) {
                    $newTalks++;
                }
            }
        }

        $this->info("Import complete: {$newTalks} new talks imported ({$totalTalks} total processed)");

        return Command::SUCCESS;
    }

    private function loadLookupData(string $sourceSlug): void
    {
        $this->callings = ChurchCalling::with('organization')->get();
        $this->organizations = ChurchOrganization::all()->keyBy('name');
        $this->talkTypes = TalkType::all()->keyBy('slug');
        $this->source = Source::where('slug', $sourceSlug)->first();
    }

    private function importTalk(array $data, int $order): bool
    {
        $existing = Talk::where('source_id', $this->source->id)
            ->where('slug', $data['slug'])
            ->first();

        if ($existing) {
            return false;
        }

        $talkType = isset($data['type']) ? ($this->talkTypes[$data['type']] ?? null) : null;
        $calling = $this->findCalling($data['calling_prefix'] ?? null, $data['organization'] ?? null);
        $organizationName = $data['organization'] ?? null;
        $organization = $organizationName ? ($this->organizations[$organizationName] ?? null) : null;

        // Generate URL if not provided
        $url = $data['url'] ?? $this->source->generateTalkUrl($data['slug'], [
            'year' => Carbon::parse($data['date'])->year ?? null,
            'month' => Carbon::parse($data['date'])->format('m') ?? null,
        ]);

        Talk::create([
            'source_id' => $this->source->id,
            'talk_type_id' => $talkType?->id,
            'general_conference_session_id' => null, // Non-GC talks
            'speaker_name' => $data['speaker'],
            'speaker_title' => $data['speaker_title'] ?? null,
            'church_calling_id' => $calling?->id,
            'church_organization_id' => $organization['id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'] ?? null,
            'talk_date' => isset($data['date']) ? Carbon::parse($data['date']) : null,
            'url' => $url,
            'display_order' => $order,
        ]);

        return true;
    }

    private function findCalling(?string $prefix, ?string $organizationName): ?ChurchCalling
    {
        if (!$prefix || !$organizationName) {
            return null;
        }

        return $this->callings->first(function ($calling) use ($prefix, $organizationName) {
            return $calling->prefix === $prefix &&
                   $calling->organization &&
                   $calling->organization->name === $organizationName;
        });
    }
}
