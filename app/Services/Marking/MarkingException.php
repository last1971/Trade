<?php

namespace App\Services\Marking;

use App\Exceptions\ApiException;

// ApiException.render() отдаёт {message, errors} с HTTP 400 — тексты проверок
// («коды уже переданы», «не в обороте») доходят до снекбара, а не глушатся 500-й.
class MarkingException extends ApiException
{
}
