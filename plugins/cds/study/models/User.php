<?php namespace Cds\Study\Models;

use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Models\User as RainUser;
use Carbon\Carbon;
use Auth;

/**
 * User Model
 */
class User extends RainUser
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;

    function __construct($attributes = [])
    {
        parent::__construct();
        $this->loadProperties();
    }

    public function loadProperties()
    {
        $this->fillable = array_merge($this->fillable, [
            'type', 'midname', 'birth_date', 'agent', 'name_org', 'inn', 'kpp', 'orgn', 'phone_org', 'personal'
        ]);

    }

    public $rules = [
        'email'    => 'required|between:6,255|email|unique:users',
        'avatar'   => 'nullable|image|max:4000',
        'username' => 'alpha_num|size:10|unique:users,username',
        'password' => 'required:create|between:6,255|confirmed',
        'password_confirmation' => 'required_with:password|between:6,255',
        'name' => 'required|alpha|between:1,255',
        'midname'    => ['first' => 'required','alpha','between:1,255'],
        'surname'    => ['first' => 'required','alpha','between:1,255'],
        'birth_date' => ['first' => 'required','date_format:Y-m-d','after:1900-01-01','before:tomorrow'],
        'name_org' => 'required|string|between:1,255',
        'inn' => 'required|string|between:10,12',
        'kpp' => 'required|string|size:9',
        'orgn' => 'required|string|size:13',
        'phone_org' => 'nullable|size:11',
    ];

    public $attributeNames = [
        'email' => 'E-mail',
        'avatar' => 'аватар',
        'username' => 'телефон',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'name' => 'имя',
        'midname' => 'отчество',
        'surname' => 'фамилия',
        'birth_date' => 'дата рождения',
        'name_org' => 'наименование организации',
        'inn' => 'ИНН',
        'kpp' => 'КПП',
        'orgn' => 'ОРГН',
        'phone_org' => 'телефон организации',
    ];

    public $customMessages = [
        'birth_date.before' => 'Дата рождения должна быть не позже текущей',
        'birth_date.after' => 'Дата рождения должна быть не ранее 1900 года',
        'inn.between' => 'ИНН организации должно содержать 10 или 12 символов',
        'username.size' => 'Поле телефон должно содержать 11 цифр',
        'password.between' => 'Пароль должен содержать от 6 до 255 символов',
        'password_confirmation.between' => 'Пароль должен содержать от 6 до 255 символов',
    ];

    public $nullable = ['username'];

    public $hasOne = [
        'resume' => [
            Resume::class
        ]
    ];

    public $hasMany = [
        'favourites' => [
            UserAction::class,
            'delete' => true,
            'order' => 'created_at desc',
            'conditions' => 'action = favorite'
        ],
        'history_views' => [
            UserAction::class,
            'delete' => true,
            'order' => ''
        ],
        'articles' => Article::class,
        'organizations' => [Organization::class]
    ];

    public $belongsToMany = [
        'organization_requests' => [
            'table' => 'cds_actions',
            Organization::class,
            'conditions' => "object_type = 'Cds\Study\Models\Organization' AND action = 'request' AND value > 0 ",
            'otherKey' => 'object_id',
            'pivot' => ['value', 'created_at', 'id'],
            ],
    ];

    public $attachOne = [
        'avatar' => CdsFile::class
    ];

    // ============================= Before After ================================= //

    public function beforeValidate()
    {
        //для физ лица можно убрать из валидации обятазательность фамилии, отчества и даты рождения
        if ($this->type == 1) {
            array_set($this->rules, 'surname.first', 'nullable');
            array_set($this->rules, 'midname.first', 'nullable');
            array_set($this->rules, 'birth_date.first', 'nullable');
            $this->rules = array_except($this->rules, ['name_org', 'inn', 'kpp', 'orgn', 'phone_org']);
        } else {
            if (strlen($this->inn) == 11) {
                throw new \ValidationException(['inn' => 'ИНН организации должно содержать 10 или 12 цифр']);
            }
        }

        if (!Auth::check()) {
            //Валидация формы при авторизации
            if ($username = $this->username) {
                if (strpos($username, '@') === false) {
                    //через телефон
                    $this->rules['username'] = 'nullable|alpha_num|size:10|unique:users,username';
                } else {
                    //через почту
                    $this->rules['username'] = 'required|email|between:6,255|unique:users';
                }
                $this->username  = $username;
            }
        }

        if ($this->phone_org) {
            $this->phone_org = preg_replace('/[^0-9]/', '', $this->phone_org);
        }

        $this->rules['password'] = "required:create|between:6,255|confirmed";
        $this->rules['password_confirmation'] = "required_with:password|between:6,255";
    }

    // ============================= Getters Setters ============================== //

    public function getFullNameAttribute()
    {
        return $this->surname . ' ' . $this->name . ' ' . $this->midname;
    }

    public function getBirthDateViewAttribute()
    {
        if (!empty($this->birth_date)) {
            return Carbon::parse($this->birth_date)->format('d.m.Y');
        }
    }

    public function getUsernameViewAttribute()
    {
        if (empty($this->attributes['username'])) return;
        return $this->attributes['username'];
    }

    public function setBirthDateAttribute($value)
    {
        //Должны ввести на форму в формате 01.01.1999, а положится в базу 1999-01-01
        if (!empty($value)) {
            try {
                $this->attributes['birth_date'] = Carbon::createFromFormat('d.m.Y', $value)->format("Y-m-d");
            } catch (\Exception $e) {
                throw new \ValidationException(['birth_date' => 'Дата рождения должна соответствовать формату День.Месяц.Год (31.12.1999)']);
            }
        }
    }

    // ============================= Scopes filter ================================ //
    // ============================= Make Scopes ================================== //
    // ============================= Protected Methods ============================ //
    // ============================= Public Methods =============================== //

    /**
     * Возвращаем аватар пользователя
     */
    public function getAvatarThumb($size = 25, $options = 'crop', $default = true)
    {
        $default = 'mm';

        if ($this->avatar) {
            return $this->avatar->getThumb($size, $size, ['mode' => $options]);
        }
        else {
            if ($default) return '/themes/main/assets/img/images/no_avatar.png';
            return '';
        }
    }

    /**
     * Возвращаем аватар пользователя для мобильной версии меню сайта
     */
    public function getAvatarThumbMobile($size = 50, $options = 'crop')
    {
        $default = 'mm';

        if ($this->avatar) {
            return $this->avatar->getThumb($size, $size, ['mode' => $options]);
        }
        else {
            return '/storage/app/media/no_avatar_mobile.svg';
        }
    }

}
