<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
