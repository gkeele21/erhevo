<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            // Redundant: the organization is derivable via church_calling → organization.
            $table->dropForeign(['church_organization_id']);
            $table->dropColumn('church_organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('talks', function (Blueprint $table) {
            $table->foreignId('church_organization_id')->nullable()->after('church_calling_id')
                ->constrained('church_organizations')->nullOnDelete();
        });
    }
};
