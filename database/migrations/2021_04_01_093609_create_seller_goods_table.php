<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerGoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_goods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('search_name');
            $table->string('producer')->nullable();
            $table->string('case')->nullable();
            $table->string('code');
            $table->integer('seller_id');
            $table->integer('good_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('basic_delivery_time');
            $table->string('remark')->nullable();
            $table->unsignedInteger('package_quantity')->default(1);
            $table->timestamps();
            $table->unique(['seller_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_goods');
    }
}
