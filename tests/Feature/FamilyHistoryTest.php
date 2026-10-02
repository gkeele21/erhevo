<?php

namespace Tests\Feature;

use App\Models\Ancestor;
use App\Models\User;
use App\Services\FamilyHistory\ChurchHistory;
use App\Services\FamilyHistory\FamilyTreeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FamilyHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): string
    {
        return base_path('tests/fixtures/family.ged');
    }

    private function importFor(User $user): void
    {
        app(FamilyTreeImporter::class)->import($user, $this->fixture());
    }

    private function upload(string $name = 'family.ged'): UploadedFile
    {
        return new UploadedFile($this->fixture(), $name, null, null, true);
    }

    public static function sectionPages(): array
    {
        return [
            'list' => ['/family-history'],
            'import' => ['/family-history/import'],
            'pedigree' => ['/family-history/pedigree'],
            'baptized' => ['/family-history/baptized'],
            'pioneers' => ['/family-history/pioneers'],
            'church sites' => ['/family-history/church-sites'],
        ];
    }

    /** @dataProvider sectionPages */
    public function test_guests_are_redirected_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    /** @dataProvider sectionPages */
    public function test_users_with_lds_content_disabled_are_forbidden(string $uri): void
    {
        $user = User::factory()->create();
        $user->setSetting('show_lds_content', false)->save();

        $this->actingAs($user)->get($uri)->assertForbidden();
    }

    public function test_without_a_tree_the_list_sends_you_to_import(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/family-history')
            ->assertRedirect(route('family-history.import'));
    }

    public function test_import_keeps_only_the_direct_line_with_preferred_parents(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/family-history/import', ['file' => $this->upload()])
            ->assertRedirect(route('family-history.index'));

        $this->assertSame(
            ['AAAA-001', 'AAAA-002', 'AAAA-003', 'AAAA-005', 'AAAA-007'],
            $user->ancestors()->orderBy('ahnentafel')->pluck('fs_id')->all(),
        );
        // Sibling (living) and the adoptive father are not kept.
        $this->assertDatabaseMissing('ancestors', ['fs_id' => 'AAAA-004']);
        $this->assertDatabaseMissing('ancestors', ['fs_id' => 'AAAA-006']);

        $tree = $user->familyTree;
        $this->assertSame('Test Person', $tree->root_name);
        $this->assertSame(4, $tree->ancestor_count);
        $this->assertSame(2, $tree->generation_count);
        $this->assertSame(7, $tree->people_count);
    }

    public function test_import_works_out_relationships_and_church_history_clues(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);

        $father = $user->ancestors()->where('fs_id', 'AAAA-002')->first();
        $this->assertSame('Father', $father->relationship());
        $this->assertTrue($father->baptized_while_living);
        $this->assertSame('8 MAR 1958', $father->lds_baptism_date);

        $ezra = $user->ancestors()->where('fs_id', 'AAAA-005')->first();
        $this->assertSame('Grandfather', $ezra->relationship());
        $this->assertSame('father', $ezra->side());
        $this->assertSame(4, $ezra->ahnentafel);
        $this->assertSame('Provo City Cemetery, Provo, Utah', $ezra->burial_place);
        // The hand-entered LdsBaptism fact is after death — a proxy baptism.
        $this->assertSame('14 JUN 2000', $ezra->lds_baptism_date);
        $this->assertFalse($ezra->baptized_while_living);
        $this->assertSame(['nauvoo'], array_column($ezra->church_places, 'key'));
        $this->assertTrue($ezra->likely_pioneer);
        $this->assertContains('Arrived with Daniel A. Miller/John W. Cooley Company (1853)', $ezra->pioneer_signals);

        $this->assertSame('Mother', $user->ancestors()->where('fs_id', 'AAAA-003')->first()->relationship());
        $this->assertNull($user->ancestors()->where('fs_id', 'AAAA-003')->first()->side());
    }

    public function test_relationship_labels_count_greats(): void
    {
        $ancestor = new Ancestor(['generation' => 5, 'sex' => 'F', 'ahnentafel' => 33]);
        $this->assertSame('3rd great-grandmother', $ancestor->relationship());

        $ancestor->generation = 3;
        $this->assertSame('Great-grandmother', $ancestor->relationship());
    }

    public function test_reimport_keeps_research(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);

        $this->actingAs($user)->put('/family-history/ancestors/AAAA-005/research', [
            'researched' => true,
            'notes' => 'Crossed the plains in 1853.',
            'lds_baptism_on' => '1842-05-01',
            'baptized_while_living' => true,
            'pioneer' => null,
        ])->assertRedirect();

        $this->importFor($user);

        $this->actingAs($user)->get('/family-history/ancestors/AAAA-005')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/Show')
                ->where('research.researched', true)
                ->where('research.notes', 'Crossed the plains in 1853.')
                ->where('ancestor.baptized_while_living', true)
                ->where('ancestor.pioneer', true));
    }

    public function test_research_overrides_drive_the_list_filters_and_first_baptized(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);

        // Before: only the father counts as baptized while living.
        $this->actingAs($user)->get('/family-history?filter=baptized')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/Index')
                ->has('ancestors.data', 1)
                ->where('ancestors.data.0.fs_id', 'AAAA-002')
                ->where('firstBaptized.fs_id', 'AAAA-002')
                ->where('stats.total', 4)
                ->where('stats.pioneers', 1));

        $this->actingAs($user)->put('/family-history/ancestors/AAAA-005/research', [
            'researched' => true,
            'lds_baptism_on' => '1842-05-01',
            'baptized_while_living' => true,
        ]);

        $this->actingAs($user)->get('/family-history?filter=baptized&sort=baptism')
            ->assertInertia(fn (Assert $page) => $page
                ->has('ancestors.data', 2)
                ->where('firstBaptized.fs_id', 'AAAA-005')
                ->where('stats.researched', 1));

        $this->actingAs($user)->get('/family-history?filter=unresearched')
            ->assertInertia(fn (Assert $page) => $page->has('ancestors.data', 3));
    }

    public function test_baptized_page_sorts_by_baptism_and_birth(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);
        // Ezra's file baptism is a proxy; the user found his own in 1842.
        $user->ancestorResearch()->create(['fs_id' => 'AAAA-005', 'lds_baptism_on' => '1842-05-01', 'baptized_while_living' => true]);

        $this->actingAs($user)->get('/family-history/baptized')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/Baptized')
                ->where('sort', 'baptism')
                ->has('people', 2)
                ->where('people.0.fs_id', 'AAAA-005')
                ->where('people.0.age_at_baptism', ['years' => 21, 'approximate' => false])
                ->where('people.1.fs_id', 'AAAA-002')
                // Born "1950", so the age is approximate.
                ->where('people.1.age_at_baptism', ['years' => 8, 'approximate' => true])
                ->missing('people.0.sort_keys'));

        $this->actingAs($user)->get('/family-history/baptized?sort=birth&direction=desc')
            ->assertInertia(fn (Assert $page) => $page
                ->where('people.0.fs_id', 'AAAA-002')
                ->where('people.1.fs_id', 'AAAA-005'));
    }

    public function test_pioneers_page_shows_arrival_and_clues(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);
        // Hannah has no clues, but the user knows she crossed too.
        $user->ancestorResearch()->create(['fs_id' => 'AAAA-007', 'pioneer' => true]);

        $this->actingAs($user)->get('/family-history/pioneers')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/Pioneers')
                ->has('people', 2)
                ->where('people.0.fs_id', 'AAAA-005')
                ->where('people.0.arrival', ['year' => 1853, 'company' => 'Daniel A. Miller/John W. Cooley Company'])
                ->where('people.0.confirmed', false)
                // No arrival known, so she sorts last.
                ->where('people.1.fs_id', 'AAAA-007')
                ->where('people.1.arrival', null)
                ->where('people.1.confirmed', true));
    }

    public static function churchSitePlaces(): array
    {
        return [
            'Harmony in Susquehanna County' => ['Harmony, Susquehanna, Pennsylvania, United States', 1828, 'harmony', 'in'],
            'Colesville is near Harmony' => ['Colesville, Broome, New York, United States', 1830, 'harmony', 'near'],
            'a different Harmony, PA' => ['Harmony, Butler, Pennsylvania, United States', 1828, null, null],
            'New Harmony, Indiana' => ['New Harmony, Posey, Indiana, United States', 1828, null, null],
            'Independence, Missouri' => ['Independence, Jackson, Missouri, United States', 1832, 'independence', 'in'],
            'Clay County is near Independence' => ['Liberty, Clay, Missouri, United States', 1835, 'independence', 'near'],
            'Independence, Iowa' => ['Independence, Buchanan, Iowa, United States', 1832, null, null],
            'Kirtland while still in Geauga' => ['Kirtland Township, Geauga, Ohio, United States', 1835, 'kirtland', 'in'],
            'Kirtland, New Mexico' => ['Kirtland, San Juan, New Mexico, United States', 1835, null, null],
            'Palmyra' => ['Palmyra, Palmyra, Wayne, New York, United States', 1820, 'palmyra', 'in'],
            'Manchester, the Smith farm' => ['Manchester, Ontario, New York, United States', 1825, 'palmyra', 'in'],
            'Ontario, Canada' => ['Toronto, Ontario, Canada', 1825, null, null],
            'Far West' => ['Far West, Caldwell, Missouri, United States', 1838, 'far_west', 'in'],
            'Carthage is near Nauvoo' => ['Carthage, Hancock, Illinois, United States', 1844, 'nauvoo', 'near'],
            'Montrose, across the river' => ['Montrose, Lee, Iowa, United States', 1841, 'nauvoo', 'near'],
            'Nauvoo after the Saints left' => ['Nauvoo, Hancock, Illinois, United States', 1900, null, null],
        ];
    }

    /** @dataProvider churchSitePlaces */
    public function test_church_sites_match_the_right_town_and_county(string $place, int $year, ?string $key, ?string $proximity): void
    {
        $found = app(ChurchHistory::class)->places([['year' => $year, 'date' => (string) $year, 'place' => $place]]);

        $this->assertSame($key, $found[0]['key'] ?? null);
        $this->assertSame($proximity, $found[0]['proximity'] ?? null);
    }

    public function test_church_sites_page_groups_ancestors_by_site(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);

        $this->actingAs($user)->get('/family-history/church-sites')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/ChurchSites')
                ->has('sites', count(ChurchHistory::PLACES))
                ->where('sites.0.key', 'palmyra')
                ->has('sites.0.people', 0)
                ->where('sites.5.key', 'nauvoo')
                ->has('sites.5.people', 1)
                ->where('sites.5.people.0.fs_id', 'AAAA-005')
                ->where('sites.5.people.0.proximity', 'in')
                ->where('sites.5.people.0.from', 1843));
    }

    public function test_refresh_command_recomputes_clues_from_stored_events(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);
        // As if imported under older rules that knew nothing about Nauvoo.
        $user->ancestors()->update(['church_places' => json_encode([]), 'has_church_places' => false]);

        $this->artisan('family-history:refresh-clues')->assertSuccessful();

        $ezra = $user->ancestors()->where('fs_id', 'AAAA-005')->first();
        $this->assertTrue($ezra->has_church_places);
        $this->assertSame('nauvoo', $ezra->church_places[0]['key']);
    }

    public function test_surprise_me_picks_someone_not_yet_researched(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);
        foreach (['AAAA-002', 'AAAA-003', 'AAAA-005'] as $fsId) {
            $user->ancestorResearch()->create(['fs_id' => $fsId, 'researched_at' => now()]);
        }

        $this->actingAs($user)->get('/family-history/random')
            ->assertRedirect(route('family-history.show', 'AAAA-007'));
    }

    public function test_pedigree_lays_out_parents_by_slot(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);

        $this->actingAs($user)->get('/family-history/pedigree')
            ->assertInertia(fn (Assert $page) => $page
                ->component('FamilyHistory/Pedigree')
                ->where('slots.1.fs_id', 'AAAA-001')
                ->where('slots.2.fs_id', 'AAAA-002')
                ->where('slots.3.fs_id', 'AAAA-003')
                ->where('slots.4.fs_id', 'AAAA-005')
                ->where('slots.5.fs_id', 'AAAA-007')
                ->missing('slots.6'));
    }

    public function test_ancestors_are_private_to_their_user(): void
    {
        $owner = User::factory()->create();
        $this->importFor($owner);

        $this->actingAs(User::factory()->create())
            ->get('/family-history/ancestors/AAAA-002')
            ->assertNotFound();
    }

    public function test_rejects_files_that_are_not_gedcom(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/family-history/import', ['file' => UploadedFile::fake()->createWithContent('notes.txt', 'hello')])
            ->assertSessionHasErrors('file');

        $this->actingAs($user)
            ->post('/family-history/import', ['file' => UploadedFile::fake()->createWithContent('fake.ged', "just text\n")])
            ->assertSessionHasErrors('file');

        $this->actingAs($user)
            ->post('/family-history/import', ['file' => $this->upload(), 'root_fs_id' => 'ZZZZ-999'])
            ->assertSessionHasErrors('file');

        $this->assertNull($user->fresh()->familyTree);
    }

    public function test_deleting_the_tree_removes_research_too(): void
    {
        $user = User::factory()->create();
        $this->importFor($user);
        $user->ancestorResearch()->create(['fs_id' => 'AAAA-002', 'notes' => 'x']);

        $this->actingAs($user)->delete('/family-history')->assertRedirect(route('family-history.import'));

        $this->assertSame(0, $user->ancestors()->count());
        $this->assertSame(0, $user->ancestorResearch()->count());
        $this->assertNull($user->fresh()->familyTree);
    }
}
