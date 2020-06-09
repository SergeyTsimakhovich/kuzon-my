<?php namespace Cds\Study\Components;

use Cds\Study\Models\FeedbackFormCapture;
use Cds\Study\Models\FormCapture;
use Cds\Study\Models\FormCaptureUserAnswer;

use Cds\Study\Models\Organization;
use Cds\Study\Models\Program;
use Cds\Study\Models\Sector;

use Session;
use Flash;

class FormCaptures extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Компонент Форма захвата',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRenderModalFeedbackFormCapture($params)
    {
        return $params;
    }

    /**
     * Ищем ID категории в зависимости от страницы и рендерим форму захвата
     */
    public function onRenderDefault()
    {
        $sectorIds = null;
        $objectName = '';

        if ($this->property('object') == 'Organization') {
            $org = Organization::with('sectors')->find($this->property('id'));
            $sectorIds = $org->sectors->lists('id');
            if (empty($sectorIds)) return false;
            $objectName = $org->name;
        }

        if ($this->property('object') == 'Program') {
            
            $prog = Program::with('sector')->find($this->property('id'));
            if (empty($prog->sector)) return false;
            $sectorIds = ['id' => $prog->sector->id];
            $objectName = $prog->name;
        }

        if ($this->property('object') == 'Search') {
            $sectors = Sector::whereIn('id', post('c', []));
            $sectorIds = $sectors->lists('id');
            if (empty($sectorIds)) return false;
            $objectName = 'Результаты поиска';
        }

        if ($this->property('object') == 'Category') {
            $categories = Sector::getCategoryBySlugAndId($this->property('slug'));
            if (empty($categories)) return false;
            $sectorIds = ['id' => $categories->id];
            $objectName = $categories->name;
        }

        $formCapture = $this->getQueryFormCapture($sectorIds)->first();

        if (empty($formCapture)) return false;

        $data = [
            'url' => $this->getCurrentUrl(),
            'form_capture_id' => $formCapture->id,
        ];

        return compact('formCapture', 'objectName');
    }

    /**
     * отправка формы захвата
     */
    public function onSendFormCapture()
    {
        $data = [
                'link' => $this->getCurrentUrl(),
                'realm_id' => Session::get('realm.id')
            ] + post();

        $data['status'] = false;

        $feedback = FeedbackFormCapture::create($data);
        $answer = $this->makeAnswer();

        Flash::success('В ближайшее время с Вами свяжется наш специалист');
        return ['#form_capture_result' => ''];
    }

    /**
     * Подготовка запроса для поиска формы захвата по категории и городу
     */
    private function getQueryFormCapture($sectorId = null)
    {
        $url = $this->getCurrentUrl();
        return FormCapture::byRealmAndSector($sectorId);
    }

    /**
     * Получить текущий URL страницы
     */
    private function getCurrentUrl()
    {
        return $this->controller->getRouter()->getUrl();
    }

    private function makeAnswer()
    {
        $data = [
                'link' => $this->getCurrentUrl(),
                'token' => Session::get('_token'),
            ] + post();

        $answer = FormCaptureUserAnswer::create($data);

        return $answer;
    }
}
