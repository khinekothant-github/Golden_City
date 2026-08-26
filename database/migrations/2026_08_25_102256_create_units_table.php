<?php

use App\Enums\UnitStatus;
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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->foreignId('building_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type')->nullable();
            $table->text('room_description')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->string('status')->default(UnitStatus::Available->value)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // A unit belongs to a building OR hangs directly off a phase — never both, never neither.
        // Named CHECK constraints are PostgreSQL-only; the app layer also validates this rule,
        // so the schema-level guarantee stays where the production engine supports it.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE units ADD CONSTRAINT units_parent_check CHECK ((building_id IS NULL) <> (phase_id IS NULL))'
            );
        }

        DB::statement(
            'CREATE UNIQUE INDEX units_building_name_unique ON units (building_id, name) WHERE building_id IS NOT NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX units_phase_name_unique ON units (phase_id, name) WHERE phase_id IS NOT NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
