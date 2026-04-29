<?php

namespace App\Services\Upd;

use App\Services\Upd\Contracts\UpdSourceInterface;
use Illuminate\Support\Facades\View;

class UpdXmlBuilder
{
    /**
     * Рендерит XML УПД/УПД-2 из UpdSourceInterface в формате ФНС (ON_NSCHFDOPPR).
     * Возвращает строку в кодировке windows-1251 с XML-декларацией.
     */
    public function build(UpdSourceInterface $source): string
    {
        $output = View::make('upd-xml')->with(['source' => $source])->render();
        return "<?xml version=\"1.0\" encoding=\"windows-1251\" ?> \n"
            . iconv('utf-8', 'cp1251//TRANSLIT//IGNORE', $output);
    }
}
