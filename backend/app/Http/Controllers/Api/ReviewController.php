<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Umkm;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reviews ("ulasan" / komentar) on a UMKM.
 *
 * One user has at most one review per UMKM. The first POST creates it; after
 * that the user edits it with PUT. The rule is enforced three times over: the
 * UI shows an edit form instead of a second "Kirim", the API answers 409 to a
 * second POST, and a unique index on (umkm_id, user_id) refuses the row even
 * if two requests race past the API check.
 */
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

    /** Admin: every review, newest first, for moderation. */
    public function adminIndex()
    {
        return ReviewResource::collection(
            Review::with(['umkm' => fn ($q) => $q->withTrashed()])->latest()->limit(500)->get()
        );
    }

    /** Authenticated user posts their (single) review of a UMKM. */
    public function store(Request $request, Umkm $umkm): ReviewResource|JsonResponse
    {
        $user = $request->user();

        // Only what the public can see can be reviewed.
        abort_unless($umkm->isVisible(), 404, 'UMKM ini belum tampil untuk umum.');
        abort_if($umkm->owner_id === $user->id, 403, 'Pemilik tidak bisa mengulas UMKM miliknya sendiri.');

        $data = $this->validated($request);

        $existing = Review::where('umkm_id', $umkm->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return $this->alreadyReviewed($existing);
        }

        try {
            $review = $umkm->reviews()->create([
                'user_id' => $user->id,
                'author_name' => $user->name,
                'stars' => $data['stars'],
                'text' => $data['text'],
            ]);
        } catch (UniqueConstraintViolationException) {
            // A parallel request won the race - same answer as the check above.
            return $this->alreadyReviewed(
                Review::where('umkm_id', $umkm->id)->where('user_id', $user->id)->firstOrFail()
            );
        }

        $umkm->refreshRating();

        return new ReviewResource($review);
    }

    /** The review's author edits their own stars/text. */
    public function update(Request $request, Review $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403, 'Hanya penulis ulasan yang bisa mengubahnya.');

        $data = $this->validated($request);
        $review->update($data);

        $review->umkm()->withTrashed()->first()?->refreshRating();

        return new ReviewResource($review);
    }

    /** The author deletes their review; an admin may delete any (moderation). */
    public function destroy(Request $request, Review $review)
    {
        $user = $request->user();
        abort_unless($review->user_id === $user->id || $user->isAdmin(), 403, 'Hanya penulis ulasan yang bisa menghapusnya.');

        $umkm = $review->umkm()->withTrashed()->first();
        $review->delete();
        $umkm?->refreshRating();

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
        ], [
            'reply.required' => 'Balasan tidak boleh kosong.',
        ]);

        $review->update(['reply' => trim($data['reply'])]);

        return new ReviewResource($review);
    }

    /**
     * @return array{stars: int, text: string}
     */
    private function validated(Request $request): array
    {
        if (is_string($request->input('text'))) {
            $request->merge(['text' => trim($request->input('text'))]);
        }

        return $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'text' => ['required', 'string', 'min:3', 'max:2000'],
        ], [
            'stars.required' => 'Pilih jumlah bintang terlebih dahulu.',
            'stars.min' => 'Rating minimal 1 bintang.',
            'stars.max' => 'Rating maksimal 5 bintang.',
            'text.required' => 'Komentar tidak boleh kosong.',
            'text.min' => 'Komentar minimal 3 karakter.',
            'text.max' => 'Komentar maksimal 2000 karakter.',
        ]);
    }

    private function alreadyReviewed(Review $existing): JsonResponse
    {
        return response()->json([
            'message' => 'Kamu sudah memberi ulasan untuk UMKM ini. Silakan edit ulasanmu.',
            'review' => new ReviewResource($existing),
        ], 409);
    }
}
