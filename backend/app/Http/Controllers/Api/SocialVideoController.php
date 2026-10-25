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
     * Shared validation. A new slot may start without a link; saving a slot
     * with an empty link is refused (hide it with `active` or delete it).
     * The platform follows the link itself, so a wrong dropdown choice never
     * rejects a good link, and the link is stored in its clean form.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SocialVideo $existing = null): array
    {
        $data = $request->validate([
            'platform' => [$existing ? 'sometimes' : 'required', Rule::in(['youtube', 'instagram'])],
            'title' => [$existing ? 'sometimes' : 'required', 'string', 'max:150'],
            'url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        // No link sent: keep the stored one. A brand-new slot may start empty.
        if (! array_key_exists('url', $data)) {
            return $data;
        }
        if (! $existing && trim((string) $data['url']) === '') {
            return [...$data, 'url' => null];
        }

        $link = SocialVideo::parseLink((string) $data['url']);
        if (is_string($link)) {
            throw ValidationException::withMessages(['url' => $link]);
        }

        return [...$data, ...$link];
    }
}
