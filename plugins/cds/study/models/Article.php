<?php namespace Cds\Study\Models;

use Model;
use Auth;
use System\Models\File;

/**
 * Article Model
 */
class Article extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;
    use \October\Rain\Database\Traits\Purgeable;
    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_articles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    protected $nullable = ['author', 'source', 'viewed', 'user_id'];

    /**
     * @var array List of attributes to purge.
     */
    protected $purgeable = ['desc_count', 'body_count'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'published_at',
        'published',
        'viewed',
        'title',
        'description',
        'body',
    ];

    protected $appends = [
        'authorLink',
        'authorName',
        'authorLinkMail',
        'link',
        'nextArticle',
        'prevArticle',
        'reviewsCount',
        'publishedAtView',
        'thumbnailCard',
        'titleView',
        'descriptionView',
    ];

    public $rules = [
        'author' => 'nullable|string|max:255',
        'title'  => 'required|string|max:255',
        'description'  => 'required|string|max:1000',
        'published_at'  => 'required|date',
        'body'  => 'required|string|min:1000',
        'source' => 'nullable|url',
        'user_id' => 'nullable|exists:users,id',
        'slug' => 'required|string|max:255|unique:cds_study_articles,slug'
    ];

    public $attributeNames = [
        'slug' => 'наименование в URL',
        'title' => 'название статьи, новости',
        'description' => 'краткое описание статьи, новости',
        'body' => 'текст статьи, новости',
        'source' => 'источник статьи, новости',
        'author' => 'автор статьи, новости',
        'user_id' => 'автор (пользователь сайта) статьи, новости',
        'published_at' => 'дата публикации',
    ];

    protected $dates = ['created_at', 'updated_at', 'published_at'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $belongsTo = [
        'user' => User::class,
    ];
    public $hasMany = [
        'reviews' => [
            ArticleReview::class,
            'scope' => 'my'
        ],
        'reviews_all' => [
            ArticleReview::class,
        ],
        'reviews_count_yes' => [
            ArticleReview::class,
            'condition' => 'status = true'
        ],
        'reviews_count_no' => [
            ArticleReview::class,
            'condition' => 'status = false'
        ]
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [
        'notification' => [
            Notification::class,
            'name' => 'object',
        ],
    ];
    public $morphMany = [
        'views' => [
            UserAction::class,
            'name' => 'object',
            'conditions' => "action = 'view'"
        ],
        'favourite' => [
            UserAction::class,
            'name' => 'object',
            'conditions' => "action = 'favourite'",
            'scope' => 'my'
        ],
        'comments' => [
            Comment::class,
            'name' => 'object',
            'scope' => 'active'
        ],
        'comments_new' => [
            Comment::class,
            'name' => 'object',
            'scope' => 'new'
        ]
    ];
    public $attachOne = [];
    public $attachMany = [
        'image' => File::class
    ];

    // ============================= Before After ================================= //

    public function beforeValidate()
    {
        if (!empty($this->author) && strpos($this->author, '@') !== false) {
            $this->rules['author'] = 'email';
        }

        //Для корректной работы счетика символов в админке при добавлении и редактировании статьи
        if (mb_strlen(preg_replace("#\\\r|\\\n|\\\t#", "", $this->body)) < 1000) {
           throw new \ValidationException(['body' => 'Текст статьи должен содержать минимум 1000 символов!']);
        }

        if (mb_strlen(preg_replace("#\\\r|\\\n|\\\t#", "", $this->description)) > 512) {
            throw new \ValidationException(['description' => 'Поле Краткое описание должно быть не длиннее 512 символов!']);
        }
    }

    public function afterSave()
    {
        //отправляем уведомление автору статьи, если к нему привязали статью
        $notification = Notification::where('object_type', Article::class)
            ->where('object_id', $this->id)
            ->where('type', 6)
            ->first();

        if (empty($notification) && !empty($this->user_id)) {
            $data = [];
            $data['user_id'] = $this->user_id;
            $data['type'] = 6;

            $this->notification()->create($data);
        }
    }

    // ============================= Getters Setters ============================== //

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = empty($value) ? str_slug($this->title) : $value;
    }

    public function getSlugAttribute()
    {
        return empty($this->attributes['slug']) ? str_slug($this->title) : $this->attributes['slug'];
    }

    public function getNameAttribute()
    {
        return $this->title;
    }

    public function getDescCountAttribute()
    {
        return 'Краткое описание: ' . mb_strlen($this->description) . '/512 символов';
    }

    public function getBodyCountAttribute()
    {
        return 'Текст статьи: ' . (mb_strlen(preg_replace("#\\\r|\\\n|\\\t#", "", $this->body))) . ' символов (минимум 1000)';
    }

    public function getAuthorLinkAttribute()
    {
        if (empty($this->user_id)) return false;
        return "articles/user/{$this->user_id}";
    }

    public function getAuthorNameAttribute()
    {
        return !empty($this->author) ? $this->author : (!empty($this->user) ? $this->user->fullName : 'Автор неизвестен');
    }

    public function getLinkAttribute()
    {
        return "articles/{$this->slug}";
    }

    public function getAuthorLinkMailAttribute()
    {
        if (!empty($this->author)) {
            return strpos($this->author, '@') !== false ? 'mailto: ' . $this->author : false;
        } else {
            return false;
        }
    }

    public function getNextArticleAttribute()
    {
        if (empty($this->user)) return false;

        $item = $this->user->articles()->isPublished()->where('id', '>', $this->id)->orderBy('id', 'asc')->first();
        if (!empty($item)) return $item->link;

        return false;
    }

    public function getPrevArticleAttribute()
    {
        if (empty($this->user)) return false;

        $item = $this->user->articles()->isPublished()->where('id', '<', $this->id)->orderBy('id', 'desc')->first();
        if (!empty($item)) return $item->link;

        return false;

    }

    public function getReviewsCountAttribute()
    {
        $reviews = $this->reviews_all()->get()->toArray();
        return array_sum(array_column($reviews, 'statusNum'));
    }

    public function getPublishedAtViewAttribute()
    {
        if (!empty($this->published_at)) {
            return $this->published_at->format('d.m.Y');
        }
    }

    public function getThumbnailCardAttribute()
    {
        $image = $this->image->first();
        if (!empty($image)) {
            return $this->image->first()->getThumb(285, 130, 'crop');
        } else {
            return "/themes/main/assets/img/images/no-photo.png";
        }
    }

    public function getTitleViewAttribute()
    {
        return str_limit($this->title, 100);
    }

    public function getDescriptionViewAttribute()
    {
        return str_limit(strip_tags($this->description), 140);
    }

    public function getAnonymAvatar()
    {
        return '/themes/main/assets/img/images/no_avatar.png';
    }

    // ============================= Scopes filter ================================ //

    public function scopeIsPublished($q)
    {
        return $q->where('published', true)->whereNotNull('published_at');
    }

    public function scopeNotPublished($q)
    {
        return $q->whereNull('published');
    }

    public function scopeByUserId($q, $user_id)
    {
        if (empty($user_id)) {
            return;
        }
        return $q->where('user_id', $user_id);
    }

    // ============================= Make Scopes ================================== //
    // ============================= Protected Methods ============================ //
    // ============================= Public Methods =============================== //

    /**
     * добавляем просмотр для статьи
     */
    public function addView()
    {
        $sessionToken = \Session::get('_token');
        $view = $this->views()->where('session_token', $sessionToken)->first();

        if (!empty($view)) {
            return;
        }

        $this->views()->create([
            'action' => 'view',
            'user_id' => Auth::id(),
            'session_token' => $sessionToken
        ]);
    }
}
