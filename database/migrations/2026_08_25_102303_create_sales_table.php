<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('sale_person_id')->nullable()->index()->constrained('users')->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('commission_id')->nullable()->index()->constrained('commission_schemes')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->text('promotion_remark')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('payment_plan')->nullable();
            $table->string('process_type')->nullable()->index();
            $table->timestamp('sold_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
