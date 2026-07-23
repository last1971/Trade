<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Стейджинг авто-подбора ТН ВЭД для страницы-ревью: фоновая пачка складывает
 * сюда предложения (status=pending), человек проверяет/правит и применяет —
 * применённые удаляются. Веб-сущность, живёт в MySQL (не в Firebird).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tnved_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('goodscode')->unique();
            $table->string('name', 500)->nullable();
            $table->string('tnved', 10)->nullable();
            $table->text('tnved_name')->nullable();
            $table->unsignedTinyInteger('mark_required')->nullable();
            $table->string('okpd2', 12)->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('model')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tnved_suggestions');
    }
};
