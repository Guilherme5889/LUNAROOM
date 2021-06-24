<?php

namespace Tests\Feature\App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Mockery as m;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $course;
    private $module;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->admin = User::factory()->create(['admin' => '1']);
        $this->course = Course::factory()->create();
        $this->module = Module::create(['course_id' => $this->course->id, 'name' => 'Module_Test']);
    }

    public function test_admin_can_see_create_course_view()
    {
        //Prepare
        $user = User::factory()->create(['admin' => '1']);
        $this->actingAs($user);

        //Act
        $response = $this->get(route('add-course'));

        //Assert
        $response->assertOk();
        $response->assertSee('Cadastrar Novo Curso');
    }


    public function test_admin_can_create_course()
    {
        //Prepare
        Storage::fake('public');

        $user = User::factory()->create(['admin' => '1']);

        $file = UploadedFile::fake()->image('test.jpg');

        $payload = [
            'title' => 'Title',
            'description' => 'Descrição',
            'image' => $file,
            'price' => '300'
        ];

        Storage::shouldReceive('disk')
            ->once()
            ->with(m::type('string'))
            ->andReturnSelf();

        Storage::shouldReceive('putFileAs')
            ->once()
            ->with(m::type('string'), $file, m::type('string'));

        //Act
        $this->actingAs($user);
        $response = $this->post(route('post-course'), $payload);

        //Assert
        $response->assertRedirect(route('courses'));

    }

    public function test_admin_can_see_courses_page()
    {
        // Prepare
        $user = User::factory()->create(['admin' => '1']);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('courses'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('courses');
    }

    public function test_admin_can_see_edit_course_page()
    {
        // Prepare
        $user = User::factory()->create(['admin' => '1']);
        $course = Course::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('edit-course', $course->id));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Gerenciar Curso');
    }

    public function test_admin_can_update_course()
    {
        // Prepare
        Storage::fake('public');

        $user = User::factory()->create(['admin' => '1']);
        $course = Course::factory()->create();

        $file = UploadedFile::fake()->image('test.jpg');

        $this->actingAs($user);

        $payload = [
            'title' => 'NewTitle',
            'description' => 'NewDescription',
            'image' => $file,
            'price' => '300'
        ];

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
        $response = $this->put(route('put-course', $course->id), $payload);

        //Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => $payload["title"],
            'description' => $payload["description"]
        ]);

    }

    public function test_admin_can_delete_course()
    {
        // Prepare
        Storage::fake('public');

        $user = User::factory()->create(['admin' => '1']);
        $this->actingAs($user);

        Storage::shouldReceive('disk')
            ->once()
            ->with(m::type('string'))
            ->andReturnSelf();

        Storage::shouldReceive('delete')
            ->once()
            ->withAnyArgs();

        // Act
        $response = $this->get(route('delete-course', $this->course->id));

        //Assert
        $response->assertRedirect(route('courses'));
        $this->assertDatabaseMissing('courses', [
            'title' => $this->course->title
        ]);
    }

    public function test_admin_can_add_course_to_user_when_user_not_have_course()
    {
        // Prepare
        $user = User::factory()->create();
        $this->actingAs($this->admin);

        $payload = [
            'user_id' => $user->id,
            'course_id' => $this->course->id
        ];

        // Act
        $response = $this->post(route('add-course-to-user'), $payload);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('users_courses', [
            'user_id' => $user->id,
            'course_id' => $this->course->id
        ]);
    }

    public function test_admin_can_not_add_course_to_user_when_user_have_course()
    {
        // Prepare
        $user = User::factory()->create();
        $user->courses()->attach(['course_id' => $this->course->id]);

        $this->actingAs($this->admin);

        $payload = [
            'user_id' => $user->id,
            'course_id' => $this->course->id
        ];

        // Act
        $response = $this->post(route('add-course-to-user'), $payload);

        // Assert
        $response->assertStatus(302);
        $response->assertJson(['message' => 'User already owns the course']);
    }

    public function test_client_should_see_your_courses()
    {
        // Prepare
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->courses()->sync(['course_id' => $this->course->id]);

        // Act
        $response = $this->get(route('my-courses'));

        // Assert
        $response->assertViewHas('courses');
    }




}
