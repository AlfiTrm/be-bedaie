<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_current_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_user_can_register_and_receive_bearer_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Alfi',
            'email' => 'alfi@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alfi@example.com',
        ]);
    }

    public function test_user_can_login_and_receive_bearer_token(): void
    {
        User::factory()->create([
            'name' => 'Alfi',
            'email' => 'alfi@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'alfi@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ]);
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_authenticated_user_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $logoutResponse
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
