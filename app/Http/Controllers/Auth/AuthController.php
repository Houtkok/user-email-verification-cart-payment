<?php 
namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\UserVerify;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller{
    public function index(){
        return view('auth.login');
    }
    public function registration(){
        return view('auth.register');
    }
    public function postLogin(Request $request){
        $request->validate([
            'email'=>'required',
            'password'=>'required',
        ]);
        $credentials = $request->only('email','password');
        if(Auth::attempt($credentials)){
            return redirect()->intended('dashboard')->withSuccess('Log in successfully');
        }
        return redirect("login")->withSuccess('Invalid credentials');
    }
    public function postRegister(Request $request){
        $request ->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6',
        ]);
        $data = $request->all();
        $createUser = $this->create($data);
        $token = Str::random(64);
        
        UserVerify::create([
            'user_id'=>$createUser->id,
            'token'=>$token,
        ]);
        Mail::send('emails.emailVerificationEmail',['token'=>$token],function($message) use($request){
            $message->to($request->email);
              $message->subject('Email Verification Mail');
        });
        return redirect("home")->withSuccess('Great! You have Successfully loggedin');
    }
    public function dashboard(){
        if(Auth::check()){
            return view('home');
        }
        return redirect("login")->withSuccess('You don`t have access');
    }
    public function create(array $data){
        return User::create([
            'name'=>$data['name'],
            'email'=>$data['email'],
            'password'=> Hash::make($data['password'])
        ]);
    }
    public function logout(){
        Session::flush();
        Auth::logout();
        return redirect('login');
    }
    public function verifyAccount($token){
        $verifyUser = UserVerify::where('token',$token)->first();
        $message = 'Sorry your email cannot be identified.';

        if(!is_null($verifyUser)){
            $user = $verifyUser->user;
            if(!$user->is_email_verified){
                $verifyUser->user->is_email_verified = 1; 
                $verifyUser->user->save();
                $message = "Your email is verified.";
            }
            else{
                $message = "Your email is already verified";
            }

            return redirect()->route('login')->with('message',$message);
        }
    }
}
