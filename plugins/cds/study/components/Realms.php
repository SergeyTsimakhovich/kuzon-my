<?php namespace Cds\Study\Components;

use Cds\Study\Models\Realm;
use Cds\Study\Models\CdsFile;
use morphos\Russian\GeographicalNamesInflection;
use Redirect;
use Session;
use Http;

class Realms extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Компонент регионов и городов',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /** при загрузке компонента проверяем хост и ставим пользователю необходимый город */
    function onRun() {
        $realm = $_SERVER['HTTP_HOST'];
        $realm = str_replace('www.', '', $realm);
        $realm = str_replace('.'.$_SERVER['SERVER_NAME'], '', $realm);
        $realm = str_replace($_SERVER['SERVER_NAME'], '', $realm);
        if ($realm) {
            $city = Realm::byDomain($realm)->with('city')->first();

            if ($city) {
                $this->setCity($city->toArray());
                Session::put('selected_city', true);
            }
        }
    }

    /**
     * Рендер выбранного города или региона на страницу
     */
    public function onRenderCity()
    {
        if (empty(Session::get('realm'))) {
            $city = $this->setCity();
        }

        $userCity = [
            'selected_city' => Session::get('selected_city') ? 'true' : 'false',
        ] + Session::get('realm');

        $cities = $this->getAllCities()->toArray();
        return ['cities' => $cities, 'user_city' => $userCity];
    }

    /**
     * Рендер области в котором находится находится выбранные город
     */
    public function onRenderArea()
    {
        $realm = Session::get('realm');
        if ($realm['isArea']) return null;

        $area = Realm::find($realm['parent_id']);
        //Меняем название области в дательный падеж (Поиск по Тюменской области)
        $area->name = GeographicalNamesInflection::getCase($area->name, 'дательный');

        return $area;
    }

    /**
     * Рендер модального окна со списком городов для выбора
     */
    public function onRenderModalSearchCity($params)
    {
        $geoName = !empty($params['name']) ? $params['name'] : null;
        $yourCity = $this->onGetCityByName($geoName);

        $cities = $this->getAllCities($yourCity);

        return compact('cities', 'yourCity');
    }

    /**
     * Выбор города
     */
    public function onSetCity()
    {
        $city = $this->setCity();

        Session::put('selected_city', true);

        $pageUrl = '//'.$city['host'].\Request::server('REQUEST_URI');

        return Redirect::to($pageUrl);
    }

    /**
     * Устанавливаем выбранный город в сессию пользователю
     */
    private function setCity($city = null)
    {
        if (empty($city)) {
            $city = Realm::with('city')->find(post('id', 5395))->append('host')->toArray();
            if ($name = post('name')) {
                $city = Realm::where('name', $name)->first()->toArray();
            }
        }
        if (empty($city)) {
            $city = Realm::with('city')->find(5395)->append('host')->toArray();
        }

        $city['city'] = array_pluck($city['city'], 'id');

        Session::put('realm', $city);
        return $city;
    }

    /**
     * Возвращаем коллекцию всех крупных городов, если ничего не ввели в поиск
     */
    private function getAllCities($yourCity = null)
    {
        return Realm::where('sort', '>=', 1)->where('id', '<>', !empty($yourCity) ? $yourCity->id : null)
            ->orderBy('sort', 'des')->orderBy('name', 'asc')->with('city')->get(['id', 'name', 'sort', 'domain']);
    }

    /**
     * Поиск городов и областей по названию и вывод их в модального окно
     */
    public function onGetCity()
    {
        $postName = post('name');

        $yourCity = $this->onGetCityByName(post('geoCity'));

        if ($postName == '') return ["#cities-list" => $this->renderPartial('@cities_list', [
            'cities' => $this->getAllCities($yourCity),
            'yourCity' => $yourCity
        ])];

        $city = Realm::where('name', 'ILIKE', '%'. $postName . '%')
            ->orderByRaw('case when parent_id is null then 0
                               when name ILIKE ' . "'" . $postName . "%'" . ' then 1 else 2
                            end asc, sort desc')
            ->take(40)->get(['id', 'name', 'sort']);

        return ["#cities-list" => $this->renderPartial('@cities_list', ['cities' => $city, 'search' => true])];
    }

    /**
     * Вывод в шапке сайта названия города и области в нужном падеже
     */
    public function getNameMorph()
    {
        $realm = Session::get('realm');
        $cityName = '';
        $padej = 'locative';

        if (!empty($realm)) {
            $cityName = $realm['name'];
        } else {
            $cityName =  Realm::where('id', 5395)->first()->name;
        }

        if (mb_substr($cityName, -1, 1) === 'р') $padej = 'предложный';

        return GeographicalNamesInflection::getCase($cityName, $padej);
    }

    public function onGetCityByName($name = null)
    {
        if (empty($name)) return false;
        return Realm::where('parent_id', '<>', null)->where('name', $name)->first();
    }
}
