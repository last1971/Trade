<?php

namespace App\Console\Commands;

use App\Certificate;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ImportCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificates:import
        {dir : Папка с файлами сертификатов (pdf/jpg/jpeg/png)}
        {type : Тип документов в папке (например "Отказное письмо")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импорт файлов сертификатов из папки в реестр';

    private $extensions = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dir = $this->argument('dir');
        if (!is_dir($dir)) {
            $this->error('Папка не найдена: ' . $dir);
            return 1;
        }
        $imported = 0;
        $skipped = 0;
        foreach (scandir($dir) as $name) {
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) continue;
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($extension, $this->extensions)) {
                $this->warn('Пропущен (не сертификат): ' . $name);
                $skipped++;
                continue;
            }
            $number = pathinfo($name, PATHINFO_FILENAME);
            if (Certificate::query()->where('original_name', $name)->exists()) {
                $this->warn('Пропущен (уже импортирован): ' . $name);
                $skipped++;
                continue;
            }
            Certificate::create([
                'number' => $number,
                'type' => $this->argument('type'),
                'file_path' => Storage::putFile('certificates', new File($path)),
                'original_name' => $name,
                'mime' => mime_content_type($path),
                'size' => filesize($path),
            ]);
            $this->info('Импортирован: ' . $name);
            $imported++;
        }
        $this->info('Готово. Импортировано: ' . $imported . ', пропущено: ' . $skipped);
        return 0;
    }
}
