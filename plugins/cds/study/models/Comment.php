<?php

namespace Cds\Study\Models;

use Model;
use Auth;

/**
 * Comment Model
 */
class Comment extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;

    CONST STATUS = ['0' => 'В ожидании', '1' => 'Одобрено', '2' => 'Отклонено'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_comments';

    public $attributes = [
        'status' => 0,
        'user_id' => null,
        'parent_id' => null,
        'guest_name' => null,
    ];

    public $nullable = ['user_id', 'parent_id', 'guest_name'];

    public $rules = [
        'parent_id' => 'nullable|exists:cds_study_comments,id',
        'body' => 'required|string|between:1,255',
        'status' => 'required|integer|between:0,2',
        'guest_name' => 'nullable|string|between:3,255',
    ];

    public $attributeNames  = [
        'user_id' => 'пользователь',
        'parent_id' => 'родительский комментарий',
        'body' => 'комментария',
        'status' => 'статус модерации',
        'guest_name' => 'имя анонимного пользователя',
    ];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'user_id',
        'parent_id',
        'body',
        'rate',
        'status',
        'object_id',
        'object_type',
        'guest_name',
        'guest_session_token',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'user' => User::class,
        'parent' => [
            Comment::class
        ]
    ];
    public $belongsToMany = [];
    public $morphTo = [
        'object' => []
    ];
    public $morphOne = [
        'my_rate' => [
            UserAction::class,
            'name' => 'object',
            'conditions' => "action = 'rate'",
            'scope' => 'my'
        ],
        'notification' => [
            Notification::class,
            'name' => 'object',
        ],
    ];
    public $morphMany = [
        'rates' => [
            UserAction::class,
            'name' => 'object',
            'conditions' => "action = 'rate'"
        ],
    ];
    public $attachOne = [];
    public $attachMany = [];

    // ============================= Relation ===================================== //

    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id')->active()
            ->with(['children', 'rates', 'my_rate'])
            ->orderby('created_at', 'desc');
    }

    // ============================= Before After ================================= //

    public function beforeValidate()
    {

        //Формируем текст ошибки для разных страниц с комментариями
        if ($this->object instanceof Organization) {
            $this->attributeNames['body'] = 'отзыва';
        } else if ($this->object instanceof About) {
            $this->attributeNames['body'] = 'вопроса';
        } else {
            $this->attributeNames['body'] = 'комментария';
        }
    }

    public function afterUpdate()
    {
        //отправляем уведомление автору статьи или организции, если комментарий одобрили
        if ($this->status == 1 && !empty($this->object->user_id)) {
            $data = [];
            $data['user_id'] = $this->object->user_id;
            $data['type'] = $this->object_type == Article::class ? 0 : 1;

            $this->notification()->create($data);

            if (!empty($this->rate)) {
                $data['type'] = 3;
                $this->notification()->create($data);
            }

            if (!empty($this->parent_id)) {
                $data['user_id'] = $this->parent->user_id;
                $data['type'] = 8;

                $this->notification()->create($data);
            }
        }
    }

    // ============================= Getters Setters ============================== //

    public function getObjectNameAttribute()
    {
        if ($object = $this->object instanceof Organization){
            return $object->name;
        }

        if ($object = $this->object instanceof Article){
            return $object->title;
        }

        return 'Помощь';
    }

    public function getStatusViewAttribute()
    {
        return self::STATUS[$this->status];
    }

    public function getLinkAttribute()
    {
        return $this->object->link . "/#commentdiv-{$this->id}";
    }

    public function getCreatedDateAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // ============================= Scopes filter ================================ //

    public function scopeActive($q)
    {
        return $q->where('status', 1);
    }

    public function scopeNew($q)
    {
        if (!Auth::check()) return null;
        return $q->active()->whereHas('notification', function ($qNotify) {
            $qNotify->where('user_id', Auth::id())->where('read_at', null);
        });
    }

    public function scopeByObject($q, $prop = [])
    {
        $prop['object_type'] = "Cds\Study\Models\\" . ucfirst($prop['object_type']);
        return $q->active()->where('object_type', $prop['object_type'])->where('object_id', $prop['object_id'])->where('parent_id', null);
    }

    // ============================= Make Scopes ================================== //
    // ============================= Protected Methods ============================ //
    // ============================= Public Methods =============================== //

    public function getUserLabel()
    {
        if (($user_comment = $this->user) && ($user_article = $this->object->user)) {
            return $user_comment->id == $user_article->id
                ? 'Автор'
                : 'Пользователь';
        } elseif (!empty($user_comment)) {
            return 'Пользователь';
        } else {
            return 'Аноним';
        }

    }

    public function getAnonymAvatar($size)
    {
        return '//www.gravatar.com/avatar/?s=' . $size. '&d=mm';
    }

    public function getChildCount()
    {
        $count = 0;

        if (!empty($this->children)) {
            $count += count($this->children);
            foreach ($this->children as $item) {
                $count += $item->getChildCount();
            }
        }

        return $count;

    }

    public function getStatusOptions()
    {
        return self::STATUS;
    }

}
