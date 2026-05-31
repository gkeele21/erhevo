<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\ChurchCalling;
use App\Models\ChurchOrganization;
use Illuminate\Database\Seeder;

class ChurchOrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First Presidency
        $firstPres = ChurchOrganization::create([
            'name' => 'The First Presidency',
            'parent_id' => null,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($firstPres, 'President', 'President', Gender::Male);
        $this->createCalling($firstPres, '1st Counselor', 'President', Gender::Male);
        $this->createCalling($firstPres, '2nd Counselor', 'President', Gender::Male);

        // Quorum of the Twelve Apostles
        $quorum12 = ChurchOrganization::create([
            'name' => 'The Quorum of the Twelve Apostles',
            'parent_id' => $firstPres->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($quorum12, 'President', 'President', Gender::Male);
        $this->createCalling($quorum12, 'Acting President', 'President', Gender::Male);
        $this->createCalling($quorum12, 'Apostle', 'Elder', Gender::Male);

        // Presidency of the Seventy
        $seventyPres = ChurchOrganization::create([
            'name' => 'The Presidency of the Seventy',
            'parent_id' => $quorum12->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($seventyPres, '', 'Elder', Gender::Male);

        // Presiding Bishopric
        $presidingBishopric = ChurchOrganization::create([
            'name' => 'The Presiding Bishopric',
            'parent_id' => $seventyPres->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($presidingBishopric, 'Presiding Bishop', 'Bishop', Gender::Male);
        $this->createCalling($presidingBishopric, '1st Counselor', 'Bishop', Gender::Male);
        $this->createCalling($presidingBishopric, '2nd Counselor', 'Bishop', Gender::Male);

        // General Officers
        $genOfficers = ChurchOrganization::create([
            'name' => 'General Officers',
            'parent_id' => $quorum12->id,
        ]);

        // Primary General Presidency
        $primaryPres = ChurchOrganization::create([
            'name' => 'Primary General Presidency',
            'parent_id' => $genOfficers->id,
            'gender' => Gender::Female,
        ]);
        $this->createCalling($primaryPres, 'President', 'Sister', Gender::Female);
        $this->createCalling($primaryPres, '1st Counselor', 'Sister', Gender::Female);
        $this->createCalling($primaryPres, '2nd Counselor', 'Sister', Gender::Female);

        // Relief Society General Presidency
        $rsPres = ChurchOrganization::create([
            'name' => 'Relief Society General Presidency',
            'parent_id' => $genOfficers->id,
            'gender' => Gender::Female,
        ]);
        $this->createCalling($rsPres, 'President', 'Sister', Gender::Female);
        $this->createCalling($rsPres, '1st Counselor', 'Sister', Gender::Female);
        $this->createCalling($rsPres, '2nd Counselor', 'Sister', Gender::Female);

        // Sunday School General Presidency
        $ssPres = ChurchOrganization::create([
            'name' => 'Sunday School General Presidency',
            'parent_id' => $genOfficers->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($ssPres, 'President', 'Brother', Gender::Male);
        $this->createCalling($ssPres, '1st Counselor', 'Brother', Gender::Male);
        $this->createCalling($ssPres, '2nd Counselor', 'Brother', Gender::Male);

        // Young Women General Presidency
        $ywPres = ChurchOrganization::create([
            'name' => 'Young Women General Presidency',
            'parent_id' => $genOfficers->id,
            'gender' => Gender::Female,
        ]);
        $this->createCalling($ywPres, 'President', 'Sister', Gender::Female);
        $this->createCalling($ywPres, '1st Counselor', 'Sister', Gender::Female);
        $this->createCalling($ywPres, '2nd Counselor', 'Sister', Gender::Female);

        // Young Men General Presidency
        $ymPres = ChurchOrganization::create([
            'name' => 'Young Men General Presidency',
            'parent_id' => $genOfficers->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($ymPres, 'President', 'Brother', Gender::Male);
        $this->createCalling($ymPres, '1st Counselor', 'Brother', Gender::Male);
        $this->createCalling($ymPres, '2nd Counselor', 'Brother', Gender::Male);

        // General Authority Seventies
        $genAuthoritySeventies = ChurchOrganization::create([
            'name' => 'General Authority Seventies',
            'parent_id' => $quorum12->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($genAuthoritySeventies, 'General Authority Seventy', 'Elder', Gender::Male);

        // Area Seventies
        $areaSeventies = ChurchOrganization::create([
            'name' => 'Area Seventies',
            'parent_id' => $genAuthoritySeventies->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($areaSeventies, 'Area Seventy', 'Elder', Gender::Male);

        // Church Historian
        $historian = ChurchOrganization::create([
            'name' => 'Church Historian',
            'parent_id' => $quorum12->id,
            'gender' => Gender::Male,
        ]);
        $this->createCalling($historian, 'Church Historian and Recorder', 'Elder', Gender::Male);
        $this->createCalling($historian, 'Assistant Church Historian', 'Elder', Gender::Male);
    }

    private function createCalling(ChurchOrganization $org, string $name, string $prefix, Gender $gender): ChurchCalling
    {
        return ChurchCalling::create([
            'church_organization_id' => $org->id,
            'name' => $name,
            'prefix' => $prefix,
            'gender' => $gender,
        ]);
    }
}
