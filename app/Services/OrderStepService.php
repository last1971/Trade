<?php


namespace App\Services;


use App\OrderStep;
use Carbon\Carbon;

class OrderStepService extends ModelService
{
    public function __construct()
    {
        parent::__construct(OrderStep::class);
        $this->dateAttributes = ['DATA'];
    }

    public function create($request)
    {
        $item = $request->getAttibutes()['item'];
        $item['USERNAME'] = $request->user()->name;
        $item['DATA'] = Carbon::now()->format('d.m.Y');
        $request->merge(compact('item'));
        return parent::create($request);
    }

    public function update($request, $id)
    {
        $item = $request->getAttibutes()['item'];
        $item['USERNAME'] = $request->user()->name;
        $item['DATA'] = Carbon::now()->format('d.m.Y');
        $request->merge(compact('item'));
        return parent::update($request, $id);
    }
}
