<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerOrderLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_order_id');
            $table->string('item_id');
            $table->string('item_name');
            $table->integer('qty');
            $table->decimal('price', 10, 2);
            $table->json('price_data')->nullable();
            $table->timestamps();

            $table->foreign('seller_order_id')
                  ->references('id')
                  ->on('seller_orders')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_order_lines');
    }
}
