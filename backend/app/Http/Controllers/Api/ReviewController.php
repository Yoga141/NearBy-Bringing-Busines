<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Umkm;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** List reviews for a UMKM. */
    public function index(Umkm $umkm)
    {
        return ReviewResource::collection($umkm->reviews()->latest()->get());
    }

    /** The authenticated user's own reviews, across every UMKM (Akun → Riwayat → Komentar). */
    public function mine(Request $request)
    {
        $reviews = Review::where('user_id', $request->user()->id)
            ->with('umkm')
            ->latest()
            ->get();

        return ReviewResource::collection($reviews);
    }

    /** Authenticated user posts a review. */
    public function store(Request $request, Umkm $umkm)
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = $umkm->reviews()->create([
            'user_id' => $request->user()->id,
            'author_name' => $request->user()->name,
            'stars' => $data['stars'],
            'text' => $data['text'] ?? null,
        ]);

        // Keep the cached rating / count in step (see Umkm::applyReviewDelta
        // for why this is incremental rather than a recount).
        $umkm->applyReviewDelta($review->stars, 1);

        return new ReviewResource($review);
    }

    /** The review's author edits their own stars/text. */
    public function update(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403, 'Hanya penulis ulasan yang bisa mengubahnya.');

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStars = $review->stars;
        $review->update($data);

        if ($review->stars !== $oldStars) {
            $review->umkm()->withTrashed()->first()?->applyReviewDelta($review->stars - $oldStars, 0);
        }

        return new ReviewResource($review);
    }

    /** The review's author deletes their own review. */
    public function destroy(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403, 'Hanya penulis ulasan yang bisa menghapusnya.');

        $review->delete();
        $review->umkm()->withTrashed()->first()?->applyReviewDelta(-$review->stars, -1);

        return response()->json(['message' => 'Ulasan dihapus.']);
    }

    /** UMKM owner replies to a review. */
    public function reply(Request $request, Review $review)
    {
        $user = $request->user();
        $umkm = $review->umkm()->withTrashed()->first();
        abort_unless(
            $user->isAdmin() || ($umkm && $umkm->owner_id === $user->id),
            403,
            'Hanya pemilik UMKM yang bisa membalas.'
        );

        $data = $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        $review->update(['reply' => $data['reply']]);

        return new ReviewResource($review);
    }
}
