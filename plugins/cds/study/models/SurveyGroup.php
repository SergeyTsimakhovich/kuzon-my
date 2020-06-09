<?php namespace Cds\Study\Models;

use Model;
use Session;

/**
 * SurveyGroup Model
 */
class SurveyGroup extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_survey_groups';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['title'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'title' => 'required|string|max:255',
        'realms' => 'required',
        'sectors' => 'required',
    ];

    public $attributeNames = [
        'title' => 'наименование анкеты',
    ];

    public $customMessages = [
        'realms.required' => 'Привяжите к опросу минимум 1 регион',
        'sectors.required' => 'Привяжите к опросу минимум 1 категорию',
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
    public $hasMany = [
        'surveys' => [
            Survey::class
        ],
        'answer_users' => [
            SurveyAnswerUser::class
        ],
        'answer_users_closed' => [
            SurveyAnswerUser::class,
            'scope' => 'closedSurveyGroup'
        ],
        'answer_users_real' => [
            SurveyAnswerUser::class,
            'scope' => 'real'
        ]
    ];
    public $belongsTo = [];
    public $belongsToMany = [
        'realms' => [
            Realm::class,
            'table' => 'cds_study_survey_group_realms',
            'scope' => 'regions'
        ],
        'sectors' => [
            Sector::class,
            'table' => 'cds_study_survey_group_sectors',
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
     * scope отксекает опросы по региону и категории
     * @param $q
     * @param array $data
     * @return mixed
     */
    public function scopeByRealmAndSector($q, $sectorIds, $url)
    {
        $realm = Session::get('realm');
        $token = Session::get('_token');

        $userAnswerSurveyGroupId = SurveyAnswerUser::where('token', $token)
            ->where(function ($q) use($url) {
                $q->where('is_closed', '<>', 1)->where('link', '<>', $url);
            })
            ->select('survey_group_id')->distinct('survey_group_id')->lists('survey_group_id');

        if (empty($realm)) return null;
        $realmId = $realm['isArea'] ? $realm['id'] : $realm['parent_id'];

        $query = $q->active()
            //ищем опрос по городу
            ->whereHas('realms', function ($qRealms) use($realmId) {
                $qRealms->where('cds_study_realms.id', $realmId);
            })
            //и по категориям
            ->whereHas('sectors', function ($qSectors) use($sectorIds) {
                $qSectors->whereIn('cds_study_sectors.id', $sectorIds);
            })
            ->whereNotIn('id', $userAnswerSurveyGroupId)
            ->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * scope ищет для текущего пользователя (по сессии), не проходил ли он найденный опрос
     * @param $q
     * @param array $data
     * @return mixed
     */
    public function scopeByData($q, $data = [])
    {
        $token = Session::get('_token');
        $query = $q->active()
            ->where('id', $data['survey_group_id'])
            ->whereDoesntHave('answer_users', function ($qAnswer) use($data, $token) {
                $qAnswer
                    //ищем записи, если на этой странице закрыли опрос
                    ->where(function ($qClosed) use($data, $token) {
                        $qClosed
                            ->where('token', $token)
                            ->where('survey_group_id', $data['survey_group_id'])
                            ->where('link', $data['url'])
                            ->where('is_closed', 1);
                    })
                    //ищем записи, если на этой странице не закрыли опрос и ответили на вопрос
                    ->orWhere(function ($qNotClosed) use($data, $token)  {
                        $qNotClosed
                            ->where('survey_group_id', $data['survey_group_id'])
                            ->where('token', $token)
                            ->where('is_closed', 0);
                    });
            })
            ->withSurveys();

        return $query;
    }

    public function scopeWithSurveys($q)
    {
        $query = $q->with(['surveys' => function ($qSurveys) {
            return $qSurveys->with('answers')->orderBy('id', 'asc');
        }]);

        return $query;
    }
}
