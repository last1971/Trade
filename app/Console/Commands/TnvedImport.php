<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TnvedImport extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:import
        {file : путь к xlsx-выгрузке (абсолютный или относительно storage/app)}
        {--sheet=2 : номер листа с кодами, 1-based (у TWS данные на 2-м)}';

    /**
     * @var string
     */
    protected $description = 'Импорт справочника ТН ВЭД (Код/Наименование/Тариф) в таблицу tnved';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!is_file($file)) {
            $file = storage_path('app/' . ltrim($file, '/'));
        }
        if (!is_file($file)) {
            $this->error('Файл не найден: ' . $this->argument('file'));
            return 1;
        }

        $this->info('Читаю ' . $file . ' …');
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);

        $sheetIndex = max(0, (int) $this->option('sheet') - 1);
        $sheet = $spreadsheet->getSheet($sheetIndex);
        // [A, B, C, ...] по строкам; formatted values, чтобы код пришёл текстом
        $rows = $sheet->toArray(null, true, true, false);

        $now = now();
        $batch = [];
        $total = 0;
        DB::table('tnved')->truncate();

        foreach ($rows as $r) {
            $code = trim((string) ($r[0] ?? ''));
            $name = trim((string) ($r[1] ?? ''));
            $tariff = trim((string) ($r[2] ?? ''));

            if ($code === '' || mb_stripos($code, 'Код') === 0) {
                continue; // заголовок / пустая строка
            }
            $code = preg_replace('/\D/', '', $code); // только цифры
            if ($code === '') {
                continue;
            }
            $code = str_pad($code, 10, '0', STR_PAD_LEFT); // восстановить ведущие нули
            if (strlen($code) !== 10) {
                continue; // только полные 10-значные субпозиции
            }

            $batch[] = [
                'code' => $code,
                'name' => $name,
                'tariff' => $tariff !== '' ? $tariff : null,
                'heading' => substr($code, 0, 4),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 1000) {
                DB::table('tnved')->insertOrIgnore($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        if ($batch) {
            DB::table('tnved')->insertOrIgnore($batch);
            $total += count($batch);
        }

        $inTable = DB::table('tnved')->count();
        $this->info("Импортировано строк: {$total}, в таблице: {$inTable}");

        return 0;
    }
}
