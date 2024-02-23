<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    
    protected function create_a_user($status = 1){
        $user = User::factory()->create([
            'email' => 'authenticateduser@mail.com',
            'password' => 'authenticated',
            'status' => $status
        ]);
        return $user;
    }

    public function test_login_should_return_error_if_email_or_password_is_not_set(): void
    {
        $response = $this->postJson('api/login', []);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors'=>['email', 'password']]);
    }

    public function test_login_should_return_error_if_email_is_wrong(): void
    {
        $user = $this->create_a_user();
        $response = $this->postJson('api/login', ['email'=>'unauthenticateduser@mail.com', 'password'=>'authenticated']);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors'=>['email']]);
    }

    public function test_login_should_return_error_if_password_is_wrong(): void
    {
        $user = $this->create_a_user();
        $response = $this->postJson('api/login', ['email'=>'authenticateduser@mail.com', 'password'=>'unauthenticated']);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors'=>['email']]);
    }

    public function test_login_should_return_an_error_if_user_is_disabled():void
    {
        $user = $this->create_a_user(0);
        $response = $this->postJson('api/login', ['email'=>'authenticateduser@mail.com', 'password'=>'authenticated']);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    public function test_login_should_return_a_success_if_everything_went_okay():void{
        $user = $this->create_a_user();
        $response = $this->postJson('api/login', ['email'=>'authenticateduser@mail.com', 'password'=>'authenticated']);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data'=>['id', 'token']]);
    }
}
