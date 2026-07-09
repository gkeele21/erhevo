<?php

namespace App\Console\Commands;

use App\Models\ChurchCalling;
use App\Models\Talk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportPndrRoles extends Command
{
    protected $signature = 'talks:import-pndr-roles
                            {--all : Re-process talks that already have a calling}
                            {--recheck-calling= : Re-process only talks currently set to this church_calling_id}
                            {--sleep=0.5 : Seconds to pause between requests}
                            {--limit=0 : Only process N talks (0 = no limit)}';

    protected $description = 'Set each talk\'s church_calling_id (calling held when given) from the authorRole on its pndr.me talk page.';

    private const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    private array $lookup = [];

    public function handle(): int
    {
        ChurchCalling::with('organization')->get()->each(function (ChurchCalling $c) {
            $this->lookup[$this->key($c->organization?->name ?? '', $c->name ?? '')] = $c->id;
        });

        $recheck = $this->option('recheck-calling');
        $query = Talk::whereNotNull('url')
            ->when($recheck, fn ($q) => $q->where('church_calling_id', $recheck))
            ->when(! $recheck && ! $this->option('all'), fn ($q) => $q->whereNull('church_calling_id'));
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $usleep = (int) round(((float) $this->option('sleep')) * 1_000_000);
        $mapped = 0;
        $failed = 0;
        $unmapped = [];
        $processed = 0;

        foreach ($query->cursor() as $talk) {
            $slug = basename(parse_url($talk->url, PHP_URL_PATH) ?: '');
            if ($slug === '') {
                continue;
            }
            $processed++;

            $response = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)
                ->get("https://www.pndr.me/talk/{$slug}?lang=en");

            if (! $response->successful()) {
                $failed++;
            } else {
                // authorRole is embedded in backslash-escaped streamed JSON.
                $body = str_replace('\\"', '"', $response->body());
                if (preg_match('/"authorRole":"([^"]*)"/', $body, $m) && trim($m[1]) !== '') {
                    $role = $m[1];
                    $callingId = $this->resolve($role);
                    if ($callingId) {
                        $talk->update(['church_calling_id' => $callingId]);
                        $mapped++;
                    } else {
                        $unmapped[$role] = ($unmapped[$role] ?? 0) + 1;
                    }
                }
            }

            if ($usleep > 0) {
                usleep($usleep);
            }
            if ($processed % 100 === 0) {
                $this->line("  …{$processed} processed, {$mapped} mapped");
            }
        }

        $this->info("Processed {$processed}; set calling on {$mapped}; fetch failures {$failed}.");
        if ($unmapped) {
            arsort($unmapped);
            $this->warn(count($unmapped) . ' unmapped role(s):');
            foreach (array_slice($unmapped, 0, 25, true) as $role => $n) {
                $this->line("  {$role} ({$n})");
            }
        }

        return self::SUCCESS;
    }

    /** Map a pndr authorRole string to a ChurchCalling id, or null. */
    private function resolve(string $role): ?int
    {
        $r = mb_strtolower($role);
        $fp = 'The First Presidency';
        $twelve = 'The Quorum of the Twelve Apostles';
        // "Council of the Twelve" is the historical name for the Quorum of the Twelve.
        $inTwelve = str_contains($r, 'quorum of the twelve') || str_contains($r, 'council of the twelve');

        $map = match (true) {
            str_contains($r, 'first counselor') && str_contains($r, 'first presidency') => [$fp, '1st Counselor'],
            str_contains($r, 'second counselor') && str_contains($r, 'first presidency') => [$fp, '2nd Counselor'],
            str_contains($r, 'counselor') && str_contains($r, 'first presidency') => [$fp, 'Counselor'],
            str_contains($r, 'president of the church') => [$fp, 'President'],
            $inTwelve && str_contains($r, 'assistant') => [$twelve, 'Assistant to the Twelve'],
            $inTwelve && str_contains($r, 'acting president') => [$twelve, 'Acting President'],
            $inTwelve && str_contains($r, 'president') => [$twelve, 'President'],
            $inTwelve => [$twelve, 'Apostle'],
            str_contains($r, 'presidency of the seventy') => ['The Presidency of the Seventy', ''],
            str_contains($r, 'area seventy') => ['Area Seventies', 'Area Seventy'],
            str_contains($r, 'seventy') => ['General Authority Seventies', 'General Authority Seventy'],
            str_contains($r, 'presiding bishopric') && str_contains($r, 'first counselor') => ['The Presiding Bishopric', '1st Counselor'],
            str_contains($r, 'presiding bishopric') && str_contains($r, 'second counselor') => ['The Presiding Bishopric', '2nd Counselor'],
            str_contains($r, 'presiding bishop') => ['The Presiding Bishopric', 'Presiding Bishop'],
            default => $this->auxiliary($r),
        };

        return $map ? ($this->lookup[$this->key($map[0], $map[1])] ?? null) : null;
    }

    private function auxiliary(string $r): ?array
    {
        $orgs = [
            'relief society' => 'Relief Society General Presidency',
            'young women' => 'Young Women General Presidency',
            'young men' => 'Young Men General Presidency',
            'primary' => 'Primary General Presidency',
            'sunday school' => 'Sunday School General Presidency',
        ];
        foreach ($orgs as $kw => $org) {
            if (str_contains($r, $kw)) {
                return match (true) {
                    str_contains($r, 'first counselor') => [$org, '1st Counselor'],
                    str_contains($r, 'second counselor') => [$org, '2nd Counselor'],
                    default => [$org, 'President'],
                };
            }
        }

        return null;
    }

    private function key(string $org, string $name): string
    {
        return mb_strtolower($org) . '|' . mb_strtolower($name);
    }
}
