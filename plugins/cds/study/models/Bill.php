<?php namespace Cds\Study\Models;

use Model;
use Auth;
use Carbon\Carbon;

/**
 * bill Model
 */
class Bill extends Model
{
    use \October\Rain\Database\Traits\Validation;

    const STATUS = ['0' => 'Не оплачено', '1' => 'Оплачено', '2' => 'Отменен'];
    const OBJECT_TYPE = [Organization::class => 'Тарифы', Program::class => 'Приоритетное размещение'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_bills';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'object_id',
        'object_type',
        'user_id',
        'date_start',
        'date_end',
        'bill_property_id',
        'tariff_id',
        'cost',
        'status'
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'object_id' => 'required',
        'object_type' => 'required',
        'user_id' => 'required|exists:users,id',
        'date_start' => 'required|date',
        'date_end' => 'required|date',
        'bill_property_id' => 'required|exists:cds_study_bill_properties,id',
        'tariff_id' => 'required|exists:cds_study_tariffs,id',
        'cost' => 'required',
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
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'date_start',
        'date_end'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [

    ];
    public $hasMany = [];
    public $belongsTo = [
        'tariff' => [
            Tariff::class
        ],
        'prop' => [
            BillProperty::class,
            'key' => 'bill_property_id'
        ]
    ];
    public $belongsToMany = [];
    public $morphTo = [
        'object' => []
    ];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeMy($q)
    {
        if (!Auth::check()) {
            return null;
        }
        return $q->where('user_id', Auth::id());
    }

    public function scopeIncludedServices($query, $value)
    {
        return $query->whereHas('tariff', function ($qTar) use($value) {
            $qTar->whereHas('services', function ($qSer) use($value) {
                $qSer->whereIn('cds_study_services.id', $value);
            });
        });
    }

    // отбирает только оплаченные счета
    public function scopePaid($query)
    {
        return $query->where('status', 0);
    }

    // отбирает только счета в ожидании оплаты
    public function scopePending($query)
    {
        return $query->where('status', 1);
    }

    // отбирает только счета НЕ в ожидании оплаты
    public function scopeNotPending($query)
    {
        return $query->where('status', '<>', 1);
    }

    public function getStatusOptions()
    {
        return self::STATUS;
    }

    public function getTypeFinanceOptions()
    {
        return self::OBJECT_TYPE;
    }

    public function getDateActiveAttribute()
    {
        $dateStart = $this->date_start->format('d.m.Y г.');
        $dateEnd = $this->date_end->format('d.m.Y г.');
        return "c {$dateStart} по {$dateEnd}";
    }

    public function getDateActiveViewAttribute()
    {
        $dateStart = $this->date_start->format('d.m');
        $dateEnd = $this->date_end->format('d.m.y');
        return "{$dateStart} - {$dateEnd}";
    }

    public function getCreatedAtViewAttribute()
    {
        if (!empty($this->created_at)) {
            return $this->created_at->format('d F Y г.');
        }
    }

    public function getCostFormatedAttribute()
    {
        return self::getCostFormated($this->cost);
    }

    public function getCostNdsFormatedAttribute()
    {
        return self::getCostFormated($this->cost / 100 * 20);
    }

    /**
     * форматирование цены тарифа в человеко читаемый вид
     */
    public function getCostStrAttribute()
    {
        $value = $this->cost;
        $value = explode('.', number_format($value, 2, '.', ''));

        $f = new \NumberFormatter('ru', \NumberFormatter::SPELLOUT);
        $str = $f->format($value[0]);

        // Первую букву в верхний регистр.
        $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));

        // Склонение слова "рубль".
        $num = $value[0] % 100;
        if ($num > 19) {
            $num = $num % 10;
        }
        switch ($num) {
            case 1: $rub = 'рубль'; break;
            case 2:
            case 3:
            case 4: $rub = 'рубля'; break;
            default: $rub = 'рублей';
        }

        return $str . ' ' . $rub . ' ' . $value[1] . ' копеек.';
    }

    /**
     * формируем текст комментария для отчёта по финансовые операциям
     */
    public function getCommentTextAttribute()
    {
        $text = '';
        $object = $this->object;

        $objectName = empty($this->object) ? '<удалено>' : $object->name;
        $objectOrgName = empty($object->organization) ? '<удалено>' : $object->organization->name;
        $tariffName = empty($this->tariff) ? '<удалено>' : $this->tariff->name;

        if ($this->object_type == Program::class) {
            $text = "Оплата приоритетного размещения для программы \"{$objectName}\" в компании \"{$objectOrgName}\"";
            $text .= " по счету №{$this->id} от {$this->createdAtView}";
        } else {
            $text = "Оплата тарифа \"{$tariffName}\" для компании \"{$objectName}\"";
            $text .= " по счету №{$this->id} от {$this->createdAtView}";
        }

        return $text;
    }

    function getTermAttribute() {
        return ''.$this->date_start->format('\с d.m.Y') . $this->date_end->format(' по d.m.Y');
    }

    public function getNearEndStatusAttribute()
    {
        $now = Carbon::now();
        return $this->date_end->diffInDays($now);
    }

    public static function getCostFormated($value)
    {
        return number_format($value, 2, '.', '&nbsp;');
    }

    function afterSave() {
        if ($this->wasChanged('status') and $this->status == 1)
            $this->makePaied();
    }

    function makePaied() {
        $records = $this->tariff->assignTo($this->object, $this->date_start, $this->date_end);
        return $records;
    }
}
