<?php

namespace RS\Form\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use RS\Form\Formlet;
use RS\Form\Tests\Fixtures\Formlets\TestPostFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestUserFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestUserPermissionForm;
use RS\Form\Tests\Fixtures\Formlets\TestUserPermissionFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestUserPostsFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestUserProfileFormlet;
use RS\Form\Tests\Fixtures\Formlets\TestUserRoleFormlet;
use RS\Form\Tests\Fixtures\Models\TestPermission;
use RS\Form\Tests\Fixtures\Models\TestRole;
use RS\Form\Tests\Fixtures\Models\TestUser;

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
    public function a_formlet_may_have_an_existing_model()
    {
        $formlet = $this->formlet();
        $formlet->build();

        $this->assertFalse($formlet->modelExists());

        $user = factory(TestUser::class)->create();

        $formlet->model($user);
        $this->assertTrue($formlet->modelExists());

    }

    /** @test */
    public function test_store_method()
    {
        Route::post('/users', function (TestUserFormlet $formlet, TestUser $model) {
            return $formlet->model($model)->store();
        });

        $this->post('/users', ['email' => 'john@example.com'])
          ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    /** @test */
    public function test_update_method()
    {

        TestUser::create(['email' => 'john@example.com']);

        $this->assertDatabaseHas('users', ['id' => 1, 'email' => 'john@example.com']);

        Route::put('/users/{user}', function ($user, TestUserFormlet $formlet) {
            $user = TestUser::find($user);
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
        $adminRole = TestRole::create(['name' => 'Admin']);
        $userRole = TestRole::create(['name' => 'User']);
        $editorRole = TestRole::create(['name' => 'Editor']);

        Route::post('/users', function (TestUserRoleFormlet $formlet, TestUser $user) {
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

        $user = TestUser::create(['email' => 'john@example.com']);
        $user->assignProfile(['name' => 'John', 'active' => false]);

        $form = app(TestUserProfileFormlet::class);
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
        Route::post('/users', function (TestUserProfileFormlet $formlet, TestUser $model) {
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

        $user = TestUser::create(['email' => 'john@example.com']);
        $user->assignProfile(['name' => 'John']);

        Route::put('/users/{user}', function ($user, TestUserProfileFormlet $formlet) {
            $user = TestUser::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'profile' => [['name' => 'James']]
        ])->assertStatus(200);

        tap($user->fresh(),function(TestUser $user){
            $this->assertEquals("james@example.com",$user->email);
            $this->assertEquals("James",$user->profile->name);
        });
    }

    /** @test */
    public function has_many_relation()
    {

        $user = TestUser::create(['email' => 'john@example.com']);
        $postA = $user->posts()->create(['name'=>'Post A']);
        $postB = $user->posts()->create(['name'=>'Post B']);

        $formlet = app(TestUserPostsFormlet::class);
        $formlet->model($user)->build();

        $this->assertCount(2,$formlet->formlets('posts'));
        $this->assertEquals($postA->name,$formlet->formlets('posts')->get(0)->field('name')->getValue());
        $this->assertEquals($postB->name,$formlet->formlets('posts')->get(1)->field('name')->getValue());

    }

    /** @test */
    public function has_many_create_method()
    {

        Route::post('/users', function (TestUser $user, TestUserPostsFormlet $formlet) {
            return $formlet->model($user)->store();
        });

        $this->post('/users', [
          'email'   => 'john@example.com',
          'posts' => [
            ['name' => 'Post A'],
            ['name' => 'Post B']
          ]
        ])->assertStatus(200);

        tap(TestUser::first(),function(TestUser $user){
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

        $user = TestUser::create(['email' => 'john@example.com']);
        $user->posts()->create(['name'=>'Post A']);
        $user->posts()->create(['name'=>'Post B']);

        Route::put('/users/{user}', function ($user, TestUserPostsFormlet $formlet) {
            $user = TestUser::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'posts' => [
            ['name' => 'Post A Updated'],
            ['name' => 'Post B Updated']
          ]
        ])->assertStatus(200);

        tap($user->fresh(),function(TestUser $user){
            $this->assertEquals("james@example.com",$user->email);
            $posts = $user->posts;
            $this->assertCount(2,$posts);
            $this->assertEquals("Post A Updated",$posts->first()->name);
            $this->assertEquals("Post B Updated",$posts->get(1)->name);
        });
    }

    /** @test */
    public function has_many_relation_restriction()
    {
        $user = TestUser::create(['email' => 'john@example.com']);
        $user->posts()->create(['name'=>'Post A']);
        $user->posts()->create(['name'=>'Post B']);

        $formlet = $this->formlet(function(Formlet $formlet){
            $formlet->relation('posts',TestPostFormlet::class,function($query){
                $query->limit(1);
            });
        });

        $formlet->model($user)->build();

        $this->assertCount(1,$formlet->formlets('posts'));
    }

    /** @test */
    public function many_to_many_relation()
    {

        $user = TestUser::create(['email' => 'john@example.com']);
        $permissionA = TestPermission::create(['name' =>'Permission A','color'=>'Blue']);
        $permissionB =  TestPermission::create(['name' =>'Permission B']);

        $user->permissions()->attach($permissionA->id,['color'=>'Red']);

        $formlet = app(TestUserPermissionForm::class);
        $formlet->model($user)->build();

        $formlets = $formlet->formlets('permissions');

        $this->assertCount(2, $formlets);
        $this->assertEquals($permissionA->name, $formlets->get(0)->getRelated()->name);
        $this->assertEquals($permissionB->name, $formlets->get(1)->getRelated()->name);
        $this->assertEquals("john@example.com", $formlets->get(0)->getParent()->email);

        $this->assertInstanceOf(Model::class, $formlets->first()->getModel());
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
        TestPermission::create(['name' =>'Permission A']);
        TestPermission::create(['name' =>'Permission B']);

        Route::post('/users', function (TestUser $user, TestUserPermissionForm $formlet) {
            return $formlet->model($user)->store();
        });

        $this->post('/users', [
          'email'   => 'john@example.com',
          'permissions' => [
            ['id' => '1','color'=>'Red'],
            ['color'=>'Blue']
          ]
        ])->assertStatus(200);

        tap(TestUser::first(),function(TestUser $user){
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
        $user = TestUser::create(['email' => 'john@example.com']);
        $permissionA = TestPermission::create(['name' =>'Permission A']);
        $permissionB =  TestPermission::create(['name' =>'Permission B']);

        $user->permissions()->attach($permissionA->id,['color'=>'Red']);

        Route::put('/users/{user}', function ($user, TestUserPermissionForm $formlet) {
            $user = TestUser::find($user);
            return $formlet->model($user)->update();
        });

        $this->put('/users/1', [
          'email'   => 'james@example.com',
          'permissions' => [
            ['color'=>'Red'],
            ['id' => '2','color'=>'Blue']
          ]
        ])->assertStatus(200);

        tap($user->fresh(),function(TestUser $user){
            $this->assertEquals("james@example.com",$user->email);
            $permissions = $user->permissions;
            $this->assertCount(1,$permissions);
            $this->assertEquals("Permission B",$permissions->first()->name);
            $this->assertEquals("Blue",$permissions->first()->pivot->color);
        });

    }

    /** @test */
    public function many_to_many_restriction()
    {

        TestPermission::create(['name' =>'Permission A']);
        TestPermission::create(['name' =>'Permission B']);

        $formlet = $this->formlet(function(Formlet $formlet){
            $formlet->relation('permissions',TestUserPermissionFormlet::class,function($query){
                $query->limit(1);
            });
        });

        $formlet->model(new TestUser)->build();

        $this->assertCount(1,$formlet->formlets('permissions'));

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

        TestPermission::create(['name' =>'Permission A']);
        TestPermission::create(['name' =>'Permission B']);

        $form = app(TestUserPermissionForm::class);
        $user = new TestUser();

        $form->model($user)->build();

        $this->assertEquals([1=>['color'=>"Red"]],$form->subscriptionData('permissions'));

    }

    private function formlet(\Closure $closure = null): Formlet
    {
        return $this->app->makeWith(IntegrationFormlet::class, ['closure' => $closure]);
    }


}

class IntegrationFormlet extends Formlet
{

    protected $closure;

    public function __construct(\Closure $closure = null)
    {
        $this->closure = $closure;
    }

    public function prepare(): void
    {
        $closure = $this->closure;
        if (!is_null($closure)) {
            $closure($this);
        }
    }


}



