<?php

namespace Database\Seeders;

use App\Models\GeneralConference;
use App\Models\GeneralConferenceSession;
use App\Models\GeneralConferenceSessionType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GeneralConferenceSeeder extends Seeder
{
    private array $sessionTypes = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->loadLookupData();
        $this->createAllConferences();
    }

    private function loadLookupData(): void
    {
        $this->sessionTypes = GeneralConferenceSessionType::all()->keyBy('slug')->toArray();
    }

    private function createAllConferences(): void
    {
        // Create conferences from 1971 to present
        // General Conference has been held in April and October since 1971
        $startYear = 1971;
        $endYear = (int) date('Y');
        $currentMonth = (int) date('n');

        for ($year = $startYear; $year <= $endYear; $year++) {
            // April Conference
            $aprilDate = $this->getConferenceDate($year, 'april');
            $this->createConference($year, 'april', $aprilDate);

            // October Conference (only if it has happened or is happening this year)
            if ($year < $endYear || $currentMonth >= 10) {
                $octoberDate = $this->getConferenceDate($year, 'october');
                $this->createConference($year, 'october', $octoberDate);
            }
        }
    }

    private function getConferenceDate(int $year, string $month): Carbon
    {
        // General Conference is typically the first weekend in April and October
        // Saturday is the first day
        $monthNum = $month === 'april' ? 4 : 10;
        $firstDay = Carbon::create($year, $monthNum, 1);

        // Find the first Saturday
        $daysUntilSaturday = (Carbon::SATURDAY - $firstDay->dayOfWeek + 7) % 7;
        return $firstDay->addDays($daysUntilSaturday);
    }

    private function createConference(int $year, string $month, Carbon $startDate): void
    {
        $monthTitle = ucfirst($month);
        $conference = GeneralConference::updateOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'name' => "{$monthTitle} {$year} General Conference",
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDay(),
            ]
        );

        $this->createSessions($conference, $startDate);
    }

    private function createSessions(GeneralConference $conference, Carbon $startDate): void
    {
        $sunday = $startDate->copy()->addDay();

        // Standard session structure (modern conferences)
        $sessions = [
            ['type' => 'saturday-morning', 'date' => $startDate, 'order' => 1],
            ['type' => 'saturday-afternoon', 'date' => $startDate, 'order' => 2],
            ['type' => 'priesthood', 'date' => $startDate, 'order' => 3],
            ['type' => 'sunday-morning', 'date' => $sunday, 'order' => 4],
            ['type' => 'sunday-afternoon', 'date' => $sunday, 'order' => 5],
        ];

        // Women's session was added in 2014 (replaced some priesthood sessions)
        if ($conference->year >= 2014) {
            // Women's session is typically the Saturday before conference in September/March
            // For simplicity, we'll add it with the same date
        }

        foreach ($sessions as $sessionData) {
            if (!isset($this->sessionTypes[$sessionData['type']])) {
                continue;
            }

            $typeId = $this->sessionTypes[$sessionData['type']]['id'];
            $typeName = $this->sessionTypes[$sessionData['type']]['name'];

            GeneralConferenceSession::updateOrCreate(
                [
                    'general_conference_id' => $conference->id,
                    'session_type_id' => $typeId,
                ],
                [
                    'name' => $typeName . ' Session',
                    'session_date' => $sessionData['date'],
                    'display_order' => $sessionData['order'],
                ]
            );
        }
    }

    /**
     * Helper method to generate the church URL for a talk
     */
    public static function generateTalkUrl(int $year, string $month, string $talkSlug): string
    {
        $monthNum = $month === 'april' ? '04' : '10';
        return "https://www.churchofjesuschrist.org/study/general-conference/{$year}/{$monthNum}/{$talkSlug}?lang=eng";
    }
}
