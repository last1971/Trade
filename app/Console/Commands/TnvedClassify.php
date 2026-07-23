<?php

namespace App\Console\Commands;

use App\Services\Marking\AutoClassifyService;
use App\Services\Marking\GoodClassifyService;
use App\Services\Marking\StockClassifService;
use Illuminate\Console\Command;

/**
 * Авто-классификация товаров «не проверяли»: по данным товара подбираем код
 * ТН ВЭД, по справочнику маркировки решаем подлежит/не подлежит (+ ОКПД2) и
 * ставим вердикт — та же операция, что человек делает на карточке Нац Каталога.
 *
 * Своей бизнес-логики не держит: выборка — StockClassifService::uncheckedCodes(),
 * подбор — AutoClassifyService::classifyOne(), запись — GoodClassifyService::setVerdict().
 */
class TnvedClassify extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:classify
        {--limit=10 : сколько товаров «не проверяли» обработать}
        {--confidence=80 : порог уверенности; ниже — пропуск, остаётся «не проверяли»}
        {--apply : записать вердикты (без флага — только отчёт, dry-run)}';

    /**
     * @var string
     */
    protected $description = 'Авто-классификация «не проверяли»: подбор ТН ВЭД, вердикт маркировки и ОКПД2';

    public function handle(
        StockClassifService $stock,
        AutoClassifyService $auto,
        GoodClassifyService $verdict
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $threshold = (int) $this->option('confidence');
        $apply = (bool) $this->option('apply');

        $codes = $stock->uncheckedCodes($limit);
        if (empty($codes)) {
            $this->warn('Нет товаров «не проверяли» (или снапшот пуст — запустите stock:classif).');
            return self::SUCCESS;
        }

        $labels = [
            'no_name' => 'нет названия',
            'not_found' => 'не подобрано',
            'low_confidence' => 'пропуск (низкая)',
        ];

        $rows = [];
        foreach ($codes as $code) {
            $r = $auto->classifyOne($code, $threshold);

            $action = $labels[$r['status']] ?? 'dry-run';
            if ($r['status'] === 'ok' && $apply) {
                try {
                    $verdict->setVerdict($code, $r['mark_required'], $r['tnved'], $r['okpd2'], $r['prim']);
                    $action = 'записано';
                } catch (\Throwable $e) {
                    $action = 'ошибка: ' . $e->getMessage();
                }
            }

            $rows[] = [
                $code,
                $r['name'],
                $r['tnved'] ?? '',
                $r['tnved_name'] ? mb_strimwidth($r['tnved_name'], 0, 40, '…') : '',
                $r['status'] === 'ok' ? ($r['mark_required'] ? 'да' : 'нет') : '',
                $r['okpd2'] ?? '',
                $r['confidence'] !== null ? $r['confidence'] . '%' : '',
                $action,
            ];
        }

        $this->table(
            ['Код', 'Товар', 'ТНВЭД', 'Что это', 'Подлежит', 'ОКПД2', 'Увер.', 'Действие'],
            $rows
        );
        if (!$apply) {
            $this->info('Отчёт (dry-run). Для записи вердиктов добавьте --apply.');
        }

        return self::SUCCESS;
    }
}
