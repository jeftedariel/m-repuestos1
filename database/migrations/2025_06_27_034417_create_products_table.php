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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')
                ->constrained()
                ->nullable(false)
                ->references('id')
                ->on('brands');
            $table->foreignId('category_id')
                ->constrained()
                ->nullable(false)
                ->references('id')
                ->on('categories');
            $table->string('code')
                ->nullable();
            $table->string('description', 255);
            $table->string('image')
                ->nullable();
            $table->decimal('purchasePrice', 10, 2);
            $table->decimal('salePrice', 10, 2);
            $table->decimal('profitMargin', 5, 2);
            $table->integer('quantity')
                ->default(0);
            $table->boolean('active')
                ->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};