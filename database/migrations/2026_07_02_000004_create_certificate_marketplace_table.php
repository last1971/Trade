<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateMarketplaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_marketplace', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_id');
            $table->unsignedBigInteger('marketplace_id');
            $table->date('uploaded_at')->nullable();
            $table->timestamps();

            $table->unique(['certificate_id', 'marketplace_id']);

            $table->foreign('certificate_id')
                  ->references('id')
                  ->on('certificates')
                  ->onDelete('cascade');

            $table->foreign('marketplace_id')
                  ->references('id')
                  ->on('marketplaces')
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
        Schema::dropIfExists('certificate_marketplace');
    }
}
