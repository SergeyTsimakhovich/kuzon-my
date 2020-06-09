<?php namespace Cds\Study\Components;

use Cds\Study\Components\ComponentBase;
use Cds\Study\Models\Organization;
use Cds\Study\Models\Sector;
use Cds\Study\Models\Setting;
use Cds\Study\Models\Banner;
use Cds\Study\Models\BannerGroup;
use Session;

class Banners extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Рекламные банеры и полезные ссылки с логототипами',
            'description' => ''
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Рендер рекламных баннеров на страницу
     */
    public function onRenderFooterWide()
    {
        $banners =  $this->getQueryBuilder(Setting::get("max_{$this->variant}",4))->first();
        if (empty($banners)) return;
        return $banners;
    }

    /**
     * Рендер карточек с нашими партнёрами на страницу
     */
    public function onRenderFooterSlim()
    {
        $banners =  $this->getQueryBuilder(Setting::get("max_{$this->variant}",7))->first();
        if (empty($banners)) return;
        return $banners;
    }

    /**
     * Подготовка запроса для баннеров и наших партнёров
     */
    public function getQueryBuilder($limit)
    {
        return BannerGroup::where('title', $this->variant)
            ->with(['banners' => function($q) use($limit) {
                return $q->published()->dateActive()->inRandomOrder()->take($limit)->with('image');
            }]);
    }
    
}
