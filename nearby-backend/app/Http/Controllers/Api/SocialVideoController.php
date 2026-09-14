<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SocialVideoResource;
use App\Models\SocialVideo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SocialVideoController extends Controller
{
    /** Public: the cards rendered in the homepage "Video dari medsos" section. */
    public function index()
    {
        return SocialVideoResource::collection(
            SocialVideo::where('active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    /** Admin: every slot, including the inactive ones and the empty placeholders. */
    public function adminIndex()
    {
        return SocialVideoResource::collection(
            SocialVideo::orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= (int) SocialVideo::max('sort_order') + 1;
        // Set explicitly rather than leaning on the column default: the model
        // returned to the client is the in-memory one, so an omitted `active`
        // would come back as null and read as "off" in the dashboard toggle.
        $data['active'] ??= true;

        return new SocialVideoResource(SocialVideo::create($data));
    }

    public function update(Request $request, SocialVideo $socialVideo)
    {
        $socialVideo->update($this->validated($request, $socialVideo));

        return new SocialVideoResource($socialVideo);
    }

    public function destroy(SocialVideo $socialVideo)
    {
        $socialVideo->delete();

        return response()->noContent();
    }

    /**
     * Shared validation. A link is optional (an empty slot is a valid state),
     * but when given it has to be one we can actually turn into an embed for
     * the chosen platform — otherwise the card would silently stay blank.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SocialVideo $existing = null): array
    {
        $data = $request->validate([
            'platform' => [$existing ? 'sometimes' : 'required', Rule::in(['youtube', 'instagram'])],
            'title' => [$existing ? 'sometimes' : 'required', 'string', 'max:150'],
            'url' => ['nullable', 'string', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        // Normalise an empty string from the form into a real "slot is empty".
        if (array_key_exists('url', $data) && trim((string) $data['url']) === '') {
            $data['url'] = null;
        }

        $platform = $data['platform'] ?? $existing?->platform;
        $url = array_key_exists('url', $data) ? $data['url'] : $existing?->url;

        if ($url !== null) {
            $recognised = $platform === 'youtube'
                ? SocialVideo::youtubeId($url) !== null
                : SocialVideo::instagramCode($url) !== null;

            if (! $recognised) {
                throw ValidationException::withMessages([
                    'url' => $platform === 'youtube'
                        ? 'Tautan YouTube tidak dikenali. Gunakan tautan video, youtu.be, atau Shorts.'
                        : 'Tautan Instagram tidak dikenali. Gunakan tautan permalink post atau reel.',
                ]);
            }
        }

        return $data;
    }
}
