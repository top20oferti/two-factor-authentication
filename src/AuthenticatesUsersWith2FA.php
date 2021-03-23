<?php

namespace Top20ofe\TwoFactorAuthentication;

//use Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use OTPHP\Factory;
use OTPHP\TOTP;
use Validator;

trait AuthenticatesUsersWith2FA
{
    /*
     * Priveate variable to store user object.
     */
    private $user;

    /**
     * If username/password is authenticated then the authenticted.
     * If 2FA enabled it will redirect user to enter TOTP Token else
     * Logs the user in normally.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User                $user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        //dd($user->is_two_factor_enabled);
        if ($user->is_two_factor_enabled) {
            $request->session()->put('2fa:user:id', encrypt($user->id));
            $secret = getenv('HMAC_SECRET');
            $signature = hash_hmac('sha256', $user->id, $secret);
            //Auth::logout();
            Auth::guard('backpack')->logout();
            
            //dd(Auth::guard('backpack')->user());
            //return redirect()->intended('verify-2fa?signature=' . $signature);
            return redirect()->route('verify-2fa', ['signature' => $signature]);
        }

        //return redirect()->intended(config('2fa-config.redirect_to'));
        return redirect()->route(config('2fa-config.redirect_to'));
    }

    /**
     * Verify token and sign in the user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyToken(Request $request)
    {
        $TwoFAModel = TwoFactorAuthenticationServiceProvider::getTwoFAModelInstance();
        // Pulling encrypted user id from session and getting user details
        $userId = $request->session()->get('2fa:user:id');
        $this->user = $TwoFAModel->find(decrypt($userId));
        //dd($userId);
        // If token is not valid then custom validation error message will be shown.
        $messages = [
            'totp_token.valid_token' => 'Security code is not valid',
            'totp_token.required'    => 'Security code is required',
        ];
        
        // Impllicitly adding an validation rule to check if token is valid or not.
        Validator::extendImplicit('valid_token', function ($attribute, $value) {
            $totp = Factory::loadFromProvisioningUri($this->user->two_factor_provisioned_uri);
            //dd($totp);
            return $totp->verify($value);
        });

        // If Validation fails, it will return the error else sign in the user.
        $number_of_digits = config('2fa-config.number_of_digits');
        
        $validator = Validator::make($request->all(), [
            'totp_token' => "required|digits:$number_of_digits|valid_token",
        ], $messages);

        

        $secret = getenv('HMAC_SECRET');
        $signature = hash_hmac('sha256', $this->user->id, $secret);

        //dd($signature);


        if ($validator->fails()) {
            
            return redirect('verify-2fa?signature=' . $signature)
                ->withErrors($validator)
                ->withInput();
        }

        // Flush the session.
        $request->session()->forget('2fa:user:id');

        Auth::guard('backpack')->loginUsingId($this->user->id);

        //return redirect()->intended(config('2fa-config.redirect_to'));
        return redirect()->route(config('2fa-config.redirect_to'));
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        //dd($this->user);
        return $this->user;
    }
}