<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Marking\GoodClassifyService;
use App\Services\Marking\TnvedSuggestService;
use App\TnvedSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TnvedSuggestController extends Controller
{
    /**
     * Список предложений на проверку (наполняет фоновая пачка).
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        return TnvedSuggestion::query()
            ->where('status', 'pending')
            ->orderBy('confidence')
            ->get();
    }

    /**
     * Статус фонового подбора — фронт поллит после «Подобрать пачкой».
     */
    public function status(): array
    {
        return [
            'running' => (bool) Cache::get(TnvedSuggestService::CACHE_RUNNING),
            'updated_at' => Cache::get(TnvedSuggestService::CACHE_UPDATED_AT),
            'count' => TnvedSuggestion::where('status', 'pending')->count(),
        ];
    }

    /**
     * Запуск подбора в фоне (долгий — ИИ по каждому товару). HTTP не ждёт конца;
     * повторный запуск отсекает Cache::add в команде.
     */
    public function run(Request $request): array
    {
        $data = $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
            'confidence' => 'nullable|integer|min:0|max:100',
        ]);
        $limit = (int) ($data['limit'] ?? 50);
        $confidence = (int) ($data['confidence'] ?? 80);

        if (!Cache::get(TnvedSuggestService::CACHE_RUNNING)) {
            exec('php ' . base_path('artisan')
                . ' tnved:suggest --limit=' . $limit . ' --confidence=' . $confidence
                . ' >> /tmp/tnved-suggest-web.log 2>&1 &');
        }

        return ['running' => true];
    }

    /**
     * Применить отмеченные предложения (с правками пользователя) — каждому свой
     * вердикт через GoodClassifyService::setVerdict, применённые удаляем. ИИ не
     * дёргается: пишем то, что уже подобрано и проверено человеком.
     *
     * @return array{applied: int, errors: array<int, array{GOODSCODE: int, message: string}>}
     */
    public function apply(Request $request, GoodClassifyService $service): array
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.GOODSCODE' => 'required|integer',
            'items.*.MARK_REQUIRED' => 'required|in:0,1',
            'items.*.TNVED' => 'nullable|string|max:10',
            'items.*.OKPD2' => 'nullable|string|max:12',
            'items.*.PRIM' => 'nullable|string|max:250',
        ]);

        $applied = 0;
        $errors = [];
        foreach ($data['items'] as $item) {
            $goodscode = intval($item['GOODSCODE']);
            try {
                $service->setVerdict(
                    $goodscode,
                    intval($item['MARK_REQUIRED']),
                    $item['TNVED'] ?? null,
                    $item['OKPD2'] ?? null,
                    $item['PRIM'] ?? null
                );
                TnvedSuggestion::where('goodscode', $goodscode)->delete();
                $applied++;
            } catch (\Exception $e) {
                $errors[] = ['GOODSCODE' => $goodscode, 'message' => $e->getMessage()];
            }
        }

        return ['applied' => $applied, 'errors' => $errors];
    }
}
