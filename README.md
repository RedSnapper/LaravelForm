#Laravel Form

Begin by installing this package through Composer. Run the following from the terminal:

composer require "rs/form-laravel"
Next, add your new provider to the providers array of config/app.php:

```php
  'providers' => [
    // ...
    \RS\Form\FormServiceProvider::class,
    // ...
  ],
  
```
