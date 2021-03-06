<?php

namespace Top20ofe\TwoFactorAuthentication\Http\Controllers;

use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Top20ofe\TwoFactorAuthentication\AuthenticatesUsersWith2FA;
use Top20ofe\TwoFactorAuthentication\Contracts\TwoFactorAuthenticationInterface;
use Top20ofe\TwoFactorAuthentication\Exceptions\TwoFactorAuthenticationExceptions;
use Top20ofe\TwoFactorAuthentication\TwoFactorAuthenticationServiceProvider;

class TwoFactorAuthenticationController extends Controller implements TwoFactorAuthenticationInterface
{
    use AuthenticatesUsersWith2FA;

    /**
     * User Model.
     */
    protected $TwoFAModel;

    /**
     * Assigns $usersModel Property a Model instance.
     * Set authenticated users data to $user Property.
     */
    public function __construct()
    {
        $this->TwoFAModel = TwoFactorAuthenticationServiceProvider::getTwoFAModelInstance();

        $this->middleware(function ($request, $next) {
            //dd(config('2fa-config.guard'));
            $this->setUser(\Auth::guard('backpack')->user());
            //dd($this);
            return $next($request);
        });
    }

    /**
     * Setup two factor authentication.
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     *
     * @throws \Top20ofe\TwoFactorAuthentications\Exceptions\TwoFactorAuthenticationExceptions
     *
     * @return mixed
     */
    public function setupTwoFactorAuthentication(Request $request)
    {
        $user = $this->getUser();
        $totp = TOTP::create(
            $this->base32EncodedString(),
            config('2fa-config.period'),
            config('2fa-config.digest_algorithm'),
            config('2fa-config.number_of_digits')
        );
        //$totp->setLabel(config('2fa-config.account_name'));
        $totp->setLabel(config('2fa-config.account_name') . ' (' . $this->getUser()->email . ')');
        $totpURI = $totp->getProvisioningUri();
        if(!$user->is_two_factor_enabled){
            $this->updateUserWithProvisionedUri($totpURI);
        }    

        $qrCode = new QrCode($totpURI);
        $barcode = $qrCode->writeDataUri();

        if ($request->ajax()) {
            return $barcode;
        }

        return view('2fa::setup', compact('barcode', 'user'));
    }

    /**
     * Disable 2FA.
     *
     * @param \Illuminate\Http\Request
     *
     * @return mixed
     */
    public function enableTwoFactorAuthentication(Request $request)
    {
        $user = $this->getUser();
        $user->is_two_factor_enabled = 1;
        $user->update();

        if ($request->ajax()) {
            return [
                'data' => [
                    'message'     => 'success',
                    'description' => '2FA Enabled',
                ],
            ];
        }

        //return redirect(config('2fa-config.redirect_to'));
        return redirect()->route(config('2fa-config.redirect_to'));
    }

    /**
     * Enable 2FA.
     *
     * @param \Illuminate\Http\Request
     *
     * @return mixed
     */
    public function disableTwoFactorAuthentication(Request $request)
    {
        $user = $this->getUser();
        $user->is_two_factor_enabled = 0;
        $user->two_factor_provisioned_uri = null;
        $user->update();

        if ($request->ajax()) {
            return [
                'data' => [
                    'message'     => 'success',
                    'description' => '2FA Disabled',
                ],
            ];
        }

        //return redirect(config('2fa-config.redirect_to'));
        return redirect()->route(config('2fa-config.redirect_to'));
    }

    /**
     * Verify Two Factor Authentication.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function verifyTwoFactorAuthentication(Request $request)
    {
        //\Auth::guard('backpack')->logout();
        //dd($request->session()->has('2fa:user:id'));
        if ($request->session()->has('2fa:user:id')) {
            $secret = getenv('HMAC_SECRET');
            $signature = hash_hmac('sha256', decrypt($request->session()->get('2fa:user:id')), $secret);
            //dd(md5($signature), md5($request->signature));
            if (md5($signature) !== md5($request->signature)) {
                return redirect()->intended('login');
            }
            
            return view('2fa::verify');
        }

        return redirect()->back(); //shoud be configurable
    }

    /**
     * Encode Random String to 32 Base Transfer Encoding.
     *
     * @return string
     */
    private function base32EncodedString(): string
    {
        return trim(Base32::encodeUpper(random_bytes(128)), '=');
    }

    /**
     * Update User data with 2FA generated Key.
     *
     * @return void
     */
    private function updateUserWithProvisionedUri($twoFactorProvisionedUri)
    {
        //dd($this);
        $user = $this->TwoFAModel->find($this->getUser()->id);
        if (!Schema::hasColumn(config('2fa-config.table'), 'two_factor_provisioned_uri') ||
            !Schema::hasColumn(config('2fa-config.table'), 'is_two_factor_enabled')) {
            throw TwoFactorAuthenticationExceptions::columnNotFound();
        }
        $user->two_factor_provisioned_uri = $twoFactorProvisionedUri;
        $user->update();
    }
}