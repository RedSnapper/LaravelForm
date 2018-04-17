<?php

namespace Tests;

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Formlets\UserFormlet;
use Tests\Fixtures\Formlets\UserPermissionForm;
use Tests\Fixtures\Formlets\UserPostsFormlet;
use Tests\Fixtures\Formlets\UserProfileFormlet;
use Tests\Fixtures\Formlets\UserRoleFormlet;
use Tests\Fixtures\Models\Permission;
use Tests\Fixtures\Models\Post;
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

        $this->post('/users', ['email' => 'james@example.com', 'roles' => [$adminRole->id, $userRole->id]])
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
        $user->assignProfile(['name' => 'John', 'active' => false]);

        $form = app(UserProfileFormlet::class);
        $form->model($user)->build();

        $fields = $form->fields();
        $profileFormlet = $form->formlet('profile');

        $this->assertEquals('john@example.com', $fields->get('email')->getValue());
        $this->assertEquals('John', $profileFormlet->fields()->get('name')->getValue());
        $this->assertFalse($profileFormlet->fields()->get('active')->getValue());
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
          'profile' => [['name' => 'John', 'active' => "1"]]
        ])->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('profiles', ['user_id' => 1, 'name' => 'John', 'active' => true]);
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

        tap($user->fresh(),function(User $user){
            $this->assertEquals("james@example.com",$user->email);
            $this->assertEquals("James",$user->profile->name);
        });
    }

    /** @test */
    public function has_many_relation()
    {

        $user = User::create(['email' => 'john@example.com']);
        $postA = $user->posts()->create(['name'=>'Post A']);
        $postB = $user->posts()->create(['name'=>'Post B']);

        $formlet = app(UserPostsFormlet::class);
        $formlet->model($user)->build();

        $this->assertCount(2,$formlet->formlets('posts'));
        $this->assertEquals($postA->name,$formlet->formlets('posts')->get(0)->field('name')->getValue());
        $this->assertEquals($postB->name,$formlet->formlets('posts')->get(1)->field('name')->getValue());

    }

    /** @test */
    public function has_many_create_method()
    {

        Route::post('/users', function (User $user, UserPostsFormlet $formlet) {
            return $formlet->model($user)->store();
        });

        $this->post('/users', [
          'email'   => 'john@example.com',
          'posts' => [
            ['name' => 'Post A'],
            ['name' => 'Post B']
          ]
        ])->assertStatus(200);

        tap(User::first(),function(User $user){
            $this->assertEquals("john@example.com",$user->email);
            $posts = $user->posts;
            $this->assertCount(2,$posts);
            $this->assertEquals("Post A",$posts->first()->name);
            $this->assertEquals("Post B",$posts->get(1)->name);
        });
    }

    /** @test */
    public function has_many_relation_update_method()
    {

        $user = User::create(['email' => 'john@example.com']);
        $user->posts()->create(['name'=>'Post A']);
        $user->posts()->create(['name'=>'Post B']);

        Route::put('/users/{user}', function ($user, UserPostsFormlet $formlet) {
            $user = User::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'posts' => [
            ['name' => 'Post A Updated'],
            ['name' => 'Post B Updated']
          ]
        ])->assertStatus(200);

        tap($user->fresh(),function(User $user){
            $this->assertEquals("james@example.com",$user->email);
            $posts = $user->posts;
            $this->assertCount(2,$posts);
            $this->assertEquals("Post A Updated",$posts->first()->name);
            $this->assertEquals("Post B Updated",$posts->get(1)->name);
        });
    }

    /** @test */
    public function many_to_many_relation()
    {

        $user = User::create(['email' => 'john@example.com']);
        $permissionA = Permission::create(['name'=>'Permission A']);
        $permissionB =  Permission::create(['name'=>'Permission B']);

        $user->permissions()->attach($permissionA->id,['color'=>'Red']);

        $formlet = app(UserPermissionForm::class);
        $formlet->model($user)->build();

        $formlets = $formlet->formlets('permissions');

        $this->assertCount(2, $formlets);
        $this->assertEquals($permissionA->name, $formlets->get(0)->getRelated()->name);
        $this->assertEquals($permissionB->name, $formlets->get(1)->getRelated()->name);

        $this->assertEquals("Red", $formlets->first()->getModel()->pivot->color);
        $this->assertNull($formlets->get(1)->getModel());

        $this->assertTrue($formlets->first()->field('id')->isChecked());
        $this->assertEquals('Red',$formlets->first()->field('color')->getValue());
        $this->assertFalse($formlets->get(1)->field('id')->isChecked());
        $this->assertNull($formlets->get(1)->field('color')->getValue());

    }

    /** @test */
    public function many_to_many_relation_store()
    {
        $this->withoutExceptionHandling();
        Permission::create(['name'=>'Permission A']);
        Permission::create(['name'=>'Permission B']);

        Route::post('/users', function (User $user, UserPermissionForm $formlet) {
            return $formlet->model($user)->store();
        });

        $this->post('/users', [
          'email'   => 'john@example.com',
          'permissions' => [
            ['id' => '1','color'=>'Red'],
            ['color'=>'Blue']
          ]
        ])->assertStatus(200);

        tap(User::first(),function(User $user){
            $this->assertEquals("john@example.com",$user->email);
            $permissions = $user->permissions;
            $this->assertCount(1,$permissions);
            $this->assertEquals("Permission A",$permissions->first()->name);
            $this->assertEquals("Red",$permissions->first()->pivot->color);
        });

    }

    /** @test */
    public function many_to_many_relation_update()
    {
        $user = User::create(['email' => 'john@example.com']);
        $permissionA = Permission::create(['name'=>'Permission A']);
        $permissionB =  Permission::create(['name'=>'Permission B']);

        $user->permissions()->attach($permissionA->id,['color'=>'Red']);

        Route::put('/users/{user}', function ($user, UserPermissionForm $formlet) {
            $user = User::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'permissions' => [
            ['color'=>'Red'],
            ['id' => '2','color'=>'Blue']
          ]
        ])->assertStatus(200);

        tap($user->fresh(),function(User $user){
            $this->assertEquals("james@example.com",$user->email);
            $permissions = $user->permissions;
            $this->assertCount(1,$permissions);
            $this->assertEquals("Permission B",$permissions->first()->name);
            $this->assertEquals("Blue",$permissions->first()->pivot->color);
        });

    }

    /** @test */
    public function retrieve_subscription_data()
    {

        app()->request->merge([
          'email'   => 'john@example.com',
          'permissions' => [
            ['id' => '1','color'=>'Red'],
            ['color'=>'Blue']
          ]
        ]);

        Permission::create(['name'=>'Permission A']);
        Permission::create(['name'=>'Permission B']);

        $form = app(UserPermissionForm::class);
        $user = new User();

        $form->model($user)->build();

        $this->assertEquals([1=>['color'=>"Red"]],$form->subscriptionData('permissions'));

    }

}



