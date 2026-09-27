<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Stored lowercase, like every email the app writes (see RegisterRequest). */
    private const EMAIL = 'admin@gmail.com';

    /**
     * Bcrypt hash (cost 12, made with Laravel's Hash::make) of the agreed
     * initial password. Only the hash is kept in the repository - never the
     * password itself - and it is what `users.password` stores for every account.
     */
    private const PASSWORD_HASH = '$2y$12$NUN1ETpFW5vlYYG4zTaSQuxDwYU/HXgUDDfkS5nE4w8cWnUTjFnGu';

    /**
     * The site's primary admin account.
     *
     * A migration rather than a seeder so it reaches production on its own:
     * .cpanel.yml runs `php artisan migrate --force` on every deploy, and a
     * migration runs exactly once per database. Changing the password later
     * from the account page is therefore never undone by a redeploy.
     *
     * Idempotent over what may already be there - matched case-insensitively:
     *  - no such account → created as an active admin;
     *  - an account with this email already exists (possibly registered by
     *    anyone, as a normal user) → it becomes the admin, gets this password,
     *    is restored if it was deleted, and every token it held is revoked, so
     *    whoever held it before cannot keep an admin session.
     */
    public function up(): void
    {
        $now = now();
        $existing = DB::table('users')->whereRaw('lower(email) = ?', [self::EMAIL])->orderBy('id')->first();

        if (! $existing) {
            DB::table('users')->insert([
                'name' => 'Admin NearBy',
                'email' => self::EMAIL,
                'password' => self::PASSWORD_HASH,
                'role' => 'admin',
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('users')->where('id', $existing->id)->update([
            'email' => self::EMAIL,
            'password' => self::PASSWORD_HASH,
            'role' => 'admin',
            'status' => 'aktif',
            'deleted_at' => null,
            'updated_at' => $now,
        ]);

        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\\Models\\User')
            ->where('tokenable_id', $existing->id)
            ->delete();
    }

    /** Nothing to undo safely: the account may be in use by now. */
    public function down(): void {}
};
