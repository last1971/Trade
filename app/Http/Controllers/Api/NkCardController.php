<?php

namespace App\Http\Controllers\Api;

use App\GoodClassif;
use App\Http\Controllers\Controller;
use App\Services\Marking\NkCardService;
use Illuminate\Http\Request;

/**
 * Карточка Нацкаталога на строке GOODS_CLASSIF: чтение, состояние создания, создание,
 * публикация, справочники для формы. Логики нет — всё в NkCardService.
 */
class NkCardController extends Controller
{
    private const MESSAGES = [
        'name.required' => 'Укажите наименование карточки',
        'name.max' => 'Наименование — не более 200 символов',
        'catId.required' => 'Не выбрана категория каталога',
        'catId.integer' => 'Категория — число',
        'vid.required' => 'Не выбран вид товара',
        'vidOther.required_if' => 'Для вида «НЕТ В СПРАВОЧНИКЕ» укажите вид товара словами',
        'tnved.required' => 'Укажите ТН ВЭД',
        'tnved.digits' => 'ТН ВЭД — 10 цифр',
        'catId.min' => 'Категория — число',
    ];

    public function __construct(private NkCardService $service)
    {
    }

    public function card($id)
    {
        return $this->service->card(GoodClassif::query()->findOrFail(intval($id)));
    }

    public function state($id)
    {
        return $this->service->state(GoodClassif::query()->findOrFail(intval($id)));
    }

    public function create(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'catId' => 'required|integer|min:1',
            'vid' => 'required|string|max:100',
            'vidOther' => 'nullable|string|max:100|required_if:vid,' . NkCardService::VID_ABSENT,
            'brand' => 'nullable|string|max:100',
            'moderation' => 'nullable|boolean',
        ], self::MESSAGES);
        $data['moderation'] = array_key_exists('moderation', $data) ? (bool)$data['moderation'] : true;
        return $this->service->create(GoodClassif::query()->findOrFail(intval($id)), $data);
    }

    public function sign(Request $request, $id)
    {
        $request->validate(['publication' => 'nullable|boolean']);
        return $this->service->sign(GoodClassif::query()->findOrFail(intval($id)), (bool)$request->input('publication', false));
    }

    public function categories(Request $request)
    {
        $data = $request->validate(['tnved' => 'required|digits:10'], self::MESSAGES);
        return $this->service->categories($data['tnved']);
    }

    public function attributes(Request $request)
    {
        $data = $request->validate(['catId' => 'required|integer|min:1'], self::MESSAGES);
        return $this->service->attributes(intval($data['catId']));
    }

    public function brands(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        return $this->service->brands((string)($data['q'] ?? ''));
    }
}
