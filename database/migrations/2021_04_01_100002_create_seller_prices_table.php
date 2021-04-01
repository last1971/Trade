<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('seller_warehouse_id');
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->default(0);
            $table->decimal('value', 20, 10);
            $table->string('CharCode')->default('RUB');
            $table->boolean('is_input')->default(true);
            $table->timestamps();
            $table->foreign('CharCode')->references('CharCode')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_prices');
    }
}
