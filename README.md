# Laravel Form

## Installation

```sh
composer require rs/form-laravel
```

View files can be changed by publishing them. By default they use bootstrap 4 classes.

```bash
php artisan vendor:publish --tag=form
```

## Quick start

### Creating formlets

Formlets can be created using artisan.

```sh
php artisan make:formlet UserForm
```

By default the formlet will be created in the path `app/Http/Formlets`.

```php
<?php

namespace App\Http\Formlets;

use RS\Form\Formlet;

class UserForm extends Formlet{

   /**
    * Prepare the form with fields
    *
    * @return void
    */
   public function prepare():void {

   }

   /**
    * Validation rules that apply to the form.
    *
    * @return array
    */
   public function rules(): array
   {
       return [];
   }

}

```

### Add fields to formlet

Next step is to add fields to the form. Fields are added using the add method in the prepare function. Prepare allows us to setup all the fields ready for rendering.

The add function excepts any type of field which extends `RS\Form\Fields\AbstractField`

```php
<?php

namespace App\Http\Formlets;

use RS\Form\Formlet;
use RS\Form\Fields\Input;

class UserForm extends Formlet{

   /**
    * Prepare the form with fields
    *
    * @return void
    */
   public function prepare():void {
        
       $field = new Input('text','name');
       $this->add($field); 
        
   }

   /**
    * Validation rules that apply to the form.
    *
    * @return array
    */
   public function rules(): array
   {
       return [
         'name'=> 'required'
       ]; 
   }

}

```

Notice we also added a validation rule. The rules can be defined as per the laravel documentation.

### View the form

We can instantiate the form in a controller and pass the form data to a view.

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Http\Formlets\UserForm;

class UserController extends BaseController {

    public function create(UserForm $form)
    {
        $data = $form->route('users.store')->build();
        
        return view('user.create',$data);
    }

    public function store(UserForm $form)
    {
        $form->store();
    }
}

```

The create method is creating our form view for us. We are passing a route by name so it knows where to post to and which HTTP method to use. The build will return an array which contains information about the form and the fields which can be used to render the view. We are also handling the post using the store method on the form. At the moment it is not doing much but we can see how to the handle the post later.

### Rendering the view

```blade
@form(['form'=>$form])
    @formlet()
@enform

```
We are using a couple of custom blade directives to render our form. The form directive requires the form key which has been passed to the view. The formlet directive will render the fields.

### Handling the post

In the controller are using the store method which runs all validations before running the persist method.

If the post fails validation the form will redirect back and populate the session with errors from the form. By default all errors will be rendered next to the field which has the error.

If validation passes then by default the persist method will try and insert into the fields into a model if one has been set. 

```php
<?php

namespace App\Http\Formlets;

use RS\Form\Formlet;
use RS\Form\Fields\Input;

class UserForm extends Formlet{
    
    public function __construct(User $user) {
        $this->model = $user;
    }
    
    
    public function prepare(): void {
        // Prepare the fields.
    }

}

```

We can overwrite the persist method if required.