<?php

namespace App\Interfaces;

interface ISellerOrderService
{
    /**
     * Получение списка заказов с пагинацией
     * @param $request
     * @return mixed
     */
    public function index($request);

    /**
     * Создание нового заказа
     * @param $request
     * @return array
     */
    public function create($request);

    /**
     * Добавление строк в заказ
     * @param string $salesId - ID заказа
     * @param array $lines - массив строк для добавления
     * @param bool $reserveAll - резервировать все строки
     * @return mixed
     */
    public function addLines(string $salesId, array $lines, bool $reserveAll = true);

    /**
     * Получение строк заказа
     * @param string $salesId - ID заказа
     * @return array ['lines' => array, 'total' => int]
     */
    public function getLines(string $salesId);

    /**
     * Получение информации о заказе
     * @param string $salesId - ID заказа
     * @return array
     */
    public function getOrder(string $salesId);

    /**
     * Изменение количества в строке заказа
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @param int $quantity - новое количество
     * @return mixed
     */
    public function updateLineQuantity(string $salesId, string $lineId, int $quantity);

    /**
     * Удаление строки заказа
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @return mixed
     */
    public function deleteLine(string $salesId, string $lineId);

    /**
     * Отправка счета
     * @param string $salesId - ID заказа
     * @return mixed
     */
    public function sendInvoice(string $salesId);

    /**
     * Отгрузка заказа
     * @param string $salesId - ID заказа
     * @param string|null $customerDeliveryTypeId - ID способа доставки
     * @param string|null $dateDeadline - дата действия (формат YYYY-MM-DD)
     * @return mixed
     */
    public function shipOrder(string $salesId, ?string $customerDeliveryTypeId, ?string $dateDeadline);
}

