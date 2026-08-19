<?php

namespace Tests\Unit;

use App\Console\Commands\ImportTemples;
use PHPUnit\Framework\TestCase;

class ImportTemplesNameTest extends TestCase
{
    public function test_the_name_comes_from_the_title_not_the_site_banner(): void
    {
        $html = '<title>Bern Switzerland Temple | ChurchofJesusChristTemples.org</title>'
            .'<h1 class="title">Temples of The Church of Jesus Christ of Latter-day Saints</h1>';

        $this->assertSame('Bern Switzerland Temple', ImportTemples::parseName($html));
    }

    public function test_entities_are_decoded(): void
    {
        $html = '<title>C&oacute;rdoba Argentina Temple | ChurchofJesusChristTemples.org</title>';

        $this->assertSame('Córdoba Argentina Temple', ImportTemples::parseName($html));
    }

    public function test_a_title_without_the_site_suffix_still_works(): void
    {
        $this->assertSame('Salt Lake Temple', ImportTemples::parseName('<title>Salt Lake Temple</title>'));
    }

    public function test_the_site_banner_alone_is_rejected(): void
    {
        $html = '<title>Temples of The Church of Jesus Christ of Latter-day Saints</title>';

        $this->assertNull(ImportTemples::parseName($html));
    }

    public function test_a_missing_title_returns_null(): void
    {
        $this->assertNull(ImportTemples::parseName('<h1 class="title">Bern Switzerland Temple</h1>'));
    }
}
