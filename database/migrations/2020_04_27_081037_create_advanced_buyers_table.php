<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedBuyersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advanced_buyers', function (Blueprint $table) {
            $table->id();
            $table->integer('buyer_id')->unique();
            $table->string('edo_id')->unique();
            $table->string('consignee')->nullable();
            $table->string('consigneeAddress')->nullable();
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
        Schema::dropIfExists('advanced_buyers');
    }
}
