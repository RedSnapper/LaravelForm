# Laravel Validation

- [Rules](#rules)
- [Configuring the validator](#configuring-the-validator)

## Rules

To add validation to each formlet we can use the rules method. These rules will be used to validate this form.

```php
/**
 * Get the validation rules that apply to the request.
 *
 * @return array
 */
public function rules()
{
    return [
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
    ];
}
```

These rules are evaluated when the `validate` method is called. The validate method is automatically called as part of the `store` and `update` method. If validation fails a redirect response will be generated to send the user back to their previous location. The errors will also be flashed to the session so they are available for display. If the request was an AJAX request, a HTTP response with a 422 status code will be returned to the user including a JSON representation of the validation errors.
 
## Configuring the validator 

If you would like to configure the validator before any of the validation rules are evaluated you can use the `withValidator` method. This method receives the fully constructed validator, allowing you to call any of its methods before the validation rules are actually evaluated:

```php
 /**
 * Configure the validator instance.
 *
 * @param  \Illuminate\Validation\Validator  $validator
 * @return void
 */
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->somethingElseIsInvalid()) {
            $validator->errors()->add('field', 'Something is wrong with this field!');
        }
    });
}
```

## Honeypot

By default there is a honeypot field added to every form. The default name is _formlet. The view for the honeypot field can be overridden. If the honeypot field fails a `HoneyPotException` is thrown which will by default be handled by laravel and redirect back to the page.

There is also a method which you can call to disable the honeypot on any given form.

```php

public function create(UserForm $form)
{
   $form->honeypot(false)->build();
}
```
