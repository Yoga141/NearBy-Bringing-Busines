<?php

namespace App\Support\Xlsx;

use RuntimeException;

/**
 * A spreadsheet that could not be written or read.
 *
 * The message is written for the end user (in Indonesian), because it is shown
 * verbatim next to the import button.
 */
class XlsxException extends RuntimeException {}
