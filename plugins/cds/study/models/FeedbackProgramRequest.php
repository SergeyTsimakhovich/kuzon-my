<?php namespace Cds\Study\Models;

use Model;
use Carbon\Carbon;
use Mail;

/**
 * FeedbackProgramRequest Model
 */
class FeedbackProgramRequest extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Nullable;

    const EDU_LEVELS = [
        0 => ['title' => 'Среднее'],
        1 => ['title' => 'Средне-специальное'],
        2 => ['title' => 'Специальное'],
        3 => ['title' => 'Высшее'],
        4 => ['title' => 'Высшее (неоконченное)'],
        5 => ['title' => 'Бакалавриат'],
        6 => ['title' => 'Магистратура'],
        7 => ['title' => 'Аспирантура'],
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cds_study_feedback_program_requests';

    public $attributes = [
        'status' => false,
        'resume_id' => null
    ];

    public $nullable = [
        'status',
        'resume_id'
    ];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'resume_id',
        'object_id',
        'object_type',
        'fio',
        'edu_level',
        'birth_date',
        'phone',
        'email',
        'status',
        'body',
        'type',
        'program_id',
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [
        'object_id' => 'required|exists:cds_study_programs,id',
        'resume_id' => 'nullable|exists:cds_study_resumes,id',
        'birth_date' => "nullable|date_format:Y-m-d|after:1900-01-01|before:tomorrow",
        'phone' => 'nullable|alpha_num|size:11',
        'email' => 'nullable|email|between:6,255',
        'fio' => 'required|string|max:255',
        'files.*' => 'mimes:doc,pdf|max:15360',
        'program_id' => 'nullable|exists:cds_study_programs,id'
    ];

    public $attributeNames = [
        'object_id' => 'отсутвует организация или программа обучения',
        'resume_id' => 'резюме',
        'birth_date' => 'дата рождения',
        'phone' => 'номер телефона',
        'email' => 'электронная почта',
        'fio' => 'ФИО',
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
    protected $appends = ['eduLevelName', 'birthDateView'];

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
    public $hasOne = [

    ];
    public $hasMany = [];
    public $belongsTo = [
        'resume' => [
            Resume::class
        ],
        'program' => [
            Program::class
        ]
    ];
    public $belongsToMany = [];
    public $morphTo = [
        'object' => []
    ];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [
        'files' => [
            CdsFile::class
        ]
    ];

    public function beforeValidate()
    {
        if (empty($this->status)) $this->status = false;

        if (strlen($this->fio) == 0) {
            throw new \ValidationException(['fullname' => 'Заполните поле ФИО']);
        }

        if (strlen($this->phone) == 0 && strlen($this->email) == 0 ) {
            $msg = ($this->type == 1 ? 'поля номер телефона или электронная почта' : ($this->type == 2 ? 'поле номер телефона' : ' поле электронная почта'));
            throw new \ValidationException(['contacts' => "Заполните {$msg}"]);
        }

        $this->attributes['program_id'] = (!empty($this->attributes['program_id']) ? $this->attributes['program_id'] : null);
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
        } else {
            $this->attributes['birth_date'] = null;
        }
    }

    public function getBirthDateViewAttribute()
    {
        if (!empty($this->birth_date)) {
            return Carbon::parse($this->birth_date)->format('d.m.Y');
        }
    }

    public function getEduLevelNameAttribute()
    {
        if (!empty($this->edu_level) || $this->edu_level == 0 )
            return !empty(self::EDU_LEVELS[$this->edu_level]['title']) 
                ? self::EDU_LEVELS[$this->edu_level]['title'] 
                : null;
        return null;
    }

    public static function getEduLevelOptions()
    {
        return self::EDU_LEVELS;
    }

    /**
     * опправка заявки на почту организации
     */
    public function sendOrganizationEmail()
    {
        $data = null;
        $email = null;

        if ($this->object instanceof Program) {
            $data = $this->load(['object.organization', 'program', 'resume.documents', 'files']);
            if (!empty($data->object->organization)) {
                $email = !empty($data->object->organization->email) ? $data->object->organization->email : null;
            }
        }
             
        if ($this->object instanceof Organization) {
            $data = $this->load(['object', 'program', 'resume.documents', 'files']);
            $email = !empty($data->object->email) ? $data->object->email : null;
        }
             
        $data = $data->toArray();
            
        if (!empty($data['object']['organization'])) {
            $pName = $data['object']['name'];
            $oName = $data['object']['organization']['name'];
            $data['subject'] = "Заявка на обучение по программе $pName в организации $oName ";
        } else {
            $oName = $data['object']['name'];
            if (!empty($data['program'])) {
                $pName = $data['object']['name'];
                $data['subject'] = "Заявка на обучение по программе $pName в организации $oName ";
            } else {
                $data['subject'] = "Заявка на обучение в организации $oName ";
            }
        }

        if ($this->type == 1 && $email)
            Mail::send('cds.study::mail.feedback_request', $data, function($message) use($data, $email) {

                $message->from('bot@cdscompany.ru', 'Kuzon.Ru - Заявка на обучение');
                $message->to($email);

                if (!empty($data['files'])) {
                    foreach ($data['files'] as $file) {
                        $message->attach($file['path'], ['as' => $file['file_name']]);
                    }
                }

                if (!empty($data['resume'] && !empty($data['resume']['documents']))) {
                    foreach ($data['resume']['documents'] as $file) {
                        $message->attach($file['path'], ['as' => $file['file_name']]);
                    }
                }
            
            });

        if ($this->type == 2 && $email)
            Mail::send('cds.study::mail.feedback_callme', $data, function($message) use($data, $email) {

                $message->from('bot@cdscompany.ru', 'Kuzon.Ru - Перезвоните мне');
                $message->to($email);
            
            });
        
        if ($this->type == 3 && $email)
            Mail::send('cds.study::mail.feedback_sendme', $data, function($message) use($data, $email) {

                $message->from('bot@cdscompany.ru', 'Kuzon.Ru - Напишите мне');
                $message->to($email);
            
            });
    }
}
