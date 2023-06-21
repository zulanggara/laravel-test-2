<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_screen_shows_welcome(): void
    {
        // Act
        $response = $this->get('/');

        // Assert
        $response->assertViewIs('welcome');
        $response->assertViewHas('pageTitle', 'Homepage');
    }

    public function test_user_page_existing_user_found(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->get('/user/' . $user->name);

        // Assert
        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    public function test_user_page_nonexisting_user_not_found(): void
    {
        // Act
        $response = $this->get('/user/sometotallynonexistinguser');

        // Assert
        $response->assertViewIs('users.notfound');
    }

    public function test_about_page_is_loaded(): void
    {
        // Act
        $response = $this->get('/about');

        // Assert
        $response->assertViewIs('pages.about');
    }

    public function test_auth_middleware_is_working(): void
    {
        // Act
        $response = $this->get('/app/dashboard');
        // Assert
        $response->assertRedirect('/login');

        // Act
        $response = $this->get('/app/tasks');
        // Assert
        $response->assertRedirect('/login');
    }


    public function test_task_crud_is_working(): void
    {
        // Arrange
        $user = User::factory()->create();
        // Act
        $response = $this->actingAs($user)->get('/app/tasks');
        // Assert
        $response->assertOk();

        // Act
        $response = $this->actingAs($user)->get('/app/tasks/create');
        // Assert
        $response->assertOk();

        // Act
        $response = $this->actingAs($user)->post('/app/tasks', ['name' => 'Test']);
        // Assert
        $response->assertRedirect('app/tasks');

        // Arrange
        $task = Task::factory()->create();
        // Act
        $response = $this->actingAs($user)->put('/app/tasks/' . $task->id, ['name' => 'Test 2']);
        // Assert
        $response->assertRedirect('app/tasks');
        $this->assertDatabaseHas(Task::class, ['name' => 'Test 2']);

        // Act
        $response = $this->actingAs($user)->delete('/app/tasks/' . $task->id);
        // Assert
        $response->assertRedirect('app/tasks');
        $this->assertDatabaseMissing(Task::class, ['name' => 'Test 2']);
    }

    public function test_task_api_crud_is_working(): void
    {
        // Arrange
        $user = User::factory()->create();
        // Act
        $response = $this->actingAs($user)->get('/api/v1/tasks');
        // Assert
        $response->assertOk();

        // Act
        $response = $this->actingAs($user)->post('/api/v1/tasks', ['name' => 'Test']);
        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas(Task::class, ['name' => 'Test']);

        // Arrange
        $task = Task::factory()->create();
        // Act
        $response = $this->actingAs($user)->put('/api/v1/tasks/' . $task->id, ['name' => 'Test 2']);
        // Assert
        $response->assertOk();
        $this->assertDatabaseHas(Task::class, ['name' => 'Test 2']);

        // Act
        $response = $this->actingAs($user)->delete('/api/v1/tasks/' . $task->id);
        $response->assertNoContent();
        // Assert
        $this->assertDatabaseMissing(Task::class, ['name' => 'Test 2']);
    }

    public function test_is_admin_middleware_is_working(): void
    {
        // Act
        $response = $this->get('/admin/dashboard');
        // Assert
        $response->assertRedirect('login');

        // Act
        $response = $this->get('/admin/stats');
        // Assert
        $response->assertRedirect('login');

        // Arrange
        $user = User::factory()->create();
        // Act
        $response = $this->actingAs($user)->get('/admin/dashboard');
        // Assert
        $response->assertStatus(403);

        // Act
        $response = $this->actingAs($user)->get('/admin/stats');
        // Assert
        $response->assertStatus(403);

        $admin = User::factory()->create(['is_admin' => 1]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        // Assert
        $response->assertViewIs('admin.dashboard');

        // Act
        $response = $this->actingAs($admin)->get('/admin/stats');
        // Assert
        $response->assertViewIs('admin.stats');
    }
}
