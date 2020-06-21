<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class XlsFactureImport implements WithCustomValueBinder
{

    use HeaderNameTrait;
}
