<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\GuideVideoController;
use App\Http\Controllers\Api\OwnerController;
use App\Http\Controllers\Api\ProblemReportController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SocialVideoController;
use App\Http\Controllers\Api\UmkmController;
use App\Http\Controllers\Api\UmkmPortController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| UMKM (public read)
|--------------------------------------------------------------------------
*/
Route::get('/umkm', [UmkmController::class, 'index']);
Route::get('/umkm/{umkm}', [UmkmController::class, 'show']);
Route::get('/umkm/{umkm}/reviews', [ReviewController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Social videos (public read — homepage "Video dari medsos" section)
|--------------------------------------------------------------------------
*/
Route::get('/social-videos', [SocialVideoController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Guide video (public read — the tutorial on /panduan)
|--------------------------------------------------------------------------
*/
Route::get('/guide-video', [GuideVideoController::class, 'current']);
Route::get('/guide-video/{guideVideo}/file', [GuideVideoController::class, 'stream']);

/*
|--------------------------------------------------------------------------
| Help widget (public — works for guests too)
|--------------------------------------------------------------------------
*/
Route::post('/problem-reports', [ProblemReportController::class, 'store']);
Route::post('/questions', [QuestionController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Authenticated (Sanctum token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/reviews', [ReviewController::class, 'mine']);

    // UMKM write (owner)
    Route::post('/umkm', [UmkmController::class, 'store']);
    Route::put('/umkm/{umkm}', [UmkmController::class, 'update']);
    Route::delete('/umkm/{umkm}', [UmkmController::class, 'destroy']);

    // Excel export / import. Deliberately NOT under /umkm/*, because the
    // public `GET /umkm/{umkm}` route is registered first and would swallow
    // `/umkm/export` as an id lookup.
    Route::prefix('umkm-excel')->group(function () {
        Route::get('/export', [UmkmPortController::class, 'export']);
        Route::post('/preview', [UmkmPortController::class, 'preview']);
        Route::post('/commit', [UmkmPortController::class, 'commit']);
    });

    // Reviews
    Route::post('/umkm/{umkm}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::post('/reviews/{review}/reply', [ReviewController::class, 'reply']);

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/umkm/{umkm}/favorite', [FavoriteController::class, 'toggle']);

    // Owner dashboard
    Route::prefix('owner')->group(function () {
        Route::get('/summary', [OwnerController::class, 'summary']);
        Route::get('/umkm', [OwnerController::class, 'umkms']);
        Route::get('/reviews', [OwnerController::class, 'reviews']);
        Route::get('/trash', [OwnerController::class, 'trash']);
        Route::post('/umkm/{id}/restore', [OwnerController::class, 'restoreUmkm']);
        Route::delete('/umkm/{id}/force', [OwnerController::class, 'forceDeleteUmkm']);
        Route::delete('/trash', [OwnerController::class, 'emptyTrash']);
    });

    // Admin dashboard (role: admin)
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        Route::get('/umkm', [AdminController::class, 'umkms']);
        Route::post('/umkm/{umkm}/toggle-hidden', [AdminController::class, 'toggleHidden']);
        Route::get('/submissions', [AdminController::class, 'submissions']);
        Route::post('/submissions/{submission}/approve', [AdminController::class, 'approve']);
        Route::post('/submissions/{submission}/reject', [AdminController::class, 'reject']);
        Route::get('/reports', [AdminController::class, 'reports']);
        Route::get('/trash', [AdminController::class, 'trash']);
        Route::post('/trash/{id}/restore', [AdminController::class, 'restore']);
        Route::get('/problem-reports', [AdminController::class, 'problemReports']);
        Route::post('/problem-reports/{problemReport}/status', [AdminController::class, 'updateProblemReportStatus']);

        // Help widget: questions from the "Bertanya" tab
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions/{question}/answer', [QuestionController::class, 'answer']);
        Route::post('/questions/{question}/status', [QuestionController::class, 'updateStatus']);

        // Panduan page tutorial video (uploaded file, not a link)
        Route::get('/guide-videos/limits', [GuideVideoController::class, 'limits']);
        Route::get('/guide-videos', [GuideVideoController::class, 'index']);
        Route::post('/guide-videos', [GuideVideoController::class, 'store']);
        Route::put('/guide-videos/{guideVideo}', [GuideVideoController::class, 'update']);
        Route::delete('/guide-videos/{guideVideo}', [GuideVideoController::class, 'destroy']);

        // Homepage social-video slots
        Route::get('/social-videos', [SocialVideoController::class, 'adminIndex']);
        Route::post('/social-videos', [SocialVideoController::class, 'store']);
        Route::put('/social-videos/{socialVideo}', [SocialVideoController::class, 'update']);
        Route::delete('/social-videos/{socialVideo}', [SocialVideoController::class, 'destroy']);
    });
});
