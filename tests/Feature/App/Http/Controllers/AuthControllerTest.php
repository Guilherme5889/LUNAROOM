<?php


namespace Tests\Feature\App\Http\Controllers;


use App\Mail\GreetingsRegister;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testViewLogin()
    {
        $response = $this->get(route('login'));

        $response->assertViewIs('auth.login');
        $response->assertSee('Login');
    }

    public function testViewRegister()
    {
        $response = $this->get(route('register'));

        $response->assertViewIs('auth.register');
        $response->assertSee('Registre-se');
    }

    public function test_client_can_see_login_page()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_client_can_not_see_login_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('welcome'));
    }

    public function test_client_should_authenticate()
    {
        $user = User::factory()->create();

        $payload = [
            'email' => $user->email,
            'password' => 'password'
        ];

        $response = $this->post(route('post.login'), $payload);

        $response->assertStatus(200);
    }

    public function test_client_can_not_should_authenticate()
    {
        $user = User::factory()->create();

        $payload = [
            'email' => $user->email,
            'password' => 'incorrect_password'
        ];

        $response = $this->post(route('post.login'), $payload);

        $response->assertStatus(401);
    }

    public function test_client_should_register()
    {
        Mail::fake();

        $payload = [
            'name' => 'TestUser',
            'username' => 'username',
            'email' => 'test@gmail.com',
            'password' => 'password'
        ];

        $response = $this->post(route('post.register'), $payload)->assertStatus(200);


        $this->assertDatabaseHas('users', [
            'name' => $payload["name"],
            'email' => $payload["email"]
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $response->decodeResponseJson()["id"]
        ]);

        Mail::assertSent(GreetingsRegister::class, function ($email) use ($payload) {
            return $email->hasTo($payload["email"]);
        });
    }

    public function test_client_can_not_register()
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Guilherme',
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'incorrect_password'
        ];

        $response = $this->post(route('post.register'), $payload);

        $response->assertStatus(302);
    }

    public function test_client_can_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertRedirect(route('login'));
    }



}
