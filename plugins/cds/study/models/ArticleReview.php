<?php namespace Cds\Study\Models;

use Model;
use Auth;

/**
 * ArticleReview Model
 */
class ArticleReview extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_article_reviews';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['user_id', 'article_id', 'status', 'text', 'session_token'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'article_id' => 'required|exists:cds_study_articles,id',
        'user_id' => 'exists:users,id',
        'status' => 'required',
        'text' => 'nullable|string|max:255'
    ];

    public $customMessages = [
        'article_id.required' => 'Идентификатор статьи пустой, обратитесь к администратору сайта',
        'article_id.exists' => 'Такой статьи не существует, обновите страницу',

        'user_id.exists' => 'Ваш пользователь не существует',

        'status.required' => 'Статус ответа обязателен для заполнения',

        'text.string' => 'Отзыв должен быть строкой',
        'text.max' => 'Максимальная длина отзыва 255 символов'
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = ['statusNum'];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'article' => Article::class,
        'user' => User::class
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [
        'notification' => [
            Notification::class,
            'name' => 'object',
        ],
    ];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function afterCreate()
    {
        //отправка автору статьи уведомления о том, что его статью оценили
        $article = $this->article;
        if (!empty($article->user_id)) {
            $data['user_id'] = $article->user_id;
            $data['type'] = 9;
    
            $this->notification()->create($data);
        }
    }

    public function scopeMy($q)
    {
        $sessionToken = \Session::get('_token');
        if (!Auth::check()) {
            return $q->where('session_token', $sessionToken);
        }
        return $q->where('user_id', Auth::id());
    }

    public function getStatusNumAttribute()
    {
        return $this->status ? 1 : -1;
    }
}
