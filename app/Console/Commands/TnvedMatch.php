<?php

namespace App\Console\Commands;

use App\Services\Tnved\TnvedMatchService;
use Illuminate\Console\Command;

class TnvedMatch extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:match
        {name : название товара}
        {--category= : категория}
        {--case= : корпус}
        {--maker= : производитель}';

    /**
     * @var string
     */
    protected $description = 'Подбор кода ТН ВЭД по описанию товара (проверка)';

    public function handle(TnvedMatchService $service): int
    {
        $res = $service->match(
            (string) $this->argument('name'),
            $this->option('category'),
            $this->option('case'),
            $this->option('maker'),
        );

        if (!($res['found'] ?? false)) {
            $this->error('Не подобрано: ' . ($res['reason'] ?? 'нет данных'));
            $this->line('Позиции: ' . implode(', ', $res['headings'] ?? []));
            return 1;
        }

        $this->info('Подобран код ТН ВЭД:');
        $this->table(
            ['Код', 'Тариф', 'Уверенность', 'Применить', 'Модель', 'Позиции', 'Кандидатов'],
            [[
                $res['code'],
                $res['tariff'] ?? '',
                $res['confidence'] . '%',
                ($res['apply'] ? 'да' : 'нет'),
                $res['model'],
                implode(',', $res['headings']),
                $res['candidates'],
            ]]
        );
        $this->line('<comment>Наименование:</comment> ' . $res['name']);
        $this->line('<comment>Обоснование:</comment> ' . $res['reason']);

        return 0;
    }
}
