## Installation
**1. Composer Install**

```bash
$ composer require top20ofe/two-factor-authentication
```
*Note* - If your're using Laravel 5.5 or newer version then auto-discovery-pacakge would automatically update the providers and you could skip to **Step 3**  

**2. Add Service Provider**

After requiring the package add `TwoFactorAuthenticationServiceProvider::class` into providors array in `app.php` confi file

```php
[
 'providers' => [
    //...
    Top20ofe\TwoFactorAuthentication\TwoFactorAuthenticationServiceProvider::class
  ]
]
```

**3. Publish the ConfigFile**

Publish config file
```
$ php artisan vendor:publish --provider="Top20ofe\TwoFactorAuthentication\TwoFactorAuthenticationServiceProvider" --tag=config
```
Once the config file is published you can navigate to config directory of your application and look for `2fa-config.php` file and change configuration as you want.

**4. Run Migrations**

Now run the migration
```bash
$ php artisan migrate
```
It will use the default User model and adds two columns `is_2fa_enabled` and `secret_key`.

**5. Add `AuthenticatesUserWith2FA` trait in the LoginController**

Now the config file is placed. The last thing to do is addding `AuthenticatesUsersWith2FA` trait in the  `Http/Controllers/Auth/LoginController.php` file which helps to stop user at verify-2fa page to enter TOTP token after each login.

The final snippet will look like this.
```php
use AuthenticatesUsers, AuthenticatesUsersWith2FA {
    AuthenticatesUsersWith2FA::authenticated insteadof AuthenticatesUsers;
}
```
Note: Don't forget to include use statement `use Top20ofe\TwoFactorAuthentication\AuthenticatesUsersWith2FA` in the header.

**6. Setup 2FA for user**

  **• Enable 2FA**

Now login to the application and visit `/setup-2fa/` route, which will show a barcode which can be scanned either using Google Authenticator or Authy mobile application as described above.
Scan that code and click **Enable Two Factor Authentication**.

  **• Disable 2FA**

To disable Two Factor, visit `/setup-2fa` route, which will now show a **Disable Two Factor Authentication** button. Click to disable 2FA for your account.

**7. Testing 2FA**

Now to test 2FA, perform logout and log back in again, it will ask you to enter Token which can be obtain from the authenticator mobile application. Enter the token and you're logged in.

### Additionally
If you want to publish views, and migration as well along with config file then run
```
$ php artisan vendor:publish --provider="Top20ofe\TwoFactorAuthentication\TwoFactorAuthenticationServiceProvider"
```

