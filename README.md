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
### Add a prefix to your formlet

This is useful when you have more than one form on a page which might be sharing the same field names. In order for errors and fields to be populated correctly we can give our form a prefix.

```php
<?php

class TestForm extends Formlet{
    public function __construct(){
        $this->setPrefix('test');
    }
}

```

All fields and child formlet fields will now be prefixed with the provided prefix. Also, errors will now also be added to a separate error bag of the same name.

```html
<input name="test:name"/>
<input name="test:email"/>
```
When testing, be sure to add the prefixes to your posted values.

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

## Validation

### Hooks
Before each form is validated we can manipulate the input for the formlet before it is validated using the prepareForValidation method. This will run for each formlet.

```php
<?php

namespace App\Http\Formlets;

use RS\Form\Formlet;

class UserForm extends Formlet{
    
    public function prepare(): void {
        // Prepare the fields.
    }
    
    protected  function prepareForValidation()
    {
        $this->mergeInput(['name'=>'John']);
    }

}
```
### Redisplay after a validation failure

When validation fails the request is flashed to the session and the form redisplays. Each field's value is resolved in this order:

1. Old input (the flashed request)
2. The current request
3. The model, if the form has one and the request is a `GET`
4. The field's `default()`

Before 8.0, a field missing from old input fell through to the model. That silently reverted anything the user had emptied, because an emptied field is exactly what arrives missing: a cleared text input is posted as `''` and flashed as `null` by `ConvertEmptyStringsToNull`, and an unticked checkbox or a fully emptied multi-select posts no key at all.

From 8.0 every form carries a hidden `_formlet` input naming the form (its prefix, or `default`). When that marker in old input matches a formlet, the flashed input is treated as the whole truth for that form:

- **Key present**, even with a `null` value: the submitted value is used as-is. A `null` clears the field, so neither the model nor the default reasserts the old value.
- **Key absent** and the field knows what absence means: a `Checkbox` becomes its unchecked value, and any `multiple()` field (`CheckboxGroup`, a multiple `Select`) becomes `[]`.
- **Key absent** otherwise: no signal. A disabled input, a file input, or a field the view did not render never posts a key, so these fall back to the normal resolution above.

Any other formlet on the page, and any old input flashed by a form that is not a formlet, is unaffected. Two **unprefixed** forms on one page both identify as `default`, so give them prefixes if they can fail validation independently.

If you have published the `form::components.form` view, keep the loop over `$form['hidden']`; that is where the marker is rendered.

#### Cleared values and defaults

`setValue(null)` leaves the default in force, so the common `->setValue($this->model?->foo)` in `prepare()` still shows the default on a create form. To empty a field regardless of its default, call `clearValue()`; that is what the formlet does for a field the user submitted blank. `isCleared()` reports the state, and a later `setValue()` ends it.

`isDirty()` means a value has been written to the field by anyone, developer or user; it is what stops the model overwriting a value set in `prepare()`. It does not mean the user changed the field.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release notes and upgrade guidance.
