<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tnved', function (Blueprint $t) {
            $t->id();
            $t->string('code', 10)->unique();   // 10-значный код ТН ВЭД (строка — ведущие нули)
            $t->text('name');                    // наименование (хлебная крошка со всеми уровнями)
            $t->string('tariff', 16)->nullable(); // ставка пошлины: "0%", "5%"
            $t->char('heading', 4)->index();     // товарная позиция (первые 4 цифры) — отбор кандидатов
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tnved');
    }
};
