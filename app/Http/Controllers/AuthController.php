<?php

namespace App\Http\Controllers;

use Validator;
use Config;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Password;
use Dingo\Api\Exception\ValidationHttpException;
use Illuminate\Foundation\Application;


use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\SignupUserRequest;

use Carbon\Carbon;

class AuthController extends ApiController
{

    const REFRESH_TOKEN = 'refreshToken';

    private $apiConsumer;

    private $cookie;


    public function __construct(Application $app) {

        $this->apiConsumer = $app->make('apiconsumer');
        $this->cookie = $app->make('cookie');
    }

    public function login(LoginUserRequest $request)
    {
        return response()->json($this->attemptLogin($request->email, $request->password));
    }

    public function signup(SignupUserRequest $request)
    {
        $userData = $request->all();

        User::unguard();
        $user = User::create($userData);
        User::reguard();

        if(!$user->id) {
            return $this->response->errorInternal("Could not create user");
        }

       return response()->json($this->attemptLogin($request->email, $request->password));
    }

    /**
     * Attempt to create an access token using user credentials
     *
     * @param string $email
     * @param string $password
     */
    public function attemptLogin($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!is_null($user)) {
            return $this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]);
        }

        //throw new InvalidCredentialsException();
        return $this->response->errorUnauthorized("Incorrect credentials");

    }

    /**
     * Proxy a request to the OAuth Passport server.
     *
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     */
    public function proxy($grantType, array $data = [])
    {
        $data = array_merge($data, [
            'client_id'     => env('PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSWORD_CLIENT_SECRET'),
            'grant_type'    => $grantType
        ]);

        //using inbuilt dingo api internal api dispatcher
        $response = $this->apiConsumer->post('oauth/token', $data);

        if (!$response->isSuccessful()) {
            return $this->response->errorUnauthorized();
        }

        $data = json_decode($response->getContent());

        // Create a refresh token cookie
        $this->cookie->queue(
            self::REFRESH_TOKEN,
            $data->refresh_token,
            864000, // 10 days
            null,
            null,
            false,
            true // HttpOnly
        );

        return [
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in
        ];
    }
}