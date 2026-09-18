<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuideVideoResource;
use App\Models\GuideVideo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Registration tutorial videos: uploaded by an admin, watched on /panduan.
 */
class GuideVideoController extends Controller
{
    private const ALLOWED_MIMES = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];

    /** Largest upload we accept, in megabytes. Overridable per environment. */
    private function maxMegabytes(): int
    {
        return max(1, (int) env('GUIDE_VIDEO_MAX_MB', 64));
    }

    /**
     * Public: the video the Panduan page should play, or null when the admin
     * hasn't uploaded one yet (the page then keeps its placeholder).
     */
    public function current()
    {
        $video = GuideVideo::current();

        // 204, not `json(null)`: a JsonResponse built from null serialises to
        // "{}", which the frontend would read as a video object with no fields.
        if (! $video || ! $video->fileExists()) {
            return response()->noContent();
        }

        return new GuideVideoResource($video);
    }

    /**
     * Public: stream the bytes.
     *
     * `response()->file()` returns a BinaryFileResponse, which answers `Range`
     * requests with a 206 - that is what lets the viewer drag the scrubber
     * instead of having to download the whole video before seeking.
     */
    public function stream(GuideVideo $guideVideo): BinaryFileResponse
    {
        abort_unless($guideVideo->fileExists(), 404, 'Berkas video tidak ditemukan.');

        return response()->file($guideVideo->absolutePath(), [
            'Content-Type' => $guideVideo->mime_type,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** Admin: every upload, newest first. */
    public function index()
    {
        return GuideVideoResource::collection(GuideVideo::with('uploader')->latest('id')->get());
    }

    /**
     * Admin: limits the upload form needs to state up front.
     *
     * PHP's own `upload_max_filesize` / `post_max_size` silently truncate a
     * request that exceeds them - the browser finishes uploading and Laravel
     * then sees no file at all. Surfacing the real ceiling lets the dashboard
     * say "maksimal 2 MB" before the admin waits through a doomed upload.
     */
    public function limits()
    {
        $php = min(
            $this->iniBytes(ini_get('upload_max_filesize')),
            $this->iniBytes(ini_get('post_max_size')),
        );
        $configured = $this->maxMegabytes() * 1024 * 1024;

        return response()->json([
            'maxBytes' => min($php, $configured),
            'configuredBytes' => $configured,
            'phpLimitBytes' => $php,
            'phpLimited' => $php < $configured,
            'allowedMimes' => self::ALLOWED_MIMES,
        ]);
    }

    /** Admin: upload a new tutorial video. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'video' => [
                'required',
                'file',
                'mimetypes:'.implode(',', self::ALLOWED_MIMES),
                'max:'.($this->maxMegabytes() * 1024), // validator counts kilobytes
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'video.required' => 'Pilih berkas video terlebih dahulu.',
            'video.mimetypes' => 'Format video harus MP4, WebM, OGG, atau MOV.',
            'video.max' => 'Ukuran video melebihi batas '.$this->maxMegabytes().' MB.',
            // A file between `upload_max_filesize` and `post_max_size` arrives
            // flagged UPLOAD_ERR_INI_SIZE, and the stock message for that is
            // "The video failed to upload" - which sends an admin hunting for a
            // network fault instead of the php.ini line that actually rejected it.
            'video.uploaded' => 'Berkas ditolak PHP karena melebihi upload_max_filesize ('
                .ini_get('upload_max_filesize').'). Naikkan upload_max_filesize dan post_max_size '
                .'di php.ini lalu mulai ulang server.',
            'title.required' => 'Judul video wajib diisi.',
        ]);

        $file = $request->file('video');
        $path = $file->store('guide-videos', 'public');

        // One tutorial plays on the guide page, so a new upload takes over and
        // the previous ones stay as inactive history the admin can switch back to.
        GuideVideo::where('active', true)->update(['active' => false]);

        $video = GuideVideo::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'active' => true,
            'uploaded_by' => $request->user()?->id,
        ]);

        return new GuideVideoResource($video->load('uploader'));
    }

    /** Admin: rename, re-describe, or switch which upload is live. */
    public function update(Request $request, GuideVideo $guideVideo)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul video wajib diisi.',
        ]);

        if (($data['active'] ?? false) === true) {
            GuideVideo::where('active', true)->whereKeyNot($guideVideo->id)->update(['active' => false]);
        }

        $guideVideo->update($data);

        return new GuideVideoResource($guideVideo->load('uploader'));
    }

    /** Admin: delete the row and the file behind it. */
    public function destroy(GuideVideo $guideVideo)
    {
        $guideVideo->deleteFile();
        $guideVideo->delete();

        return response()->noContent();
    }

    /** "2M" / "8M" / "512K" from php.ini → bytes. */
    private function iniBytes(string|false $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return PHP_INT_MAX;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
