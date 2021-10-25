<?php 
namespace VanguardLTE\Http\Controllers\Web\Frontend\Auth
{
    class PasswordController extends \VanguardLTE\Http\Controllers\Controller
    {
        public function __construct()
        {
            $this->middleware('guest');
        }
        public function getBasicTheme()
        {
            $frontend = 'Default';
            if( \Auth::check() ) 
            {
                
            }
            return $frontend;
        }
        public function forgotPassword()
        {
            $frontend = $this->getBasicTheme();
            return view('frontend.' . $frontend . '.auth.password.remind');
        }
        public function sendPasswordReminder(\VanguardLTE\Http\Requests\Auth\PasswordRemindRequest $request, \VanguardLTE\Repositories\User\UserRepository $users)
        {
            $user = \VanguardLTE\User::where(['username' => $request->username, 'email' => $request->email])->first();
            if (!$user)
                return redirect('categories/all?forgotpassword=fail')->withErrors(trans('auth.failed'));
            $token = Password::getRepository()->create($user);
            $user->notify(new \VanguardLTE\Notifications\ResetPassword($token));
            event(new \VanguardLTE\Events\User\RequestedPasswordResetEmail($user));
            return redirect()->to('password/remind')->with('success', trans('app.password_reset_email_sent'));
        }
        public function getReset($token = null)
        {
            if( is_null($token) ) 
            {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
            }
            $frontend = $this->getBasicTheme();
            return view('frontend.' . $frontend . '.auth.password.reset')->with('token', $token);
        }
        public function postReset(\VanguardLTE\Http\Requests\Auth\PasswordResetRequest $request, \VanguardLTE\Repositories\User\UserRepository $users)
        {
            $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
            $response = Password::reset($credentials, function($user, $password)
            {
                $this->resetPassword($user, $password);
            });
            switch( $response ) 
            {
                case Password::PASSWORD_RESET:
                    $user = $users->findByEmail($request->email);
                    \Auth::login($user);
                    return redirect('');
                default:
                    return redirect()->back()->withInput($request->only('email'))->withErrors(['email' => trans($response)]);
            }
        }
        protected function resetPassword($user, $password)
        {
            $user->password = $password;
            $user->save();
            event(new \VanguardLTE\Events\User\ResetedPasswordViaEmail($user));
        }
    }

}
