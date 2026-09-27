<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class SocialLoginController extends Controller
{
    /**
     * Which sign-in buttons the apps show.
     *
     * Store builds carry Google and Apple sign-in switched off. Each one is
     * turned on here (SOCIAL_LOGIN_* in .env) once it is set up and tested,
     * without another release.
     */
    public function config()
    {
        $serverClientId = config('services.google.app_server_client_id');

        $apple = (bool) config('services.social_login.apple_ios')
            && config('services.apple.client_ids', []) !== [];

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'google' => [
                'android' => (bool) config('services.social_login.google_android') && filled($serverClientId),
                // App Review wants Sign in with Apple wherever Google sign-in
                // is offered (guideline 4.8), so iOS never gets Google alone.
                'ios' => (bool) config('services.social_login.google_ios') && $apple && filled($serverClientId),
                'server_client_id' => $serverClientId,
                'ios_client_id' => config('services.google.ios_client_id'),
            ],
            'apple' => [
                'ios' => $apple,
            ],
        ]);
    }
}
