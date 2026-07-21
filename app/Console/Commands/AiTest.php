<?php

namespace App\Console\Commands;

use App\Services\AI\AIRequest;
use App\Services\AI\AIService;
use Illuminate\Console\Command;

class AiTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test
        {prompt=Ответь одним словом - всё работает}
        {--provider= : Провайдер (claude|openai|yandex); по умолчанию — самая дешёвая доступная модель}
        {--model= : Конкретная модель провайдера}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверка AI-модуля: дёрнуть провайдера/модель и показать ответ, токены и стоимость';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AIService $ai)
    {
        $available = $ai->getAvailableProviders();

        if (empty($available)) {
            $this->error('Нет доступных провайдеров — не задан ни один API-ключ в .env');
            return 1;
        }

        $this->info('Доступные провайдеры: ' . implode(', ', array_keys($available)));

        $provider = $this->option('provider');
        $model = $this->option('model');

        if ($provider === null || $model === null) {
            $cheapest = $ai->getCheapestModel();
            if ($cheapest === null) {
                $this->error('Не удалось подобрать модель');
                return 1;
            }
            $provider = $provider ?? $cheapest['provider'];
            $model = $model ?? $cheapest['model'];
        }

        $this->line(sprintf('Запрос → провайдер: <info>%s</info>, модель: <info>%s</info>', $provider, $model));

        $request = new AIRequest(
            staticPrompt: 'Ты лаконичный ассистент. Отвечай кратко.',
            dynamicPrompt: (string) $this->argument('prompt'),
            temperature: 0.3,
            maxTokens: 256,
        );

        try {
            $response = $ai->generate($provider, $model, $request);
        } catch (\Throwable $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->line('<comment>Ответ:</comment>');
        $this->line($response->content);
        $this->newLine();
        $this->table(
            ['Провайдер', 'Модель', 'Input', 'Output', 'Кэш', 'Стоимость, $'],
            [[
                $response->provider,
                $response->model,
                $response->inputTokens,
                $response->outputTokens,
                $response->cached ? 'да' : 'нет',
                sprintf('%.6f', $response->cost),
            ]]
        );

        return 0;
    }
}
