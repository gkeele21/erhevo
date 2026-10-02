<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One imported GEDCOM per user; re-importing replaces it.
        Schema::create('family_trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('root_fs_id', 20);
            $table->string('root_name');
            $table->string('source')->nullable();
            $table->unsignedInteger('people_count');
            $table->unsignedInteger('ancestor_count');
            $table->unsignedTinyInteger('generation_count');
            $table->timestamp('imported_at');
            $table->timestamps();
        });

        // Direct-line ancestors only (plus the root person, generation 0) —
        // collateral relatives are dropped at import. Everything here is
        // derived from the GEDCOM and rebuilt on every import.
        Schema::create('ancestors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fs_id', 20);
            $table->string('name');
            $table->string('given_name')->nullable();
            $table->string('surname')->nullable();
            $table->char('sex', 1)->nullable();
            $table->unsignedTinyInteger('generation');
            // Lowest Ahnentafel number (father = 2, mother = 3, …) when pedigree
            // collapse puts someone in the tree more than once.
            $table->unsignedBigInteger('ahnentafel');
            $table->string('father_fs_id', 20)->nullable();
            $table->string('mother_fs_id', 20)->nullable();
            $table->string('birth_date')->nullable();
            $table->smallInteger('birth_year')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('death_date')->nullable();
            $table->smallInteger('death_year')->nullable();
            $table->string('death_place')->nullable();
            $table->boolean('deceased')->default(false);
            $table->string('burial_date')->nullable();
            $table->string('burial_place')->nullable();
            $table->string('lds_baptism_date')->nullable();
            // Sortable form of the baptism date (YYYY-MM-DD, unknown parts as 00).
            $table->string('lds_baptism_sort', 10)->nullable();
            $table->boolean('baptized_while_living')->default(false);
            $table->boolean('lds_affiliation')->default(false);
            $table->boolean('likely_pioneer')->default(false);
            $table->boolean('has_church_places')->default(false);
            $table->json('pioneer_signals')->nullable();
            $table->json('church_places')->nullable();
            $table->json('events')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fs_id']);
            $table->index(['user_id', 'generation']);
        });

        // The user's own research, keyed by FamilySearch ID rather than
        // ancestors.id so it survives a re-import. Nullable overrides win over
        // what the GEDCOM says.
        Schema::create('ancestor_research', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fs_id', 20);
            $table->timestamp('researched_at')->nullable();
            $table->text('notes')->nullable();
            $table->date('lds_baptism_on')->nullable();
            $table->boolean('baptized_while_living')->nullable();
            $table->boolean('pioneer')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fs_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ancestor_research');
        Schema::dropIfExists('ancestors');
        Schema::dropIfExists('family_trees');
    }
};
