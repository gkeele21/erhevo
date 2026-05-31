<?php

namespace App\Console\Commands;

use App\Models\ChurchCalling;
use App\Models\ChurchOrganization;
use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\Source;
use App\Models\Talk;
use App\Models\TalkType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ImportGeneralConferenceTalks extends Command
{
    protected $signature = 'gc:import-talks {file? : Path to JSON file with talk data}';

    protected $description = 'Import General Conference talks from a JSON file';

    private Collection $callings;
    private Collection $organizations;
    private ?Source $source = null;
    private ?TalkType $talkType = null;

    public function handle(): int
    {
        $file = $this->argument('file') ?? database_path('data/general_conference_talks.json');

        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $data = json_decode(File::get($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $this->loadLookupData();

        if (!$this->source) {
            $this->error('Source "general-conference" not found. Run SourceSeeder first.');
            return Command::FAILURE;
        }

        $totalTalks = 0;
        $newTalks = 0;

        foreach ($data as $year => $months) {
            foreach ($months as $month => $sessions) {
                $conference = GeneralConference::where('year', $year)
                    ->where('month', $month)
                    ->first();

                if (!$conference) {
                    $this->warn("Conference not found: {$month} {$year}");
                    continue;
                }

                foreach ($sessions as $sessionSlug => $talks) {
                    $session = $conference->sessions()
                        ->whereHas('sessionType', function ($q) use ($sessionSlug) {
                            $q->where('slug', $sessionSlug);
                        })
                        ->first();

                    if (!$session) {
                        $this->warn("Session not found: {$sessionSlug} for {$month} {$year}");
                        continue;
                    }

                    foreach ($talks as $order => $talkData) {
                        $totalTalks++;

                        $created = $this->importTalk($session, $talkData, $order + 1, $year, $month);
                        if ($created) {
                            $newTalks++;
                        }
                    }
                }
            }
        }

        $this->info("Import complete: {$newTalks} new talks imported ({$totalTalks} total processed)");

        return Command::SUCCESS;
    }

    private function loadLookupData(): void
    {
        $this->callings = ChurchCalling::with('organization')->get();
        $this->organizations = ChurchOrganization::all()->keyBy('name');
        $this->source = Source::where('slug', 'general-conference')->first();
        $this->talkType = TalkType::where('slug', 'conference-talk')->first();
    }

    private function importTalk(GeneralConferenceSession $session, array $data, int $order, int $year, string $month): bool
    {
        $existing = Talk::where('source_id', $this->source->id)
            ->where('slug', $data['slug'])
            ->first();

        if ($existing) {
            return false;
        }

        $calling = $this->findCalling($data['calling_prefix'] ?? null, $data['organization'] ?? null);
        $organizationName = $data['organization'] ?? null;
        $organization = $organizationName ? ($this->organizations[$organizationName] ?? null) : null;

        $monthNum = $month === 'april' ? '04' : '10';
        $url = "https://www.churchofjesuschrist.org/study/general-conference/{$year}/{$monthNum}/{$data['slug']}?lang=eng";

        Talk::create([
            'source_id' => $this->source->id,
            'talk_type_id' => $this->talkType?->id,
            'general_conference_session_id' => $session->id,
            'speaker_name' => $data['speaker'],
            'church_calling_id' => $calling?->id,
            'church_organization_id' => $organization['id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'] ?? null,
            'talk_date' => $session->session_date,
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
