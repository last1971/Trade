<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advanced_sellers', function (Blueprint $table) {
            $table->id();
            $table->integer('seller_id')->unique();
            $table->string('edo_id')->nullable();
            $table->string('alias')->unique();
            $table->boolean('syncOrder')->default(false);
            $table->string('times');
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
        Schema::dropIfExists('advanced_sellers');
    }
}
