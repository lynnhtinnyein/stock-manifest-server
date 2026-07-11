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
        // newly added
        // attachment_has_gem, attachment_gem_price, attachment_gem_weight

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();

            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('price_in_thb', 12, 2)->default(0);
            $table->decimal('cargo_price_in_thb', 12, 2)->default(0);
            $table->decimal('cargo_price_in_mmk', 12, 2)->default(0);
            $table->decimal('selling_price_in_mmk', 12, 2)->default(0);
            $table->decimal('weight_in_grams', 10, 2)->default(0);

            $table->unsignedBigInteger('stock_in')->default(0);
            $table->unsignedBigInteger('stock_out')->default(0);

            $table->text('remark')->nullable();

            // create
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
