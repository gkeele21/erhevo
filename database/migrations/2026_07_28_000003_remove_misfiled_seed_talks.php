<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Early rows in the original talks seed snapshot (ids 2–39) were misfiled:
 * assigned to conferences they weren't given in. 17 duplicate correctly-filed
 * talks that exist elsewhere in the data; the rest don't match any real talk
 * in their claimed conference. Remove them everywhere (study_plan_items
 * cascade). Rows are matched by id + slug so a diverged database can never
 * lose a legitimate talk. The seed snapshot was regenerated without them.
 */
return new class extends Migration
{
    private const MISFILED = [
        2 => 'the-power-of-spiritual-momentum',
        3 => 'kingdoms-of-glory',
        5 => 'the-saviors-abiding-compassion',
        6 => 'in-the-strength-of-the-lord',
        7 => 'gods-plan-is-a-perfect-plan',
        8 => 'temples-designed-by-god',
        9 => 'the-priesthood-a-sacred-trust',
        10 => 'the-ministry-of-reconciliation',
        11 => 'the-sealings-of-the-lord',
        12 => 'hosanna-and-hallelujah',
        13 => 'yes-lord-i-will-follow-thee',
        15 => 'the-peace-of-christ-abolishes-enmity',
        16 => 'how-great-will-be-your-joy',
        17 => 'welcome-message',
        19 => 'steady-in-the-storms',
        21 => 'our-personal-savior',
        22 => 'seek-learning-by-faith',
        23 => 'priesthood-power',
        24 => 'living-a-christ-centered-life',
        25 => 'paths-to-the-celestial-kingdom',
        26 => 'the-gift-of-grace',
        27 => 'following-jesus-christ',
        28 => 'closing-remarks',
        30 => 'decisions-for-eternity',
        31 => 'the-message-the-meaning-and-the-multitude',
        32 => 'the-power-of-the-priesthood',
        33 => 'the-lords-promise-of-peace',
        34 => 'following-jesus-christ-is-our-sacred-duty',
        36 => 'jesus-christ-is-the-strength-of-youth',
        37 => 'keys-to-spiritual-momentum',
        38 => 'the-keys-and-authority-of-the-priesthood',
        39 => 'a-spiritual-foundation',
    ];

    public function up(): void
    {
        DB::table('talks')->where(function ($query) {
            foreach (self::MISFILED as $id => $slug) {
                $query->orWhere(fn ($q) => $q->where('id', $id)->where('slug', $slug));
            }
        })->delete();
    }

    public function down(): void
    {
        // Data removal is intentional; the rows were invalid.
    }
};
