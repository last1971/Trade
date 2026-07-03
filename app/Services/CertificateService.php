<?php

namespace App\Services;

use App\Certificate;

class CertificateService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Certificate::class);
    }
}
