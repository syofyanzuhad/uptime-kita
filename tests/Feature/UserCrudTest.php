<?php

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
});

describe('User CRUD Operations', function () {

    describe('Index', function () {
        it('can list all users for authenticated user', function () {
            User::factory()->count(5)->create();

            $response = actingAs($this->user)->get('/users');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('users.data')
            );
        });

        it('includes monitor and status page counts', function () {
            $testUser = User::factory()->create();
            $monitor = Monitor::factory()->create();
            $monitor->users()->attach($testUser->id);
            StatusPage::factory()->create(['user_id' => $testUser->id]);

            $response = actingAs($this->user)->get('/users');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('users.data')
            );
        });

        it('paginates users', function () {
            User::factory()->count(15)->create();

            $response = actingAs($this->user)->get('/users');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('users.data', 10)
                ->has('users.links')
            );
        });

        it('requires authentication', function () {
            $response = get('/users');

            $response->assertRedirect('/login');
        });
    });

    describe('Create', function () {
        it('can show create form for authenticated user', function () {
            $response = actingAs($this->user)->get('/users/create');

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Create')
            );
        });

        it('requires authentication to show create form', function () {
            $response = get('/users/create');

            $response->assertRedirect('/login');
        });
    });

    describe('Store', function () {
        it('can create a new user', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = actingAs($this->admin)->postJson('/users', $userData);

            $response->assertRedirect('/users');

            assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            $newUser = User::where('email', 'john@example.com')->first();
            expect(password_verify('password123', $newUser->password))->toBeTrue();
        });

        it('validates required fields when creating user', function () {
            $response = actingAs($this->admin)->postJson('/users', []);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['name', 'email', 'password']);
        });

        it('validates email format', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'not-an-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = actingAs($this->admin)->postJson('/users', $userData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['email']);
        });

        it('validates unique email', function () {
            $existingUser = User::factory()->create(['email' => 'existing@example.com']);

            $userData = [
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = actingAs($this->admin)->postJson('/users', $userData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['email']);
        });

        it('validates password confirmation', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different-password',
            ];

            $response = actingAs($this->admin)->postJson('/users', $userData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['password']);
        });

        it('validates minimum password length', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ];

            $response = actingAs($this->admin)->postJson('/users', $userData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['password']);
        });

        it('requires authentication to create user', function () {
            $userData = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = postJson('/users', $userData);

            $response->assertUnauthorized();
        });
    });

    describe('Show', function () {
        it('can view a user', function () {
            $viewUser = User::factory()->create();

            $response = actingAs($this->user)->get("/users/{$viewUser->id}");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Show')
                ->has('user')
                ->where('user.id', $viewUser->id)
            );
        });

        it('requires authentication to view user', function () {
            $viewUser = User::factory()->create();

            $response = get("/users/{$viewUser->id}");

            $response->assertRedirect('/login');
        });
    });

    describe('Edit', function () {
        it('can show edit form for user', function () {
            $editUser = User::factory()->create();

            $response = actingAs($this->admin)->get("/users/{$editUser->id}/edit");

            $response->assertSuccessful();
            $response->assertInertia(fn ($page) => $page
                ->component('users/Edit')
                ->has('user')
                ->where('user.id', $editUser->id)
            );
        });

        it('cannot edit default admin user', function () {
            // User with ID 1 is the first user created and is treated as default admin
            $response = actingAs($this->admin)->get('/users/1/edit');

            $response->assertRedirect('/users');
            $response->assertSessionHas('error', 'Cannot edit the default admin user.');
        });

        it('requires authentication to show edit form', function () {
            $editUser = User::factory()->create();

            $response = get("/users/{$editUser->id}/edit");

            $response->assertRedirect('/login');
        });
    });

    describe('Update', function () {
        it('can update a user', function () {
            $updateUser = User::factory()->create([
                'name' => 'Old Name',
                'email' => 'old@example.com',
            ]);

            $updateData = [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertRedirect('/users');

            assertDatabaseHas('users', [
                'id' => $updateUser->id,
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);
        });

        it('can update user with password', function () {
            $updateUser = User::factory()->create();

            $updateData = [
                'name' => $updateUser->name,
                'email' => $updateUser->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertRedirect('/users');

            $updateUser->refresh();
            expect(password_verify('newpassword123', $updateUser->password))->toBeTrue();
        });

        it('can update user without changing password', function () {
            $updateUser = User::factory()->create();
            $oldPassword = $updateUser->password;

            $updateData = [
                'name' => 'Updated Name',
                'email' => $updateUser->email,
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertRedirect('/users');

            $updateUser->refresh();
            expect($updateUser->password)->toBe($oldPassword);
            expect($updateUser->name)->toBe('Updated Name');
        });

        it('cannot update default admin user', function () {
            // User with ID 1 is the first user created and is treated as default admin
            $updateData = [
                'name' => 'Hacked Admin',
                'email' => 'hacked@example.com',
            ];

            $response = actingAs($this->admin)->putJson('/users/1', $updateData);

            $response->assertRedirect('/users');
            $response->assertSessionHas('error', 'Cannot edit the default admin user.');
        });

        it('validates unique email on update', function () {
            $existingUser = User::factory()->create(['email' => 'existing@example.com']);
            $updateUser = User::factory()->create();

            $updateData = [
                'name' => 'Updated Name',
                'email' => 'existing@example.com',
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['email']);
        });

        it('allows user to keep their own email on update', function () {
            $updateUser = User::factory()->create(['email' => 'myemail@example.com']);

            $updateData = [
                'name' => 'Updated Name',
                'email' => 'myemail@example.com',
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertRedirect('/users');

            assertDatabaseHas('users', [
                'id' => $updateUser->id,
                'email' => 'myemail@example.com',
            ]);
        });

        it('validates password confirmation on update', function () {
            $updateUser = User::factory()->create();

            $updateData = [
                'name' => $updateUser->name,
                'email' => $updateUser->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'different-password',
            ];

            $response = actingAs($this->admin)->putJson("/users/{$updateUser->id}", $updateData);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors(['password']);
        });

        it('requires authentication to update user', function () {
            $updateUser = User::factory()->create();

            $updateData = [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ];

            $response = putJson("/users/{$updateUser->id}", $updateData);

            $response->assertUnauthorized();
        });
    });

    describe('Delete', function () {
        it('can delete a user', function () {
            $deleteUser = User::factory()->create();

            $response = actingAs($this->admin)->deleteJson("/users/{$deleteUser->id}");

            $response->assertRedirect('/users');
            assertDatabaseMissing('users', ['id' => $deleteUser->id]);
        });

        it('cannot delete default admin user', function () {
            // User with ID 1 is the first user created and is treated as default admin
            $response = actingAs($this->admin)->deleteJson('/users/1');

            $response->assertRedirect('/users');
            assertDatabaseHas('users', ['id' => 1]);
        });

        it('cannot delete user with associated monitors', function () {
            $userWithMonitor = User::factory()->create();
            $monitor = Monitor::factory()->create(['uptime_check_enabled' => true]);
            $monitor->users()->attach($userWithMonitor->id);

            $response = actingAs($this->admin)->deleteJson("/users/{$userWithMonitor->id}");

            $response->assertRedirect('/users');
            assertDatabaseHas('users', ['id' => $userWithMonitor->id]);
        });

        it('cannot delete user with associated status pages', function () {
            $userWithStatusPage = User::factory()->create();
            StatusPage::factory()->create(['user_id' => $userWithStatusPage->id]);

            $response = actingAs($this->admin)->deleteJson("/users/{$userWithStatusPage->id}");

            $response->assertRedirect('/users');
            assertDatabaseHas('users', ['id' => $userWithStatusPage->id]);
        });

        it('requires authentication to delete user', function () {
            $deleteUser = User::factory()->create();

            $response = deleteJson("/users/{$deleteUser->id}");

            $response->assertUnauthorized();
        });
    });
});
