<?php

namespace App\Http\Controllers\Api;

use App\Common\Consts\User\UserStatusConsts;
use App\Http\HttpMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use App\Http\HttpResponse;
use App\Http\HttpStatus;
use App\Http\Requests;
use JWTAuth;
use App\Http\Controllers\Controller;
use App\Models\User;
use JWTAuthException;
use Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Auth\ForgotPasswordController;
use GuzzleHttp\Client as HttpClient;
use DB;

class UsersController extends Controller
{
    private $user;
    public function __construct(User $user){
        $this->user = $user;
    }

    public function register(Request $request){

        $validator = Validator::make($request->all(), [
            'phonenumber' => 'required|regex:/(01)[0-9]{9}/',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return HttpResponse::badRequest(HttpStatus::$ERR_VALIDATION, HttpMessage::$USER_ERROR_CREATING, $validator->errors()->all());
        }

        $user = User::where('phonenumber', $request->get('phonenumber'))->first();

        if ($user) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_EXISTS, HttpMessage::$USER_EMAIL_EXISTS,
                HttpMessage::$USER_EMAIL_EXISTS);
        }

        $user = User::updateOrCreate(array('phonenumber'=>$request->get('phonenumber')),
             [
                'phonenumber' => $request->get('phonenumber'),
                'password' => bcrypt($request->get('password')),
                'username' => 'sfdaf',
                'verify_code' => 'fdsf'
                
            ]
         );
         \Log::info('**************user created*******************');

        $token = null;
        $credentials = $request->only('phonenumber', 'password');
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return HttpResponse::unauthorized(HttpStatus::$ERR_USER_INVALID_CREDENTIALS,
                    HttpMessage::$USER_INVALID_CREDENTIALS, HttpMessage::$USER_INVALID_CREDENTIALS);
            }
        }
        catch (JWTAuthException $e) {
            return HttpResponse::serverError(HttpStatus::$ERR_USER_CREATE_TOKEN,
                HttpMessage::$USER_ERR_CREATING_TOKEN, HttpMessage::$USER_ERR_CREATING_TOKEN);
        }

        $user->token = $token;
            return HttpResponse::ok(HttpMessage::$USER_CREATED_SUCCESSFULLY, $user);


    }

    public function testToken(Request $request){

         $user = JWTAuth::toUser($request->token);
         
         \Log::info('**************user created*******************');
         return HttpResponse::ok(HttpMessage::$USER_CREATED_SUCCESSFULLY, $user);
    }

}