<?php

namespace App\Http\Controllers\API\Base;

use App\Http\Requests\API\Auth\CitiesOfExpertiseRequest;
use App\Http\Requests\API\Auth\EmailValidationRequest;
use App\Http\Requests\API\Auth\LoginRequest;
use App\Http\Requests\API\Auth\UserLoginToken;
use App\Http\Requests\API\Auth\RegisterRequest;
use App\Http\Requests\API\Auth\UsernameValidationRequest;
use App\Http\Requests\API\Auth\VerifyPhoneValidation;
use App\Http\Requests\API\Auth\SendCodeRequest;
use App\Http\Requests\API\Auth\ChangePhoneRequest;
use App\Http\Requests\API\Auth\CompleteInfoRequest;
use App\Http\Requests\API\Auth\UpdateInfoRequest;
use App\Http\Requests\API\Auth\ConfirmCodeRequest;
use App\Http\Requests\API\Auth\ResetPasswordRequest;
use App\Http\Requests\API\Auth\PasswordRequest;
use App\Services\Files\UserAvatar;
use App\Services\SMS\SendSMS;
use App\Services\Email\SendEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserLogin;
use App\Models\Polymorph\Attachment;
use App\Models\Base\User;
use App\Models\Chat\Group;
use App\Models\Base\Country;
use Laravel\Passport\Passport;
use Socialite;
use Validator;
use Auth;
use Hash;
use Storage;

class AuthController extends Controller
{
    /**
     * @var SendSMS
     */
    private $smsDispatcher;

    public function __construct(SendSMS $smsDispatcher)
    {
        parent::__construct();

        $this->smsDispatcher = $smsDispatcher;
    }

    public function register(RegisterRequest $request, SendEmail $sendEmail)
    {
        $user = User::register($request->only('email', 'password'));
        auth()->login($user);
        Passport::actingAs($user);

        $sendEmail->welcomeEmail($request->email, $user->first_name);

        return new UserLogin($user);
    }

    public function login(LoginRequest $request)
    {
        $email_exists = User::emailExists($request->email);

        if (auth()->attempt($request->only('email', 'password'))) {
            return new UserLogin(auth()->user());
        }

        return response()->json(['error' => ['Wrong Credentials!'], 'email_exists' => $email_exists], 406);
    }

    public function getUserWithToken(UserLoginToken $request)
    {
        return new UserLogin($request->user());
    }

    public function emailValidation(EmailValidationRequest $request)
    {
        return response()->json(['data' => [sprintf('Email (%s) validated successfully.', $request->email)]]);
    }

    public function username(UsernameValidationRequest $request)
    {
        return response()->json(['data' => [sprintf("This username %s is availble.", $request->username)]]);
    }

    public function verifyPhone(VerifyPhoneValidation $request)
    {
        $user = $request->user();

        if ($user->shouldThrottle()) {
            return response()->json(['error' => ["The activation SMS has been sent recently!"]], 429);
        }

        list($status, $code) = $this->smsDispatcher->verificationSMS($request->phone);
        if ($status) {
            $user->requestedVerificationSMS($code, $request->phone);
            return response()->json(['data' => ["verification sms sent."]]);
        }

        return response()->json(['data' => ['server failed!']], 500);
    }

    public function sendCode(SendCodeRequest $request)
    {
        $user = $request->user();

        if ($user->verification_code != $request->code) {
            return response()->json(['error' => ["code is wrong!"]], 406);
        }

        $user->makeVerify();

        return new UserLogin($user);
    }

    public function changePhone(ChangePhoneRequest $request)
    {
        $user = $request->user();

        if ($user->phone == $request->phone) {
            return response()->json(['error' => ['Both numbers are same!']], 406);
        }

        list($status, $code) = $this->smsDispatcher->verificationSMS($request->phone);
        if ($status) {
            $user->requestedVerificationSMS($code, $request->phone);
            return response()->json(['data' => ["verification sms sent."]], 200);
        }

        return response()->json(['data' => ['server failed!']], 500);
    }

    public function changePassword(PasswordRequest $request)
    {
        $user = Auth::user();
        if ($request->old_password == $request->new_password) {
            return response()->json(['error' => ['old password and new password should be different!']], 405);
        }
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['error' => ['old password is wrong!']], 406);
        }
        $user->updatePassword($request->new_password);

        return new UserLogin($user);
    }

    public function updateInfo(UpdateInfoRequest $request, UserAvatar $avatarHandler)
    {
        $user = $request->user();

        $user->update($request->updateData());
        $user->updateCurrentCity($request->city());
        if ($request->hasExpertise()) {
            $user->updateCitiesOfExpertise($request->cities_expertise);
        }

        if ($request->hasFile('avatar')) {
            $avatarHandler->of($user)->storeAvatar($request->file('avatar'));
        }

        return new UserLogin($user);
    }

    public function citiesOfExpertise(CitiesOfExpertiseRequest $request)
    {
        $user = $request->user();

        $user->updateCitiesOfExpertise($request->cities_expertise);

        return new UserLogin($user);
    }

    public function socialMediaProvidersWithSession($driver)
    {
        return Socialite::driver($driver)->redirect();
    }

    public function redirectToProvider(Request $request, $driver)
    {
        return Socialite::driver($driver)->redirect();
    }
}
