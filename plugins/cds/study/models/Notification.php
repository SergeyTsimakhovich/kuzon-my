<?php namespace Cds\Study\Models;

use Model;
use Mail;
use Auth;
use Lang;

/**
 * Notification Model
 */
class Notification extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;

    protected $typesNotification = [
        0 => ['title' => 'Новый комментарий к Вашей статье', 'class' => 2],
        1 => ['title' => 'Новый комментарий в Вашей организации', 'class' => 4],
        2 => ['title' => 'Новая оценка Вашего комментария', 'class' => 5], //5-дизлайк 6-лайк
        3 => ['title' => 'Новая оценка Вашей организации', 'class' => 4],
        4 => ['title' => 'Вам назначена организация', 'class' => 7],
        5 => ['title' => 'Ваша новая организация принята в каталог', 'class' => 7],
        6 => ['title' => 'Ваша статья принята на публикацию', 'class' => 3],
        7 => ['title' => 'У вашего комментария больше N лайков', 'class' => 8],
        8 => ['title' => 'Ответ на Ваш комментарий', 'class' => 1],
        9 => ['title' => 'Оценка статьи', 'class' => 5], //5-дизлайк 6-лайк
        10 => ['title' => 'Изменения в Вашей организации одобрены администратором', 'class' => 7],
        11 => ['title' => 'Изменения в Вашей организации отклонены администратором', 'class' => 9],
        12 => ['title' => 'Ваша новая программа принята в каталог', 'class' => 7],
        13 => ['title' => 'Изменения в Вашей программе одобрены администратором', 'class' => 7],
        14 => ['title' => 'Изменения в Вашей программе отклонены администратором', 'class' => 9],
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_notifications';

    public $nullable = ['read_at', 'text'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['user_id', 'type', 'object_type', 'object_id', 'text', 'url', 'read_at'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'user_id' => 'required|exists:users,id',
        'type' => 'required|between:0,6',
        'text' => 'nullable|between:1,255'
    ];

    public $attributeNames = [
        'user_id' => 'Пользователь',
        'type' => 'Тип уведомления',
        'text' => 'Текст уведомления'
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
    protected $appends = ['createdAtView'];

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
        'user' => User::class
    ];
    public $belongsToMany = [];
    public $morphTo = [
        'object' => []
    ];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function afterCreate()
    {
        //если создали уведомления по оценке комментариев, тогда считаем количество лайков и уведомляем автора комментария если набрали больше N лайков
        if ($this->type == 2) {
            $maxLike = Setting::get('max_like_notification',0);
            $userAction = $this->object;

            $actionCount = UserAction::where('object_type', Comment::class)
                ->where('object_id', $userAction->object_id)
                ->where('action', 'rate')
                ->where('value', '>', 0)
                ->get()
                ->count();

            if (!empty($actionCount) && $actionCount > $maxLike) {
                $data = [
                    'object_type' => Comment::class,
                    'object_id' => $userAction->object_id,
                    'user_id' => $this->user_id,
                    'type' => 7,
                    'text' => $maxLike,
                ];

                $notify = Notification::where('object_type', $data['object_type'])
                    ->where('object_id', $data['object_id'])
                    ->where('user_id', $data['user_id'])
                    ->where('type', $data['type'] )
                    ->where('text', $data['text'] )
                    ->first();

                if (empty($notify)) {
                    $this->create($data);

                    $dataMail = [
                        'name' => $this->user->full_name,
                        'max_like' => $maxLike,
                        'link' => $this->object->object->link
                    ];

                    Mail::send('rainlab.user::mail.like_notification', $dataMail, function ($message) {
                        $message->to($this->user->email, $this->user->full_name);
                    });
                }

            }
        }
    }

    public function getCreatedAtViewAttribute()
    {
        return $this->created_at->format('d.m.Y');
    }

    public function scopeMy($q)
    {
        if (!Auth::check()) return;
        return $q->where('user_id', Auth::id());
    }

    public function scopeReadAt($q)
    {
        return $q->whereNotNull('read_at');
    }

    public function scopeNew($q)
    {
        return $q->whereNull('read_at');
    }

    public function scopeIsRead($q)
    {
        return $q->where('is_read', null);
    }

    /**
     * Метод для формирования текста уведомления
     */
    public function getText()
    {
        $data = [
            'class' => $this->typesNotification[$this->type]['class'],
        ];

        if (in_array($this->type, [0, 1, 2, 3, 8]) && !empty($this->object))  {
            $user = $this->object->user;
            $data['author_name']   = !empty($user) ? $user->fullName : $this->object->guest_name ?? 'Аноним';
            $data['author_avatar'] = !empty($user) ? $user->getAvatarThumb(10, 'crop'): $this->object->getAnonymAvatar(10);
        }

        switch ($this->type) {
            case 0: //коммент к статье
                $comment = $this->object;
                if (empty($comment)) {
                    $data['status_text'] = 'Комментарий удален.';
                    break;
                }

                $data['status_text'] = " прокомментировал статью:";

                $article = $comment->object;
                if (empty($article)) {
                    $data['content'] = 'Ваша статья удалена.';
                    $data['error'] = true;
                    break;
                }
                $data['content'] = $article->title;
                $data['link'] = "/" . $comment->link;
                break;
            case 1: //отзыв к организации
                $comment = $this->object;
                if (empty($comment)) {
                    $data['status_text'] = 'Комментарий удален.';
                    break;
                }

                $organization = $comment->object;
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = " оставил отзыв к организации:";
                $data['content'] = $comment->object->name;
                $data['link'] = "/" . $comment->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 2: //оценка коммента
                $action = $this->object;
                if (empty($action)) break;

                $comment = $this->object->object;
                if (empty($comment)) {
                    $data['status_text'] = 'Ваш комментарий удален.';
                    break;
                }

                $value = $action->value > 0 ? 'одобрил' : 'не одобрил';
                $data['class'] = $action->value > 0 ? 6 : 5;

                $data['status_text'] = " {$value} Ваш комментарий:";
                $data['content'] = $comment->body;
                $data['link'] = "/" . $comment->link;
                break;
            case 3: //оценка организации
                $comment = $this->object;
                if (empty($comment)) break;

                $organization = $comment->object;
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $value = $comment->rate;
                $str = Lang::choice('звезду|звезды|звёзд', $value, [], 'ru');

                $data['status_text'] = " оценил Вашу организацию в {$value} {$str}:";
                $data['content'] = $organization->name;
                $data['link'] = "/" . $organization->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 4: //когда пользователю назначили организацию
                $organization = $this->object;
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = "Вам назначена организация:";
                $data['content'] = $organization->name;
                $data['link'] = $organization->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 5: //когда пользователь добавил новую организацию и её одобрили
                $organization = $this->object()->withoutModerate()->first();
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $status = 'не определено';
                if ($this->text == '2') {
                    $status = 'одобрена' ;
                    $data['class'] = 7;
                } elseif ($this->text == '3') {
                    $status = 'отклонена' ;
                    $data['class'] = 9;
                }

                $data['status_text'] = "Ваша новая организация {$status} администратором:";
                $data['content'] = $organization->name;
                $data['link'] = $organization->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 12: //когда пользователь добавил новую программу и её одобрили
                $program = $this->object()->withoutModerate()->first();
                if (empty($program)) {
                    $data['status_text'] = 'Ваша программа обучения удалена.';
                    break;
                }
                
                if (empty($program->organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $status = 'не определено';
                if ($this->text == '2') {
                    $status = 'одобрена' ;
                    $data['class'] = 7;
                } elseif ($this->text == '3') {
                    $status = 'отклонена' ;
                    $data['class'] = 9;
                }

                $data['status_text'] = "Ваша новая программа обучения {$status} администратором:";
                $data['content'] = $program->name . ' в ' . $program->organization->name;
                $data['link'] = $program->link;
                $data['org_info'] = "{$program->organization->region}, {$program->organization->address}";
                break;
            case 6: //когда админ опубликовал статью пользователя
                $article = $this->object;
                if (empty($article)) {
                    $data['status_text'] = 'Ваша статья удалена.';
                    break;
                }

                $data['status_text'] = "Ваша статья опубликована администратором:";
                $data['content'] = $article->title;
                $data['link'] = "/" . $article->link;
                break;
            case 7: //когда коммент набрал много лайков
                $comment = $this->object;
                if (empty($comment)) {
                    $data['status_text'] = 'Комментарий удален.';
                    break;
                }

                $data['status_text'] = "Ваш комментарий получил более {$this->text} одобрений:";
                $data['content'] = $comment->body;
                $data['link'] = "/" . $comment->link;
                break;
            case 8: //когда на ответили на комментарий
                $comment = $this->object;
                if (empty($comment)) {
                    $data['status_text'] = 'Ответ на Ваш комментарий удален.';
                    break;
                }

                if (empty($comment->parent)) {
                    $data['status_text'] = 'Ваш комментарий удален.';
                    break;
                }

                $data['status_text'] = " ответил на Ваш комментарий:";

                $data['content'] = $comment->parent->body;
                $data['link'] = "/" . $comment->link;
                break;
            case 9: //оценка статьи
                $review = $this->object;
                if (empty($review)) {
                    $data['status_text'] = 'Отзыв удален.';
                    break;
                }

                $article = $review->article;
                if (empty($article)) {
                    $data['status_text'] = 'Ваша статья удалена.';
                    break;
                }

                $value = $review->status ? 'положительно' : 'отрицательно';
                $data['class'] = $review->status ? 6 : 5;

                $data['status_text'] = " Вашу статью оценили {$value}:";
                $data['content'] = $article->title;
                $data['link'] = "/" . $article->link;
                break;
            case 10: //когда пользователь изменил организацию и её одобрили
                $organization = $this->object;
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = "Изменения в Вашей организации одобрены администратором:";
                $data['content'] = $organization->name;
                $data['link'] = $organization->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 11: //когда пользователь изменил организацию и её отклонили
                $organization = $this->object;
                if (empty($organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = "Изменения в Вашей организации отклонены администратором:";
                $data['content'] = $organization->name;
                $data['link'] = $organization->link;
                $data['org_info'] = "{$organization->region}, {$organization->address}";
                break;
            case 13: //когда пользователь изменил программу и её одобрили
                $program = $this->object;
                if (empty($program)) {
                    $data['status_text'] = 'Ваша программа обучения удалена.';
                    break;
                }
                
                if (empty($program->organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = "Ваши изменения программе обучения одобрены администратором:";
                $data['content'] = $program->name . ' в ' . $program->organization->name;
                $data['link'] = $program->link;
                $data['org_info'] = "{$program->organization->region}, {$program->organization->address}";
                break;
            case 14: //когда пользователь изменил программу и её отклонили
                $program = $this->object;
                if (empty($program)) {
                    $data['status_text'] = 'Ваша программа обучения удалена.';
                    break;
                }
                
                if (empty($program->organization)) {
                    $data['status_text'] = 'Ваша организация удалена.';
                    break;
                }

                $data['status_text'] = "Ваши изменения программе обучения отклонены администратором:";
                $data['content'] = $program->name . ' в ' . $program->organization->name;
                $data['link'] = $program->link;
                $data['org_info'] = "{$program->organization->region}, {$program->organization->address}";
                break;
        }

        if (empty($data['status_text'])) $data['status_text'] = 'Данные уведомления утеряны или повреждены.';

        return $data;
    }
}
