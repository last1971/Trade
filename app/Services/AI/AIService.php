<?php

declare(strict_types=1);

namespace App\Services\AI;

final class AIService
{
    /**
     * @var array<string, AIProviderInterface>
     */
    private array $providers = [];

    /**
     * @param iterable<AIProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    /**
     * Генерация контента через указанный провайдер и модель
     */
    public function generate(string $provider, string $model, AIRequest $request): AIResponse
    {
        $providerInstance = $this->getProvider($provider);

        return $providerInstance->generate($request, $model);
    }

    /**
     * Получить провайдер по имени
     */
    public function getProvider(string $name): AIProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown AI provider: %s. Available: %s',
                $name,
                implode(', ', array_keys($this->providers))
            ));
        }

        return $this->providers[$name];
    }

    /**
     * Получить все доступные провайдеры (у которых настроен API ключ)
     *
     * @return array<string, AIProviderInterface>
     */
    public function getAvailableProviders(): array
    {
        return array_filter(
            $this->providers,
            fn (AIProviderInterface $p) => $p->isAvailable()
        );
    }

    /**
     * Получить все провайдеры
     *
     * @return array<string, AIProviderInterface>
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Получить список провайдеров и моделей для фронта
     *
     * @return array<string, array{
     *     name: string,
     *     displayName: string,
     *     available: bool,
     *     models: array<string, array{
     *         name: string,
     *         input_cost: float,
     *         output_cost: float,
     *         tier: string,
     *         supports_cache: bool,
     *         supports_web_search: bool
     *     }>
     * }>
     */
    public function getProvidersForFrontend(): array
    {
        $result = [];

        foreach ($this->providers as $name => $provider) {
            $models = [];
            foreach ($provider->getModels() as $modelId => $modelConfig) {
                $models[$modelId] = [
                    'name' => $modelConfig['name'],
                    'input_cost' => $modelConfig['input_cost'],
                    'output_cost' => $modelConfig['output_cost'],
                    'tier' => $modelConfig['tier'],
                    'supports_cache' => $modelConfig['supports_cache'],
                    'supports_web_search' => $modelConfig['supports_web_search'],
                ];
            }

            $result[$name] = [
                'name' => $name,
                'displayName' => $provider->getDisplayName(),
                'available' => $provider->isAvailable(),
                'models' => $models,
            ];
        }

        return $result;
    }

    /**
     * Получить самую дешёвую доступную модель
     *
     * @return array{provider: string, model: string}|null
     */
    public function getCheapestModel(): ?array
    {
        $cheapest = null;
        $cheapestCost = \PHP_FLOAT_MAX;

        foreach ($this->getAvailableProviders() as $providerName => $provider) {
            foreach ($provider->getModels() as $modelId => $config) {
                // Средняя стоимость (input + output) / 2
                $avgCost = ($config['input_cost'] + $config['output_cost']) / 2;

                if ($avgCost < $cheapestCost) {
                    $cheapestCost = $avgCost;
                    $cheapest = [
                        'provider' => $providerName,
                        'model' => $modelId,
                    ];
                }
            }
        }

        return $cheapest;
    }
}
