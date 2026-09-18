<?php

namespace App\Http\Controllers\Api;

use App\Excel\Porter;
use App\Excel\SheetCodec;
use App\Http\Controllers\Controller;
use App\Support\Xlsx\XlsxException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * HTTP side of the dashboard's Excel export / import, shared by every dataset.
 *
 * Endpoints (under the dataset's prefix, e.g. /api/umkm-excel):
 *
 *  GET  /download  - the user's rows as a ready-made .xlsx file
 *  GET  /template  - an empty .xlsx with an example row and a guide sheet
 *  POST /preview   - validate an upload and report what would change (writes nothing)
 *  POST /commit    - re-validate and apply, all-or-nothing
 *  GET  /export    - the same rows as JSON (kept for API clients)
 *
 * preview/commit accept either a multipart `file` (.xlsx, parsed on the
 * server) or a JSON body `{ "rows": [...] }` of already-parsed rows.
 */
abstract class ExcelPortController extends Controller
{
    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /** Upload size limit, in kilobytes. */
    private const MAX_UPLOAD_KB = 5120;

    public function __construct(protected readonly SheetCodec $codec) {}

    abstract protected function porter(): Porter;

    /** Rows as JSON. */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'isAdmin' => $user->isAdmin(),
            'rows' => $this->porter()->exportRows($user),
        ]);
    }

    /** Rows as an .xlsx download. */
    public function download(Request $request): BinaryFileResponse|JsonResponse
    {
        $user = $request->user();
        $porter = $this->porter();
        $rows = $porter->exportRows($user);

        if (! $rows) {
            return response()->json(['message' => 'Belum ada data untuk diekspor.'], 404);
        }

        return $this->xlsx($this->codec->export($porter, $rows, $user->isAdmin()), $this->codec->exportFilename($porter));
    }

    /** Blank import template. */
    public function template(Request $request): BinaryFileResponse
    {
        $porter = $this->porter();

        return $this->xlsx($this->codec->template($porter, $request->user()->isAdmin()), $porter->templateFilename());
    }

    /** Validate an upload and report what would happen, writing nothing. */
    public function preview(Request $request): JsonResponse
    {
        return response()->json($this->porter()->analyse($this->incomingRows($request), $request->user()));
    }

    /** Re-validate and apply. Refuses the whole sheet if any row is invalid. */
    public function commit(Request $request): JsonResponse
    {
        $result = $this->porter()->commit($this->incomingRows($request), $request->user());

        if (! $result['ok']) {
            return response()->json([
                'message' => 'Masih ada baris yang bermasalah. Perbaiki dulu lalu impor ulang.',
                'summary' => $result['summary'],
                'rows' => $result['rows'],
            ], 422);
        }

        return response()->json([
            'message' => 'Impor selesai.',
            'summary' => $result['summary'],
        ]);
    }

    /**
     * The rows to import, from an uploaded .xlsx or a JSON body.
     *
     * @return list<mixed>
     */
    private function incomingRows(Request $request): array
    {
        if (! $request->hasFile('file') && ! $request->exists('file')) {
            $request->validate([
                'rows' => ['required', 'array', 'min:1', 'max:'.Porter::MAX_ROWS],
            ], [
                'rows.required' => 'Tidak ada baris data untuk diimpor.',
                'rows.min' => 'Tidak ada baris data untuk diimpor.',
                'rows.max' => 'Maksimal '.Porter::MAX_ROWS.' baris per impor - pecah file menjadi beberapa bagian.',
            ]);

            return array_values($request->input('rows'));
        }

        $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_UPLOAD_KB, 'extensions:xlsx'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.file' => 'Unggahan file gagal. Coba lagi.',
            'file.uploaded' => 'Unggahan file gagal. Coba lagi.',
            'file.max' => 'Ukuran file maksimal '.(self::MAX_UPLOAD_KB / 1024).' MB.',
            'file.extensions' => 'File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).',
        ]);

        try {
            return $this->codec->parse(
                $this->porter(),
                $request->file('file')->getRealPath(),
                $request->user()->isAdmin(),
            );
        } catch (XlsxException $e) {
            throw ValidationException::withMessages(['file' => [$e->getMessage()]]);
        }
    }

    private function xlsx(string $path, string $filename): BinaryFileResponse
    {
        return response()
            ->download($path, $filename, [
                'Content-Type' => self::XLSX_MIME,
                'Cache-Control' => 'no-store, private',
            ])
            ->deleteFileAfterSend();
    }
}
