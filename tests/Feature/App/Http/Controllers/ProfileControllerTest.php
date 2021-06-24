<?php

namespace Tests\Feature\App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Mockery as m;
use \Illuminate\Support\Facades\Storage;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;


    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function test_user_can_see_view_user_profile()
    {
        // Prepare
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('config-user-profile'));

        // Assert
        $response->assertViewIs('campus.profile.index');
        $response->assertSee('Editar Meus Dados Pessoais');
    }

    public function test_user_can_see_view_config_user_public_profile()
    {
        // Prepare
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('config-public-profile'));

        // Assert
        $response->assertViewIs('campus.profile.config-public-profile');
        $response->assertSee('Configurar Perfil Público');
    }

    public function test_user_can_create_public_profile()
    {
        // Prepare
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('create-public-profile'));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id
        ]);

    }

    public function test_admin_can_update_public_profile()
    {
        // Prepare
        Storage::fake();

        $user = User::factory()->create();
        $profile = $user->profile()->create(['image' => 'test.jpg']);

        $this->actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg');

        Storage::shouldReceive('disk')
            ->once()
            ->with(m::type('string'))
            ->andReturnSelf();

        Storage::shouldReceive('delete')
            ->once()
            ->withAnyArgs();

        Storage::shouldReceive('disk')
            ->once()
            ->with(m::type('string'))
            ->andReturnSelf();

        Storage::shouldReceive('putFileAs')
            ->once()
            ->with(m::type('string'), $file, m::type('string'));

        // Act
        $payload = [
            'image' => $file,
            'github_url' => 'https://github.com'
        ];

        $response = $this->put(route('update-public-profile'), $payload);

        // Assert
        $response->assertRedirect(route('config-public-profile'));
        $response->assertSessionHas('message');
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'github_url' => 'https://github.com'
        ]);
    }


}
