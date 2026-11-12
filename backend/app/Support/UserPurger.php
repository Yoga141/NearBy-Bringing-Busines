<?php

namespace App\Support;

use App\Models\ProblemReport;
use App\Models\Question;
use App\Models\Review;
use App\Models\Submission;
use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Models\UmkmPhoto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently erases an account and everything that belongs to it.
 *
 * Unlike the Trash (soft delete), nothing here can be restored:
 *  - every UMKM the user owns, trashed ones included, with their products,
 *    photos, reviews, favourites and verification submissions;
 *  - the user's own reviews on other UMKM (those UMKM get their rating
 *    recounted), favourites, help-desk questions and problem reports;
 *  - sign-in tokens and sessions;
 *  - the uploaded files: profile photo, UMKM photos and product photos.
 *
 * Files are removed only after the database transaction commits, so a
 * failure half-way never leaves rows pointing at deleted files.
 */
final class UserPurger
{
    /**
     * What a purge would remove, for the confirmation dialog.
     *
     * @return array{umkms: int, items: int, photos: int, reviewsReceived: int, reviewsWritten: int, favorites: int, questions: int, problemReports: int}
     */
    public function summary(User $user): array
    {
        $umkmIds = $this->umkmIds($user);

        return [
            'umkms' => count($umkmIds),
            'items' => UmkmItem::whereIn('umkm_id', $umkmIds)->count(),
            'photos' => UmkmPhoto::whereIn('umkm_id', $umkmIds)->count(),
            'reviewsReceived' => Review::whereIn('umkm_id', $umkmIds)->count(),
            'reviewsWritten' => Review::where('user_id', $user->id)->whereNotIn('umkm_id', $umkmIds)->count(),
            'favorites' => DB::table('favorites')->where('user_id', $user->id)->count(),
            'questions' => Question::where('user_id', $user->id)->count(),
            'problemReports' => ProblemReport::where('user_id', $user->id)->count(),
        ];
    }

    public function purge(User $user): void
    {
        $umkmIds = $this->umkmIds($user);

        // Collected up front: once the rows are gone the paths are too.
        $photoFiles = UmkmPhoto::whereIn('umkm_id', $umkmIds)
            ->whereNotNull('path')->whereNotNull('disk')
            ->get(['disk', 'path']);
        $itemImages = UmkmItem::whereIn('umkm_id', $umkmIds)->whereNotNull('img')->pluck('img');
        $avatar = $user->avatar_path;

        DB::transaction(function () use ($user, $umkmIds): void {
            // Reviews this user left on other UMKM: delete, then recount those ratings.
            $reviewedUmkmIds = Review::where('user_id', $user->id)
                ->whereNotIn('umkm_id', $umkmIds)
                ->distinct()->pluck('umkm_id');
            Review::where('user_id', $user->id)->delete();

            // The user's UMKM and every row hanging off them. Deleted
            // explicitly (not just via FK cascade) so SQLite without
            // foreign-key enforcement behaves the same as MySQL.
            Submission::where('owner_id', $user->id)->orWhereIn('umkm_id', $umkmIds)->delete();
            if ($umkmIds !== []) {
                Review::whereIn('umkm_id', $umkmIds)->delete();
                DB::table('favorites')->whereIn('umkm_id', $umkmIds)->delete();
                UmkmItem::whereIn('umkm_id', $umkmIds)->delete();
                UmkmPhoto::whereIn('umkm_id', $umkmIds)->delete();
                // Query builder on purpose: the model's forceDeleting hook
                // would delete files mid-transaction; we do that after commit.
                Umkm::withTrashed()->toBase()->whereIn('id', $umkmIds)->delete();
            }

            DB::table('favorites')->where('user_id', $user->id)->delete();
            Question::where('user_id', $user->id)->delete();
            ProblemReport::where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();

            $user->forceDelete();

            Umkm::whereIn('id', $reviewedUmkmIds)->get()->each->refreshRating();
        });

        $photoFiles->each(fn (UmkmPhoto $photo) => Storage::disk($photo->disk)->delete($photo->path));
        UmkmItem::deleteUnusedPhotos($itemImages);
        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }
    }

    /** @return list<int> Every UMKM the user owns, trashed ones included. */
    private function umkmIds(User $user): array
    {
        return Umkm::withTrashed()->where('owner_id', $user->id)->pluck('id')->all();
    }
}
