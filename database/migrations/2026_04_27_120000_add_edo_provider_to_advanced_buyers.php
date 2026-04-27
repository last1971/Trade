<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEdoProviderToAdvancedBuyers extends Migration
{
    public function up()
    {
        Schema::table('advanced_buyers', function (Blueprint $table) {
            $table->string('edo_provider', 20)->default('sbis')->after('edo_id');
        });
    }

    public function down()
    {
        Schema::table('advanced_buyers', function (Blueprint $table) {
            $table->dropColumn('edo_provider');
        });
    }
}
