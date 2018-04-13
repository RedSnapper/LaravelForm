<?php

namespace Tests;

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Formlets\UserFormlet;
use Tests\Fixtures\Formlets\UserProfileFormlet;
use Tests\Fixtures\Formlets\UserRoleFormlet;
use Tests\Fixtures\Models\Role;
use Tests\Fixtures\Models\User;

class FormletIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // call migrations specific to our tests, e.g. to seed the db
        // the path option should be an absolute path.
        $this->loadMigrationsFrom(realpath(__DIR__ . '/Fixtures/database/migrations'));
        $this->withFactories(realpath(__DIR__ . '/Fixtures/database/factories'));
    }

    /** @test */
    public function test_store_method()
    {
        Route::post('/users', function (UserFormlet $formlet, User $model) {
            return $formlet->model($model)->store();
        });

        $this->post('/users', ['email' => 'john@example.com'])
          ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    /** @test */
    public function test_update_method()
    {

        User::create(['email' => 'john@example.com']);

        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'john@example.com']);

        Route::put('/users/{user}', function ($user, UserFormlet $formlet) {
            $user = User::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', ['email' => 'james@example.com'])
          ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'james@example.com']);
    }

    /** @test */
    public function test_many_to_many_associations_store()
    {
        $this->withoutExceptionHandling();
        $adminRole = Role::create(['name' => 'Admin']);
        $userRole = Role::create(['name' => 'User']);
        $editorRole = Role::create(['name' => 'Editor']);

        Route::post('/users', function (UserRoleFormlet $formlet, User $user) {
            return $formlet->model($user)->store();
        });

        $this->post('/users', ['email' => 'james@example.com','roles'=>[$adminRole->id,$userRole->id]])
          ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'james@example.com']);
        $this->assertDatabaseHas('role_user', ['user_id' => 1, 'role_id' => $adminRole->id]);
        $this->assertDatabaseHas('role_user', ['user_id' => 1, 'role_id' => $userRole->id]);
        $this->assertDatabaseMissing('role_user', ['user_id' => 1, 'role_id' => $editorRole->id]);

    }

    /** @test */
    public function formlet_has_one_relation()
    {

        $user = User::create(['email' => 'john@example.com']);
        $user->assignProfile(['name' => 'John']);

        $form = app(UserProfileFormlet::class);
        $form->model($user)->build();

        $fields = $form->fields();
        $profileFormlet = $form->formlet('profile');

        $this->assertEquals('john@example.com', $fields->get('email')->getValue());
        $this->assertEquals('John', $profileFormlet->fields()->get('name')->getValue());
    }

    /** @test */
    public function formlet_has_one_relation_store_method()
    {
        $this->withoutExceptionHandling();
        Route::post('/users', function (UserProfileFormlet $formlet, User $model) {
            return $formlet->model($model)->store();
        });

        $this->post('/users', [
          'email'   => 'john@example.com',
          'profile' => [['name' => 'John']]
        ])->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => 1, 'name' => 'John']);
    }

    /** @test */
    public function formlet_has_one_relation_update_method()
    {
        $this->withoutExceptionHandling();

        $user = User::create(['email' => 'john@example.com']);
        $user->assignProfile(['name' => 'John']);

        Route::put('/users/{user}', function ($user, UserProfileFormlet $formlet) {
            $user = User::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'profile' => [['name' => 'James']]
        ])->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'james@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => 1, 'name' => 'James']);
    }

}



