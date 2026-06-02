<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->default('')->after('id');
            $table->string('last_name')->default('')->after('first_name');
        });

        // Backfill from the existing single name column: first token becomes
        // first_name, the remainder becomes last_name.
        foreach (DB::table('users')->select('id', 'name')->get() as $row) {
            $name = trim((string) ($row->name ?? ''));

            DB::table('users')->where('id', $row->id)->update([
                'first_name' => $name === '' ? '' : Str::before($name, ' '),
                'last_name' => Str::contains($name, ' ') ? trim(Str::after($name, ' ')) : '',
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('')->after('id');
        });

        foreach (DB::table('users')->select('id', 'first_name', 'last_name')->get() as $row) {
            DB::table('users')->where('id', $row->id)->update([
                'name' => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
