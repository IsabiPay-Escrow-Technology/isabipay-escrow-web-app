<?php

namespace App\Http\Controllers\Auth;

use App\Http\Traits\Notify;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use Notify;

    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
//    protected $redirectTo = RouteServiceProvider::HOME;
    protected $redirectTo = '/user/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        if (config('basic.registration') == 0) {
            return redirect('/')->with('warning', 'Registration Has Been Disabled.');
        }

        return view(template() . 'auth.register');
    }

    public function sponsor($sponsor)
    {
        session()->put('sponsor', $sponsor);
        return view(template() . 'auth.register', compact('sponsor'));

    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        if (config('basic.strong_password') == 0) {
            $rules['password'] = ['required', 'min:6', 'confirmed'];
        } else {
            $rules['password'] = ["required", 'confirmed',
                Password::min(6)->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()];
        }

        if (basicControl()->reCaptcha_status_registration) {
            $rules['g-recaptcha-response'] = ['sometimes', 'required','captcha'];
        }

        $country_code = "AF,AL,DZ,AS,AD,AO,AI,AG,AR,AM,AW,AU,AZ,BH,BD,BB,BY,BE,BZ,BJ,BM,BT,BO,BA,BW,BR,IO,VG,BN,BG,BF,MM,BI,KH,CM,CA,CV,KY,CF,ID,CL,CN,CO,KM,CK,CR,CI,HR,CU,CY,CZ,CD,DK,DJ,DM,DO,EC,EG,SV,GQ,ER,EE,ET,FK,FO,FM,FJ,FI,FR,GF,PF,GA,GE,DE,GH,GI,GR,GL,GD,GP,GU,GT,GN,GW,GY,HT,HN,HK,HU,IS,IN,IR,IQ,IE,IL,IT,JM,JP,JO,KZ,KE,KI,XK,KW,KG,LA,LV,LB,LS,LR,LY,LI,LT,LU,MO,MK,MG,MW,MY,MV,ML,MT,MH,MQ,MR,MU,YT,MX,MD,MC,MN,ME,MS,MA,MZ,NA,NR,NP,NL,AN,NC,NZ,NI,NE,NG,NU,NF,KP,MP,NO,OM,PK,PW,PS,PA,PG,PY,PE,PH,PL,PT,PR,QA,CG,RE,RO,RU,RW,BL,SH,KN,MF,PM,VC,WS,SM,ST,SA,SN,RS,SC,SL,SG,SK,SI,SB,SO,ZA,KR,ES,LK,LC,SD,SR,SZ,SE,CH,SY,TW,TJ,TZ,TH,BS,GM,TL,TG,TK,TO,TT,TN,TR,TM,TC,TV,UG,UA,AE,GB,US,UY,VI,UZ,VU,VA,VE,VN,WF,YE,ZM,ZW";
        return Validator::make($data, $rules, [
            'firstname' => ['required', 'string', 'max:91'],
            'lastname' => ['required', 'string', 'max:91'],
            'username' => ['required', 'alpha_dash', 'min:5', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required',
                'phone:'.$country_code],
            'password' => ['required', 'string', 'confirmed'],
        ], [
            'firstname.required' => 'First Name Field is required',
            'lastname.required' => 'Last Name Field is required',
            'phone' => 'The :attribute field contains an invalid number.'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {

        $basic = (object) config('basic');

        $sponsor = session()->get('sponsor');
        if ($sponsor != null) {
            $sponsorId = User::where('username', $sponsor)->first();
        } else {
            $sponsorId = null;
        }


        $user =  User::create([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'referral_id' => ($sponsorId != null) ? $sponsorId->id : null,
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'email_verification' => (@$basic->email_verification) ? 0 : 1,
            'sms_verification' => (@$basic->sms_verification) ? 0 : 1,
        ]);

        if($user->referral_id){
            Contact::create([
                'user_id' => $user->referral_id,
                'receiver_id' => $user->id,
                'status' => 1
            ]);
        }

        return $user;


    }

    public function register(Request $request)
    {

        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        $msg = [
            'username' => $user->fullname,
        ];
        $action = [
            "link" => route('admin.user-edit', $user->id),
            "icon" => "fas fa-user text-white"
        ];

        $this->adminPushNotification('ADDED_USER', $msg, $action);
        $currentDate = dateTime(Carbon::now());
        $this->sendMailSms($user, $type = 'REGISTER_CONFIRM_MAIL_TO_USER', [
            'name'          => $user->fullname,
            'date'          => $currentDate,
        ]);


        $this->guard()->login($user);


        session()->forget('sponsor');

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        if ($request->ajax()) {
            return route('user.home');
        }

        return $request->wantsJson()
            ? new JsonResponse([], 201)
            : redirect($this->redirectPath());
    }


    /**
     * The user has been registered.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $user->last_login = Carbon::now();
        $user->save();


    }

    protected function guard()
    {
        return Auth::guard();
    }

}
