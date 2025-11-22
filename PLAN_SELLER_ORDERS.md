# План реализации заказов поставщиков

## Задача
Расширить систему работы с заказами поставщиков:
1. Добавить поддержку остальных поставщиков (через Firebird)
2. Реализовать Компэл ДМС (хранение в MariaDB + отгрузка в API)
3. UI для управления активными заказами

---

## ЭТАП 1: База данных

### 1.1. Создать таблицу seller_order_lines
**Файл:** `database/migrations/2025_01_18_000001_create_seller_order_lines_table.php` - ✅ СОЗДАН

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerOrderLinesTable extends Migration
{
    public function up()
    {
        Schema::create('seller_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_order_id');
            $table->string('item_id');
            $table->string('item_name');
            $table->integer('qty');
            $table->decimal('price', 10, 2);
            $table->json('price_data')->nullable(); // ← полная строка прайса для DMS
            $table->timestamps();

            $table->foreign('seller_order_id')
                  ->references('id')
                  ->on('seller_orders')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_order_lines');
    }
}
```

**Поле price_data:** хранит полную строку прайса от поставщика (JSON) с доп. полями для DMS API:
- `brend` / `invc_brend_alias`
- `prognosis_id`
- `min_qty`, `max_qty`, `qty_multiples`
- `application_area_id`
- и другие поля

**Команда:** `php artisan migrate`

### 1.2. Модели
**Файл:** `app/SellerOrderLine.php` - ✅ СОЗДАН
**Файл:** `app/SellerOrder.php` - ✅ ОБНОВЛЕН

Связи:
- `SellerOrder->lines()` → hasMany
- `SellerOrderLine->sellerOrder()` → belongsTo
- Автоматический cast `price_data` в array/json

---

## ЭТАП 2: Backend - Сервисы

### 2.1. FirebirdOrderService (для остальных поставщиков)
**Файл:** `app/Services/FirebirdOrderService.php` - **СОЗДАТЬ**

Работает с:
- `Order` (ZAKAZ_MASTER) - заказы в Firebird
- `OrderLine` (ZAKAZ_DETAIL) - строки заказов

Требования:
- Можно выбирать только заказы со STATUS = 'FORMING'
- При добавлении строки ОБЯЗАТЕЛЬНА привязка к good_id (GOODSCODE)
- NAME_IN_PRICE = название от поставщика

### 2.2. CompelDmsOrderService (для Компэл ДМС)
**Файл:** `app/Services/CompelDmsOrderService.php` - **СОЗДАТЬ**

Работает с:
- `SellerOrder` (seller_orders) - MariaDB
- `seller_order_lines` - MariaDB

Особенности:
- Всегда один незакрытый заказ на "поставщика" DMS
- При shipOrder() создается реальный заказ в Compel API
- После отгрузки заказ помечается closed = true

### 2.3. Обновить SellerOrderService
**Файл:** `app/Services/SellerOrderService.php` - **ИЗМЕНИТЬ**

В конструкторе добавить:
```php
$this->orderServices = collect([
    config('pricing.Compel.sellerId') => new CompelOrderService(),
    config('pricing.Promelec.sellerId') => new PromelecOrderService(),
    config('pricing.CompelDms.sellerId') => new CompelDmsOrderService(), // ← НОВОЕ
]);

$this->defaultService = new FirebirdOrderService(); // ← НОВОЕ
```

В методе `getOrderServiceBySeller()`:
```php
return $this->orderServices->get($sellerId)
    ?? $this->defaultService; // ← для ГетЧипс, ДАН и всех остальных
```

---

## ЭТАП 3: Конфигурация

### 3.1. Добавить sellerId для Компэл ДМС
**Файл:** `config/pricing.php` - **ИЗМЕНИТЬ**

```php
'CompelDms' => [
    'sellerId' => 999, // TODO: выделить реальный ID
],
```

---

## ЭТАП 4: API endpoints (проверить существующие)

Должны работать через SellerOrderController:
- `GET /api/seller-orders?seller_id={id}` - список заказов
- `POST /api/seller-orders` - создать заказ
- `POST /api/seller-orders/{id}/lines` - добавить строку
- `GET /api/seller-orders/{id}/lines` - получить строки
- `PUT /api/seller-orders/{id}/lines` - изменить количество
- `DELETE /api/seller-orders/{id}/lines` - удалить строку
- `POST /api/seller-orders/{id}/ship` - отгрузить (для DMS)

---

## ЭТАП 5: Frontend (UI)

### 5.1. Компонент ActiveOrdersList.vue - СОЗДАТЬ
Показывает активные заказы на экране счета (Invoice)
- Карточки только для заказов с выбранным orderId
- Кнопка "+ Добавить заказ поставщика"

### 5.2. Компонент SellerOrderSelect.vue - СОЗДАТЬ
Dropdown для выбора заказа конкретного поставщика
- Загружает заказы через API
- Фильтрует по статусу "формирующийся"
- Для DMS - показывает единственный заказ или кнопку создать

### 5.3. Обновить SellerPriceQuantity.vue - ИЗМЕНИТЬ
Добавить обработку для:
- Firebird заказов (проверка good_id)
- DMS заказов

### 5.4. Компонент для отгрузки DMS - СОЗДАТЬ
Кнопка "Отгрузить" для DMS заказа
- Показывает подтверждение
- Вызывает shipOrder API
- Показывает номер созданного заказа в Compel

---

## ПОРЯДОК ВЫПОЛНЕНИЯ

### ✅ Подготовка
- [x] Определить sellerId для Компэл ДМС (1279)
- [x] Проверить статусы заказов в ZAKAZ_MASTER (0-6, используем STATUS = 0 для формирующихся)

### ✅ Шаг 1: База данных
- [x] Создать миграцию create_seller_order_lines (1.1)
- [x] Создать модель SellerOrderLine
- [x] Обновить модель SellerOrder
- [x] Запустить `php artisan migrate`

### ✅ Шаг 2: Backend
- [x] Создать FirebirdOrderService (2.1)
- [x] Создать CompelDmsOrderService (2.2)
- [x] Обновить SellerOrderService (2.3)
- [x] Проверить config/pricing.php (CompelDms sellerId = 1279)

### ✅ Шаг 3: Тестирование backend
- [ ] Тест: получить список заказов ГетЧипс (Firebird)
- [ ] Тест: создать заказ ГетЧипс
- [ ] Тест: добавить строку в заказ (с good_id)
- [ ] Тест: создать DMS заказ
- [ ] Тест: добавить строки в DMS
- [ ] Тест: отгрузить DMS → проверить создание в Compel API

### ✅ Шаг 4: Frontend
- [x] Обновить SellerOrdersList.vue - использовать поставщиков из стора
- [x] Обновить SellerOrderLinesDialog.vue - добавить логику для всех поставщиков
- [x] Добавить обработку Excel файлов на фронте
- [x] Кнопка "Отправить счет" для всех поставщиков
- [x] Кнопка "Отгрузить": Compel/DMS с диалогом, остальные - простая отгрузка

### ✅ Шаг 5: Тестирование UI
- [ ] Выбор заказа ГетЧипс
- [ ] Добавление позиции в заказ ГетЧипс (только с good_id)
- [ ] Создание DMS заказа
- [ ] Добавление позиций в DMS
- [ ] Отгрузка DMS заказа
- [ ] Генерация Excel для Firebird заказов
- [ ] Генерация Excel для DMS заказов

---

## Архитектура решения

```
SellerOrderService (роутер)
├── CompelOrderService → Compel API
├── PromelecOrderService → Promelec API
├── CompelDmsOrderService → MariaDB (seller_orders + seller_order_lines)
│   └── При shipOrder → Compel API
└── FirebirdOrderService (default) → Firebird (ZAKAZ_MASTER + ZAKAZ_DETAIL)
    └── Для: ГетЧипс, ДАН, МАРС-КОМПОНЕНТ и всех остальных
```

---

## Важные замечания

### Firebird заказы:
- Используем существующие Order/OrderLine модели
- **STATUS = 0** (Формируется) для формирующихся заказов
- GOODSCODE (good_id) ОБЯЗАТЕЛЕН для добавления строки
- NAME_IN_PRICE = наименование поставщика
- При отгрузке меняется на STATUS = 1 (Сформирован)

### Компэл ДМС:
- Всегда ОДИН активный заказ (определяется по seller_id, closed = false)
- Строки хранятся локально в seller_order_lines с полным price_data (JSON)
- Отгрузка = создание реального заказа через CompelApiService используя данные из price_data
- После отгрузки заказ закрывается (closed = true)

### UI:
- Показывать только заказы с выбранным orderId (не все 24 поставщика сразу)
- Для Firebird - проверять привязку к good_id перед добавлением
- Для DMS - кнопка "Отгрузить" вместо обычной отгрузки

---

## Созданные файлы

### Backend:
- ✅ `app/Services/FirebirdOrderService.php` (с генерацией Excel)
- ✅ `app/Services/CompelDmsOrderService.php` (с генерацией Excel)
- ✅ `app/Services/SellerOrderService.php` (обновлен)
- ✅ `app/SellerOrderLine.php`
- ✅ `app/SellerOrder.php` (обновлен)
- ✅ `database/migrations/2025_01_18_000001_create_seller_order_lines_table.php`
- ✅ `app/Exports/SellerOrderLinesExport.php` (для Firebird)
- ✅ `app/Exports/SellerOrderLinesDmsExport.php` (для DMS)

### Frontend:
- ✅ `resources/js/components/seller/SellerOrdersList.vue` (загрузка из стора)
- ✅ `resources/js/components/seller/SellerOrderLinesDialog.vue` (обработка Excel)

### Статусы заказов (ZAKAZ_MASTER):
```
0 - Формируется    ← используем для выбора
1 - Сформирован    ← меняем при отгрузке
2 - В Пути
3 - Частично пришел
4 - Пришел
5 - Закрыт
6 - К выполнению
```

---

## СЛЕДУЮЩИЙ ЭТАП: Frontend
