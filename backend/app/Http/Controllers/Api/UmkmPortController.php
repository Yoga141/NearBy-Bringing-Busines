<?php

namespace App\Http\Controllers\Api;

use App\Excel\Porter;
use App\Excel\UmkmPorter;

/** Excel export / import of UMKM rows - see {@see ExcelPortController} and {@see UmkmPorter}. */
class UmkmPortController extends ExcelPortController
{
    protected function porter(): Porter
    {
        return app(UmkmPorter::class);
    }
}
