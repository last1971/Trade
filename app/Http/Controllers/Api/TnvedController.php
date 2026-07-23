<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Marking\MarkingDictService;
use App\Services\Tnved\TnvedMatchService;
use Illuminate\Http\Request;

class TnvedController extends Controller
{
    /**
     * Подбор кода ТН ВЭД по описанию товара (кнопка «подобрать» на Нац Каталоге).
     */
    public function match(Request $request, TnvedMatchService $service): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:1000',
            'category' => 'nullable|string|max:500',
            'case' => 'nullable|string|max:500',
            'maker' => 'nullable|string|max:500',
        ]);

        return $service->match(
            $data['name'],
            $data['category'] ?? null,
            $data['case'] ?? null,
            $data['maker'] ?? null,
        );
    }

    /**
     * Расшифровка кода ТН ВЭД: наименование, тариф, подлежит ли маркировке и
     * варианты ОКПД2. Одна точка «что это за код» для карточки, ревью и
     * массового разбора (склейка TnvedMatchService::resolve + MarkingDictService).
     */
    public function show(string $code, TnvedMatchService $service, MarkingDictService $marking): array
    {
        $resolved = $service->resolve($code);

        return [
            'code' => $code,
            'found' => $resolved !== null,
            'name' => $resolved['name'] ?? null,
            'tariff' => $resolved['tariff'] ?? null,
            'mark_required' => $marking->isMarkRequired($code),
            'okpd2' => $marking->okpd2Options($code),
        ];
    }

    /**
     * Справочник маркируемых ТН ВЭД → ОКПД2 для дропдауна на фронте
     * (заменяет бандленный tnvedMarking.json — источник один, на бэке).
     */
    public function markingDict(MarkingDictService $marking): array
    {
        return $marking->all();
    }
}
