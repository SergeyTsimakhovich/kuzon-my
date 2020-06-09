<?php namespace Cds\Study\Models;

use Model;
use Session;

/**
 * FormCapture Model
 */
class FormCapture extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_form_captures';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'survey',
        'answer_no',
        'answer_yes'
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'survey' => 'required|string|between:1,255',
        'answer_no' => 'required|string|between:1,255',
        'answer_yes' => 'required|string|between:1,255',
        'realms' => 'required',
        'sectors' => 'required',
    ];

    public $attributeNames = [
        'survey' => 'вопрос',
        'answer_no' => 'отрицательный ответ',
        'answer_yes' => 'положительный ответ',
    ];

    public $customMessages = [
        'realms.required' => 'Привяжите к форме захвата минимум 1 город',
        'sectors.required' => 'Привяжите к форме захвата минимум 1 категорию',
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
        'updated_at'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [
        'realms' => [
            Realm::class,
            'table' => 'cds_study_form_capture_realms',
            'scope' => 'cities'
        ],
        'sectors' => [
            Sector::class,
            'table' => 'cds_study_form_capture_sectors',
        ]
    ];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }

    /**
     * scope отксекает формы захвата по городу и категории
     * @param $q
     * @param array $data
     * @return mixed
     */
    public function scopeByRealmAndSector($q, $sectorIds)
    {
        $realm = Session::get('realm');
        $token = Session::get('_token');

        //ищем ID всех форм захвата с которыми взаимодействовал пользователь
        $userAnswerFormCaptureId = FormCaptureUserAnswer::where('token', $token)
            ->select('form_capture_id')
            ->distinct('form_capture_id')
            ->lists('form_capture_id');

        if (empty($realm)) return null;
        $reamIds = $realm['isArea'] ? $realm['city'] : [$realm['id']];

        $query = $q->active()
            //ищем форму захвата по городу
            ->whereHas('realms', function ($qRealms) use($reamIds) {
                $qRealms->whereIn('cds_study_realms.id', $reamIds);
            })
            //и по категориям
            ->whereHas('sectors', function ($qSectors) use($sectorIds) {
                $qSectors->whereIn('cds_study_sectors.id', $sectorIds);
            })
            ->whereNotIn('id', $userAnswerFormCaptureId)
            ->orderBy('created_at', 'desc');

        return $query;
    }
}
