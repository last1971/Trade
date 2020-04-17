<?php


namespace App\Services;


use App\User;
use App\UserBuyer;

class UserService extends ModelService
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function update($request, $id)
    {
        $user = User::find($id);
        if (isset($request->item['roles'])) {
            $user->roles()->sync(array_map(function ($val) {
                return $val['id'];
            }, $request->item['roles']));
        }
        if (isset($request->item['user_buyers'])) {
            $newData = array_map(function ($v) {
                return $v['buyer']['POKUPATCODE'];
            }, $request->item['user_buyers']);
            $user->userBuyers()->whereNotIn('buyer_id', $newData)->delete();
            foreach ($newData as $data) {
                UserBuyer::query()->firstOrCreate(['buyer_id' => $data, 'user_id' => $id]);
            }
        }
        return parent::update($request, $id);
    }
}
