<?php

namespace Tests\Unit;

use App\Console\Commands\ImportTemples;
use PHPUnit\Framework\TestCase;

class ImportTemplesDateTest extends TestCase
{
    public function test_single_day_dedications_parse(): void
    {
        $this->assertSame('1987-02-14', ImportTemples::parseDedicationDate('14 February 1987 by James E. Faust'));
        $this->assertSame('1893-04-06', ImportTemples::parseDedicationDate('April 6, 1893'));
    }

    public function test_ranges_collapse_to_the_first_day(): void
    {
        $this->assertSame('1984-05-25', ImportTemples::parseDedicationDate('25–30 May 1984 by Gordon B. Hinckley'));
        $this->assertSame('1999-08-11', ImportTemples::parseDedicationDate('11-14 August 1999'));
    }

    public function test_ranges_spanning_months_take_the_first_day(): void
    {
        $this->assertSame('1993-04-30', ImportTemples::parseDedicationDate('30 April–1 May 1993'));
    }

    public function test_parenthetical_suffixes_are_ignored(): void
    {
        $this->assertSame('1987-02-14', ImportTemples::parseDedicationDate('14 February 1987 by James E. Faust (addition only)'));
    }

    public function test_unparseable_text_returns_null(): void
    {
        $this->assertNull(ImportTemples::parseDedicationDate('to be announced'));
    }
}
