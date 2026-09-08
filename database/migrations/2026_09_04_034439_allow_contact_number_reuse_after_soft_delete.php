<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['contact_number']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX customers_contact_number_unique ON customers (contact_number) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_contact_number_unique');

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('contact_number');
        });
    }
};
