<?php

namespace App\Modules\Approval\Exceptions;

use RuntimeException;

/**
 * Pemohon tidak berhak atas langkah approval yang sedang berjalan.
 *
 * Dipisah dari `RuntimeException` biasa supaya controller bisa membalas 403
 * (Forbidden) alih-alih 422. 422 untuk "dokumen sedang menunggu role lain"
 * akan menyesatkan: masalahnya bukan input yang salah, tapi otorisasi.
 */
class ApprovalForbiddenException extends RuntimeException {}
