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
}

