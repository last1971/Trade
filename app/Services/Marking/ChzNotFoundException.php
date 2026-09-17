<?php

namespace App\Services\Marking;

/**
 * chz-сервис ответил 404: «такого нет» (карточки в каталоге, заказа, отметки запроса).
 * Отдельный класс, чтобы зовущий отличал «нет» от «сломалось», не разбирая текст.
 */
class ChzNotFoundException extends MarkingException
{
}
