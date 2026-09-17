<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProblemReportResource;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\UmkmResource;
use App\Http\Resources\UserResource;
use App\Models\ProblemReport;
use App\Models\Review;
use App\Models\Submission;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /** Manage users. */
    public function users()
    {
        return UserResource::collection(User::orderBy('name')->get());
    }

    /** Activate/deactivate a user account (not for admins, and never a delete). */
    public function toggleUserStatus(User $user)
    {
        abort_if($user->role === 'admin', 403, 'Tidak bisa menonaktifkan akun admin.');

        $user->update(['status' => $user->status === 'nonaktif' ? 'aktif' : 'nonaktif']);

        // Blocking sign-in is not enough on its own: an already-issued token
        // would keep the deactivated account signed in until it expires, so
        // drop every token it holds.
        if ($user->status === 'nonaktif') {
            $user->tokens()->delete();
        }

        return new UserResource($user);
    }

    /** All UMKM (including pending verification). */
    public function umkms()
    {
        return UmkmResource::collection(Umkm::latest()->get());
    }

    /** Show/hide an already-approved UMKM from the public site without rejecting or deleting it. */
    public function toggleHidden(Umkm $umkm)
    {
        $umkm->update(['hidden' => ! $umkm->hidden]);

        return new UmkmResource($umkm);
    }

    /** Verification queue. */
    public function submissions()
    {
        return SubmissionResource::collection(
            Submission::with('owner')->where('status', 'menunggu')->latest()->get()
        );
    }

    /** Approve a submission → the UMKM becomes verified/visible. */
    public function approve(Submission $submission)
    {
        $submission->update(['status' => 'disetujui']);

        if ($submission->umkm) {
            $submission->umkm->update(['verification' => 'disetujui']);
        }
        if ($submission->owner && $submission->owner->status === 'menunggu') {
            $submission->owner->update(['status' => 'aktif']);
        }

        return new SubmissionResource($submission->fresh('owner'));
    }

    /** Reject a submission. */
    public function reject(Submission $submission)
    {
        $submission->update(['status' => 'ditolak']);

        if ($submission->umkm) {
            $submission->umkm->update(['verification' => 'ditolak']);
        }

        return new SubmissionResource($submission->fresh('owner'));
    }

    /** Aggregate report stats. */
    public function reports()
    {
        return response()->json([
            'stats' => [
                'umkmCount' => Umkm::count(),
                'userCount' => User::count(),
                'reviewCount' => Review::count(),
                'avgRating' => round((float) Umkm::avg('rating'), 1),
            ],
            'byCategory' => Umkm::selectRaw('category, count(*) as total')
                ->groupBy('category')->pluck('total', 'category'),
            'byLocation' => Umkm::selectRaw('location, count(*) as total')
                ->groupBy('location')->pluck('total', 'location'),
            'growth' => $this->monthlyGrowth(),
        ]);
    }

    /**
     * UMKM registrations per month for the last 6 months (this month
     * included). Computed in PHP rather than a DB-specific date-group
     * query so it works the same on sqlite (local/dev) and MySQL (prod).
     *
     * @return array<int, array{label: string, val: int}>
     */
    private function monthlyGrowth(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $counts = Umkm::query()
            ->selectRaw('created_at')
            ->get()
            ->groupBy(fn ($u) => $u->created_at?->format('Y-m'));

        return $months->map(fn ($m) => [
            'label' => $m->translatedFormat('M'),
            'val' => $counts->get($m->format('Y-m'), collect())->count(),
        ])->all();
    }

    /** Soft-deleted users and UMKM (Trash). */
    public function trash()
    {
        return response()->json([
            'users' => UserResource::collection(User::onlyTrashed()->get()),
            'umkms' => UmkmResource::collection(Umkm::onlyTrashed()->get()),
        ]);
    }

    /** Restore a soft-deleted UMKM or user. ?type=umkm|user */
    public function restore(Request $request, int $id)
    {
        $type = $request->query('type', 'umkm');

        if ($type === 'user') {
            User::onlyTrashed()->findOrFail($id)->restore();
        } else {
            Umkm::onlyTrashed()->findOrFail($id)->restore();
        }

        return response()->json(['message' => 'Berhasil dipulihkan.']);
    }

    /** Bug/issue reports sent via the help widget. */
    public function problemReports()
    {
        return ProblemReportResource::collection(ProblemReport::latest()->get());
    }

    /** Move a problem report through Baru → Ditinjau → Selesai. */
    public function updateProblemReportStatus(Request $request, ProblemReport $problemReport)
    {
        $labelToValue = ['Baru' => 'baru', 'Ditinjau' => 'ditinjau', 'Selesai' => 'selesai'];

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($labelToValue))],
        ]);

        $problemReport->update(['status' => $labelToValue[$data['status']]]);

        return new ProblemReportResource($problemReport);
    }
}
