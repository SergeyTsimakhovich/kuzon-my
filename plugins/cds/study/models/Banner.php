<?php namespace Cds\Study\Models;

use Carbon\Carbon;
use Model;


/**
 * Banner Model
 */
class Banner extends Model
{
    use \October\Rain\Database\Traits\Validation;

    const SIZE = [
        'footer_wide' => ['w' => 540, 'h' => 240],
        'footer_slim' => ['w' => 300, 'h' => 120],
    ];
    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_banners';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['published', 'title', 'group_id', 'date_start', 'date_end', 'link', 'new_window', 'image_link'];

    protected $appends = ['thumbnail'];

    protected $rules = [
        'title' => 'required|max:255',
        'group_id' => 'required|exists:cds_study_banner_groups,id',
        'date_start' => 'required|date',
        'date_end' => 'required|date',
        'link' => 'required|url|max:255',
        'image' => 'required|max:20480',
    ];

    protected $customMessages = [
        'title.required' => 'Заголовок обязателен для заполнения',
        'title.max' => 'Длина заголовка должна быть не более 255 символов',
        'group_id.required' => 'Выберите группу публикации',
        'group_id.exists' => 'Такой публикации группы не существует',
        'date_start.required' => 'Выберите дату начала показа',
        'date_start.date' => 'Неверный формат даты начала показа',
        'date_end.required' => 'Выберите дату завершения показа',
        'date_end.date' => 'Неверный формат даты завешения показа',
        'link.required' => 'Заполните ссылку для перехода',
        'link.url' => 'Введите корректную ссылку для перехода',
        'link.max' => 'Ссылка для перехода должна содержать не более 255 символов',
        'image.required' => 'Необходимо загрузить баннер (не больше 20 мбайт)',
        'image.max' => 'Необходимо загрузить баннер (не больше 20 мбайт)',
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [

    ];
    public $hasMany = [

    ];
    public $belongsTo = [
        'group' => [
            BannerGroup::class,
            'key' => 'group_id',
            'otherKey' => 'id'
        ]
    ];
    public $belongsToMany = [

    ];
    public $morphTo = [];
    public $morphOne = [

    ];
    public $morphMany = [

    ];
    public $attachOne = [
        'image' => ['System\Models\File']
    ];
    public $attachMany = [];

    public function scopePublished($q)
    {
        return $q->where('published', true);
    }

    public function scopeDateActive($q)
    {
        $date = Carbon::now();
        return $q->whereDate('date_start', '<=', $date)->whereDate('date_end', '>=', $date);
    }

    public function getThumbnailAttribute()
    {
        $size = self::SIZE[$this->group->title];

        $image = $this->image;
        if (!empty($image)) {
            return $image->getThumb($size['w'], $size['h'], 'crop');
        }
        return null;
    }
}
