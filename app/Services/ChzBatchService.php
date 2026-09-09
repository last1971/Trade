<?php

namespace App\Services;

use App\ChzBatch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Таблица пачек очереди ЧЗ: фильтры, сортировка и постраничная выдача —
 * общим механизмом ModelService, как у всех списков проекта. Своей выборки
 * у страницы «Отправка в ЧЗ» больше нет: сотня строк одним куском не давала
 * ни искать, ни листать.
 */
class ChzBatchService extends ModelService
{
    protected $dateAttributes = [
        'CREATED_AT', 'SENT_AT', 'CONFIRMED_AT',
    ];

    public function __construct()
    {
        parent::__construct(ChzBatch::class);

        // Номер счёта и номер УПД — то, чем человек ищет пачку. leftJoin: пачка
        // без документа (деление) из списка выпадать не должна.
        $this->aliases['invoice.NS'] = function (Builder $query) {
            $query->leftJoin('S as invoice', 'invoice.SCODE', '=', 'CHZ_BATCH.SCODE');
        };

        $this->aliases['transferOut.NSF'] = function (Builder $query) {
            $query->leftJoin('SF as transferOut', 'transferOut.SFCODE', '=', 'CHZ_BATCH.SFCODE');
        };
    }

    /** Ручные пачки (STATUS IS NULL) ведёт человек файлом — на этой странице им не место. */
    public function index($request)
    {
        return parent::index($request)->whereNotNull('CHZ_BATCH.STATUS');
    }
}
