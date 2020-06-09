<?php namespace Cds\Study\Components;

use Carbon\Carbon;
use Cds\Study\Components\ComponentBase;
use Cds\Study\Models\Notification;
use Auth;

class Notifications extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Компонент уведомлений',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * В зависимости от параметра рендерим новые уведомления или историю
     */
    public function onRenderDefault()
    {
        if (get('history',0 ) == 1) {;
            $data =  $this->getHistory();
        } else {
            if (get('page', 0) ==0) {
                $this->isReadNotification();
            }
            $data = $this->getNew();
        }

        $notifyCount = $this->getNotifyCount();
        return ['data' => $data, 'notifyCount' => $notifyCount];
    }

    public function getQueryBuilder()
    {
        return Notification::my()->orderBy('created_at', 'desc');
    }

    public function getNotifyCount()
    {
        return $this->getQueryBuilder()->new()->isRead()->get()->count();
    }

    public function getHistory()
    {
        if (empty($this->page->history)) {
            $page = 1;
        } else {
            $page = get('page', 1);

        }
        $this->isReadNotification();
        return $this->getQueryBuilder()->readAt()->paginate(10, $page);
    }

    public function getNew()
    {
        if (empty($this->page->history)) {
            $page = get('page', 1);
        } else {
            $page = 1;
        }

        $notify =  $this->getQueryBuilder()->new()->paginate(10);
        $notifIds = array_values($notify->lists('id'));
        \DB::table('cds_study_notifications')->whereIn('id', $notifIds)->where('is_read', null)->update(['is_read' =>  1]);

        return $notify;
    }

    private function isReadNotification()
    {
        \DB::table('cds_study_notifications')->where('user_id', Auth::id())->where('is_read', 1)->update(['read_at' => Carbon::now(), 'is_read' => 2]);
        return;
    }

}
