<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The primary admin account created by the 2026_09_28 migration.
 */
class PrimaryAdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_28_000000_ensure_primary_admin_account.php';

    private function runMigration(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    public function test_the_admin_exists_once_with_a_hashed_password(): void
    {
        $rows = DB::table('users')->whereRaw('lower(email) = ?', ['admin@gmail.com'])->get();

        $this->assertCount(1, $rows);
        $this->assertSame('admin', $rows[0]->role);
        $this->assertSame('aktif', $rows[0]->status);
        $this->assertNotSame('1234', $rows[0]->password, 'Never stored in plain text.');
        $this->assertTrue(Hash::check('1234', $rows[0]->password));
    }

    public function test_admin_logs_in_with_any_email_casing_and_reaches_admin_endpoints(): void
    {
        $res = $this->postJson('/api/login', ['email' => 'Admin@gmail.com', 'password' => '1234'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.email', 'admin@gmail.com');

        $token = $res->json('token');
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('role', 'admin');
        $this->withToken($token)->getJson('/api/admin/users')->assertOk();
        $this->withToken($token)->getJson('/api/admin/reports')->assertOk();
        $this->withToken($token)->getJson('/api/admin/submissions')->assertOk();

        $this->postJson('/api/login', ['email' => 'ADMIN@GMAIL.COM', 'password' => '1234'])->assertOk();
        $this->postJson('/api/login', ['email' => 'admin@gmail.com', 'password' => '12345'])->assertUnprocessable();
    }

    public function test_plain_users_and_owners_are_refused_by_the_backend(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();

        foreach (['user', 'owner'] as $role) {
            $token = User::factory()->create(['role' => $role])->createToken('t')->plainTextToken;
            $this->withToken($token)->getJson('/api/admin/users')->assertForbidden();
            $this->withToken($token)->getJson('/api/admin/reports')->assertForbidden();
        }
    }

    public function test_running_again_never_duplicates_and_takes_over_an_existing_account(): void
    {
        // Someone registered the address as a normal user, in another casing.
        DB::table('users')->whereRaw('lower(email) = ?', ['admin@gmail.com'])->delete();
        $squatter = User::factory()->create(['email' => 'Admin@Gmail.com', 'role' => 'user', 'password' => 'miliksaya']);
        $squatter->createToken('lama');
        $squatter->delete();

        $this->runMigration();
        $this->runMigration();

        $rows = DB::table('users')->whereRaw('lower(email) = ?', ['admin@gmail.com'])->get();
        $this->assertCount(1, $rows);
        $this->assertSame($squatter->id, $rows[0]->id);
        $this->assertSame('admin', $rows[0]->role);
        $this->assertNull($rows[0]->deleted_at);
        $this->assertTrue(Hash::check('1234', $rows[0]->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
