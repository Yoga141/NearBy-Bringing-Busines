<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'status', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Profile photo URL, or null when the user hasn't uploaded one (every
     * avatar in the UI then falls back to the initial).
     *
     * Served through an API route keyed by the stored filename rather than a
     * `/storage/...` link, for two reasons: the public disk is only reachable
     * from the web after `php artisan storage:link` (a symlink that silently
     * fails on many Windows and shared-hosting setups), and an <img> cannot
     * send the Bearer token, so the URL has to work unauthenticated. The
     * filename is the 40-character random name Laravel assigns on upload, so
     * it is unguessable and exposes no user ids.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->avatar_path) {
                return null;
            }

            return '/api/avatar/'.basename($this->avatar_path);
        });
    }

    /** Remove the current photo from disk; safe when there is none. */
    public function deleteAvatarFile(): void
    {
        if ($this->avatar_path) {
            Storage::disk('public')->delete($this->avatar_path);
        }
    }

    /** UMKM owned by this user (owner role). */
    public function umkms(): HasMany
    {
        return $this->hasMany(Umkm::class, 'owner_id');
    }

    /** Reviews written by this user. */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** UMKM this user marked as favorite. */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Umkm::class, 'favorites')->withTimestamps();
    }
}
