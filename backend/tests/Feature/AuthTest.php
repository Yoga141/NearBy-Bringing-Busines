<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_registers_and_receives_a_working_token(): void
    {
        $res = $this->postJson('/api/register', [
            'name' => '  Rina  ',
            'email' => 'Rina@Example.TEST',
            'password' => 'rahasia123',
        ]);

        $res->assertCreated()
            ->assertJsonPath('user.name', 'Rina')
            ->assertJsonPath('user.email', 'rina@example.test')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonPath('user.status', 'aktif');

        $this->withToken($res->json('token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', 'rina@example.test');
    }

    public function test_an_owner_registers_as_waiting_for_verification(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Pemilik Baru',
            'email' => 'owner@example.test',
            'password' => 'rahasia123',
            'role' => 'owner',
        ])->assertCreated()
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.status', 'menunggu');
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Penyusup',
            'email' => 'evil@example.test',
            'password' => 'rahasia123',
            'role' => 'admin',
        ])->assertStatus(422)->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'evil@example.test']);
    }

    public function test_an_email_differing_only_in_case_is_a_duplicate(): void
    {
        User::factory()->create(['email' => 'budi@example.test']);

        $this->postJson('/api/register', [
            'name' => 'Budi Lagi',
            'email' => 'BUDI@example.test',
            'password' => 'rahasia123',
        ])->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Email ini sudah terdaftar. Silakan masuk.');
    }

    public function test_register_validation_messages_are_indonesian(): void
    {
        $this->postJson('/api/register', ['name' => '', 'email' => 'bukan-email', 'password' => '123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Nama wajib diisi.')
            ->assertJsonPath('errors.password.0', 'Kata sandi minimal 8 karakter.');
    }

    public function test_login_is_case_insensitive_on_email(): void
    {
        User::factory()->create(['email' => 'sari@example.test', 'password' => 'rahasia123']);

        $this->postJson('/api/login', ['email' => ' SARI@example.test ', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);
    }

    public function test_wrong_password_and_unknown_email_get_the_same_answer(): void
    {
        User::factory()->create(['email' => 'sari@example.test', 'password' => 'rahasia123']);

        $wrongPassword = $this->postJson('/api/login', ['email' => 'sari@example.test', 'password' => 'salah-sekali']);
        $unknownEmail = $this->postJson('/api/login', ['email' => 'siapa@example.test', 'password' => 'rahasia123']);

        $wrongPassword->assertStatus(422)->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
        $unknownEmail->assertStatus(422)->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
    }

    public function test_a_deactivated_account_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'off@example.test', 'password' => 'rahasia123', 'status' => 'nonaktif']);

        $this->postJson('/api/login', ['email' => 'off@example.test', 'password' => 'rahasia123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Akun ini dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('laptop')->plainTextToken;
        $other = $user->createToken('hp')->plainTextToken;

        $this->withToken($current)->postJson('/api/logout')->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        // Forget the guard's cached user between requests, so it can't mask
        // the revoked token (in a real app every request starts fresh).
        $this->app['auth']->forgetGuards();
        $this->withToken($current)->getJson('/api/me')->assertUnauthorized()
            ->assertJsonPath('message', 'Sesi kamu sudah berakhir. Silakan masuk kembali.');
        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/me')->assertOk();
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        User::factory()->create(['email' => 'sari@example.test', 'password' => 'rahasia123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'sari@example.test', 'password' => 'tebak-'.$i])->assertStatus(422);
        }

        $this->postJson('/api/login', ['email' => 'sari@example.test', 'password' => 'rahasia123'])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Terlalu banyak percobaan masuk. Tunggu 1 menit lalu coba lagi.');
    }

    public function test_the_seeded_demo_accounts_can_log_in(): void
    {
        $this->seed();

        foreach ([
            ['admin@nearby.id', 'admin12345', 'admin'],
            ['pemilik@nearby.id', 'pemilik12345', 'owner'],
            ['pengguna@nearby.id', 'pengguna12345', 'user'],
        ] as [$email, $password, $role]) {
            $this->postJson('/api/login', ['email' => $email, 'password' => $password])
                ->assertOk()
                ->assertJsonPath('user.role', $role);
        }
    }
}
