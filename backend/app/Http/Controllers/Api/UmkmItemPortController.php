<?php

namespace App\Http\Controllers\Api;

use App\Excel\Porter;
use App\Excel\UmkmItemPorter;

/** Excel export / import of UMKM products - see {@see ExcelPortController} and {@see UmkmItemPorter}. */
class UmkmItemPortController extends ExcelPortController
{
    protected function porter(): Porter
    {
        return app(UmkmItemPorter::class);
    }
}
