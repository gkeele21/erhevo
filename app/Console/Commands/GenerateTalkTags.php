<?php

namespace App\Console\Commands;

use App\AI\AiManager;
use App\Models\Tag;
use App\Models\Talk;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateTalkTags extends Command
{
    /**
     * Every talk here is a General Conference talk by a church leader, so
     * these say nothing about the individual talk.
     */
    private const BLOCKED_TAGS = [
        'general-conference', 'conference', 'church', 'talk', 'religion',
        'religious', 'mormon', 'lds', 'sermon', 'religious-gathering',
        'latter-day-saints', 'gospel',
    ];

    protected $signature = 'talks:generate-tags
        {--user= : Id or email of the user whose AI connection to use (defaults to the first admin, then user 1)}
        {--limit=0 : Only process this many talks (0 = all)}
        {--force : Regenerate tags for talks that already have them}
        {--delay=200 : Milliseconds to wait between AI requests}';

    protected $description = 'Generate tags for talks using the given user\'s connected AI provider';

    public function handle(AiManager $ai): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('User not found.');

            return Command::FAILURE;
        }

        if (! $ai->isConnected($user)) {
            $this->error("{$user->email} has no AI connection configured (Profile → AI Connection).");

            return Command::FAILURE;
        }

        $service = $ai->serviceFor($user);

        $query = Talk::with('calling')
            ->when(! $this->option('force'), fn ($q) => $q->whereDoesntHave('tags'))
            ->orderBy('id');

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $talks = $query->get();

        if ($talks->isEmpty()) {
            $this->info('No talks need tags.');

            return Command::SUCCESS;
        }

        $this->info("Generating tags for {$talks->count()} talks using {$user->email}'s {$user->ai_provider} connection…");

        $tagged = 0;
        $skipped = 0;
        $failed = 0;
        $delay = max(0, (int) $this->option('delay')) * 1000;

        $bar = $this->output->createProgressBar($talks->count());

        foreach ($talks as $talk) {
            try {
                $names = array_values(array_filter(
                    $service->suggestTags($this->talkContext($talk), 5),
                    fn ($name) => ! in_array(Str::slug($name), self::BLOCKED_TAGS, true)
                ));

                if (empty($names)) {
                    $skipped++;
                } else {
                    $tagIds = collect($names)
                        ->map(fn ($name) => Tag::findOrCreateByName(trim($name))->id)
                        ->unique()
                        ->all();
                    $talk->tags()->syncWithoutDetaching($tagIds);
                    $tagged++;
                }
            } catch (\Throwable $e) {
                $failed++;

                // A failing key or exhausted quota fails everything — bail
                // early instead of burning through the whole list.
                if ($failed >= 5 && $tagged === 0) {
                    $this->newLine();
                    $this->error("Aborting after {$failed} consecutive failures: {$e->getMessage()}");

                    return Command::FAILURE;
                }
            }

            $bar->advance();
            usleep($delay);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done: {$tagged} talks tagged, {$skipped} got no suggestions, {$failed} failed.");

        return Command::SUCCESS;
    }

    /**
     * Talks store no body text, so tag from the metadata we have: title,
     * speaker, calling, date, and the excerpt.
     */
    protected function talkContext(Talk $talk): string
    {
        $parts = array_filter([
            'Suggest topical and doctrinal tags (e.g. faith, repentance, family, temples). '
                . 'Every item is a General Conference talk, so never suggest generic tags like '
                . '"general conference", "church", "gospel", or "religion".',
            "General Conference talk: \"{$talk->title}\"",
            $talk->speaker_name ? "Speaker: {$talk->speaker_name}" : null,
            $talk->calling?->name ? "Calling: {$talk->calling->name}" : null,
            $talk->talk_date ? 'Given: ' . $talk->talk_date->format('F Y') : null,
            $talk->summary ? "Excerpt: {$talk->summary}" : null,
        ]);

        return implode("\n", $parts);
    }

    protected function resolveUser(): ?User
    {
        if ($option = $this->option('user')) {
            return is_numeric($option)
                ? User::find($option)
                : User::where('email', $option)->first();
        }

        return User::where('is_admin', true)->orderBy('id')->first() ?? User::find(1);
    }
}
