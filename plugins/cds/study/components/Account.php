<?php namespace Cds\Study\Components;

use October\Rain\Support\Facades\Flash;
use RainLab\User\Components\Account as RainLabAccount;
use RainLab\User\Models\Settings as UserSettings;
use Cds\Study\Models\User;
use Lang;
use Auth;
use Mail;
use Event;
use Request;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;

class Account extends RainLabAccount
{
    use \Cds\Study\Traits\ComponentVariants;
    use \Cds\Study\Traits\ComponentModals;

    public function componentDetails()
    {
        return [
            'name'        => 'Личный кабинет',
            'description' => 'Компонент регистрации, авторизации и личного профиля пользователя'
        ];
    }

    /**
     * Рендер модального окна формы авторизации
     */
    public function onRenderModalSignin()
    {
        return ['rememberLoginMode' => $this->rememberLoginMode()];
    }

    /**
     * Рендер формы авторизации
     */
    public function onRenderSignin()
    {
        return ['rememberLoginMode' => $this->rememberLoginMode()];
    }

    /**
     * Рендер модального окна восстановления пароля
     */
    public function onRenderModalRecoveryPasswordEmail()
    {
        //
    }

    /**
     * Рендер формы смены пароля посе восстановения
     */
    public function onRenderRecoveryPassword()
    {
        //
    }

    /**
     * Смены типа пользоватея на форме регистрации
     */
    public function onChangeForm()
    {
        return ['#register_form' => $this->renderPartial('@main_form', ['type' => post('type')])];
    }

    /**
     * Смена пароя пользователя
     */
    public function onChangePassword()
    {
        if (!$user = $this->user()) {
            return;
        }

        if (strlen(post('current_password'))) {
            $credentials = ['email' => $user->email, 'password' => post('current_password')];
            $userCheck = Auth::findUserByCredentials($credentials, true);

            if (post('password') !== post('password_confirmation')) {
                Flash::error('Новый пароль не совпадает с подтверждением!');
                return;
            }

            $user->update(post());

            Auth::login($user->reload(), true);
            Flash::success('Ваш пароль успешно изменён!');
            return;
        } else {
            Flash::error('Укажите старый пароль');
            return;
        }
    }

    /**
     * Восстановление пароля пользователя
     */
    public function onRestorePassword()
    {
        $user = User::findByEmail(post('email'));
        if (!$user) {
            throw new ApplicationException(Lang::get('rainlab.user::lang.account.invalid_user'));
        }

        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

        $link = $this->makeResetUrl($code);

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.restore', $data, function ($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });

        return ['#recovery_email' => $this->renderPartial('@recovery_email_check', ['email' => $user->email])];
    }

    /**
     * Сброс пароля пользователя
     */
    public function onResetPassword()
    {
        $rules = [
            'code'     => 'required',
            'password' => 'required|between:6,255|confirmed',
            'password_confirmation' => 'required_with:password|between:6,255'
        ];

        $customMessages = [
            'password.required' => 'Необходимо заполнить новый пароль',
            'password.confirmed' => 'Пароль не совпадает с подтверждением',
            'password.between' => 'Пароль должен содержать от 6 до 255 символов',

            'password_confirmation.required_with' => 'Пароль не совпадает с подтверждением',
            'password_confirmation.between' => 'Пароль должен содержать от 6 до 255 символов',
        ];

        $validation = Validator::make(post(), $rules, $customMessages);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $errorFields = ['code' => Lang::get('rainlab.user::lang.account.invalid_activation_code')];

        $parts = explode('!', post('code'));
        if (count($parts) != 2) {
            throw new ValidationException($errorFields);
        }

        list($userId, $code) = $parts;

        if (!strlen(trim($userId)) || !strlen(trim($code)) || !$code) {
            throw new ValidationException($errorFields);
        }

        if (!$user = Auth::findUserById($userId)) {
            throw new ValidationException($errorFields);
        }

        if (!$user->attemptResetPassword($code, post('password'))) {
            throw new ValidationException($errorFields);
        }

        return Redirect::to('/');
    }

    /**
     * Авторизация пользователя
     */
    public function onSignin()
    {
        try {
            $data = post();
            $rules = [];

            if (strpos(post('login'), '@') === false) {
                $rules['login'] = 'required|size:10';
                $data['login'] = substr(preg_replace("/[^0-9]/", '',$data['login']), 1);
            } else {
                $rules['login'] = 'required|email|between:6,255';
            }

            $rules['password'] = 'required|between:6,255';

            $customMessages = [
                'login.required' => 'Введите номер телефона или почту для авторизации',
                'login.size' => 'Телефон должен содержать 11 цифр',
                'login.email' => 'Введите корректный email',
                'password.required' => 'Введите пароль',
                'password.between' => 'Пароль должен содержать от 6 до 255 символов',
            ];

            if (!array_key_exists('login', $data)) {
                $data['login'] = post('login');
            }

            $validation = Validator::make($data, $rules, $customMessages);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Authenticate user
             */
            $credentials = [
                'login'    => array_get($data, 'login'),
                'password' => array_get($data, 'password')
            ];

            /*
            * Login remember mode
            */
            switch ($this->rememberLoginMode()) {
                case UserSettings::REMEMBER_ALWAYS:
                    $remember = true;
                    break;
                case UserSettings::REMEMBER_NEVER:
                    $remember = false;
                    break;
                case UserSettings::REMEMBER_ASK:
                    $remember = (bool) array_get($data, 'remember', false);
                    break;
            }

            Event::fire('rainlab.user.beforeAuthenticate', [$this, $credentials]);

            $user = Auth::authenticate($credentials, $remember);
            if ($user->isBanned()) {
                Auth::logout();
                throw new AuthException(/*Sorry, this user is currently not activated. Please contact us for further assistance.*/'rainlab.user::lang.account.banned');
            }

            Flash::success('С возвращением, ' . $user->name . '!');

            return;
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

    /**
     * Обновление данных пользователя
     */
    public function onUpdate()
    {
        if (!$user = $this->user()) {
            return;
        }

        $data = post();
        if (!empty($data['username'])) $data['username'] = substr(preg_replace("/[^0-9]/", '',$data['username']), 1);


        $user->fill(['agent' => post('agent', false), 'personal' => post('personal', false)] + $data);
        $user->save();

        Flash::success('Данные успешно сохранены!');
        return;
    }

    /**
     * Регистрация пользователя
     */
    public function onRegister()
    {
        try {
            /*
             * Validate input
             */
            $data = post();
            if (!empty($data['username'])) $data['username'] = substr(preg_replace("/[^0-9]/", '',$data['username']), 1);

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            /*
             * Register user
             */
            Event::fire('rainlab.user.beforeRegister', [&$data]);

            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $user = Auth::register($data, $automaticActivation);

            Event::fire('rainlab.user.register', [$user, $data]);

            /*
             * Авторизация пользователя после регистрации
             */
            if ($automaticActivation || !$requireActivation) {
                Auth::login($user);
            }

            Flash::success('Добро пожаловать, ' . $user->name . '!');

            return;
        }
        catch (Exception $ex) {
            if (Request::ajax()) throw $ex;
            else Flash::error($ex->getMessage());
        }
    }

}



