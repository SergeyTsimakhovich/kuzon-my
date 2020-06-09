<?php namespace Cds\Study\Components;

use Cds\Study\Models\FeedbackCallme;
use Cds\Study\Models\FeedbackProgramRequest;
use Cds\Study\Models\Program;
use Cds\Study\Models\Resume;
use Cds\Study\Models\CdsFile;
use Flash;
use Auth;
use Cds\Study\Models\Organization;
use Input;
use Validator;
use Session;
use Mail;

class Feedbacks extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Компонент обратной связи',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Метод для формы Перезвоните мне
     * @return bool
     */
    public function onCallMe()
    {
        $data = ['phone' => post('user[number]'), 'realm_id' => Session::get('realm.id')];
        $feedback = FeedbackCallme::create($data);

        Flash::success('С Вами свяжется наш специалист в течение часа.');
        return true;
    }

    /**
     * Рендер модального окна для отправки заявки на детальной организации
     */
    public function onRenderModalWeconnectRequest($param)
    {
        $organization = Organization::where('id', $param['organization_id'])->with('programs')->first();
        $eduLevel = FeedbackProgramRequest::getEduLevelOptions();

        $resume = '';

        if (Auth::check()) {
            $resume = Resume::where('user_id', Auth::id())->with(['user', 'documents'])->first();
        }

        return compact('organization', 'resume', 'eduLevel');
    }

    /**
     * Отправка заявки
     */
    public function onSendOrganizationRequest()
    {
        if (!post('personal', false)) {
            throw new \ValidationException(['personal' => 'Подтвердите согласие на обработку персональных данных']);
        }

        $data = post();

        //делаем валидацию для файлов загруженных через дрозону
        $validFiles = CdsFile::validateFile(post('files'), ['pdf', 'doc', 'jpeg', 'png', 'jpg']);

        $data['object_type'] = Organization::class;
        $data['object_id'] = post('organization_id');

        $feedback = FeedbackProgramRequest::create($data);

        if (!empty($validFiles))
        {
            $feedback->files()->addMany($validFiles);
            $feedback->save();
        }

        $feedback->sendOrganizationEmail();

        Flash::success('С Вами свяжется наш специалист в течение часа.');
        return;
    }


}
