<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_good_id')->constrained();
            $table->unsignedInteger('quantity')->default(0);
            $table->integer('additional_delivery_time')->default(0);
            $table->unsignedInteger('multiplicity')->default(1);
            $table->string('remark', 400)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_warehouses');
    }
}
