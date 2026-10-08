<?php

namespace App\Services;

use App\Certificate;
use Illuminate\Database\Eloquent\Builder;

class CertificateService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Certificate::class);

        // Фильтр «по товару»: join подключается только когда фильтр пришёл (пустые фронт отбрасывает)
        $this->aliases['certificateGoods.good_id'] = function (Builder $query) {
            $query->join(
                'certificate_goods as certificateGoods',
                'certificateGoods.certificate_id',
                '=',
                'certificates.id'
            );
        };
    }
}
