<?php namespace Cds\Study\Components;

use Cds\Study\Components\ComponentBase;
use Cds\Study\Models\Menu;
use Cds\Study\Models\MenuType;

use Cds\Study\Models\Program;
use Cds\Study\Models\Sector;
use Cms\Classes\Page;
use Cms\Classes\Theme;

use Illuminate\Support\Facades\Request;

class Menus extends ComponentBase
{
    use \Cds\Study\Traits\ComponentVariants;
    use \Cds\Study\Traits\ComponentModals;

    public $menus = null;
    public $breadcrumbs = null;

    public function componentDetails()
    {
        return [
            'name'        => 'Меню личного кабинета пользователя',
            'description' => 'Рендер личного меню на всех страницах личного кабинета'
        ];
    }

    /**
     * Рендер хлебных крошек на страницу 
     * */
    public function onRenderBreadcrumbs()
    {
        return $this->getBreadcrumbs();
    }

    /**
     * Рендер хленых крошек для детальной страницы категории
     */
    public function onRenderSectorBreadcrumbs()
    {
        $categories = Sector::getCategoryBySlugAndId($this->property('slug'));
        
        if (!empty($categories)) {
            $categories = $categories->getParentsInlineTree();
            $categoriesList = array_reverse($categories->lists('sector_id'));
            $categories = Sector::whereIn('id', $categoriesList)->get();
        }
            
        if (!empty($categories)) return $categories;
        return;
    }

    /**
     * Получаем массив с хлебными крошками в зависимости от текущей страницы
     */
    public function getBreadcrumbs()
    {
        $theme = Theme::getEditTheme();
        $pages = Page::listInTheme($theme, true)->toArray();

        $url = "/" . Request::path();
        $clearUrl = $this->getClearUrl($this->page->url);
        $templateUrl = $this->getClearUrl($this->page->url);

        $data = [];
        $prevTitle = '';

        $count = count(explode("/", $templateUrl)) == 1 ? 1 : count(explode("/", $templateUrl)) - 1;
        //проходим по массиву всех страниц и ищем родительские страницы.
        for ($i = 0; $i < $count; $i++) {
            if (!empty($data)) $prevTitle = $data[$i-1]['title'];
            $data[] = $this->getAllParent($templateUrl, $pages, $prevTitle);
            $templateUrl = substr($templateUrl, 0, strrpos($templateUrl, "/"));
        }

        //добавляем в крошки главную страницу и пустой пункт, куда будет вставляться title текущий страницы.
        $data = array_reverse($data);
        $data = array_unique($data, SORT_REGULAR);
        array_unshift($data, ['url' => '/', 'title' => 'Главная страница']);

        if ($url != $clearUrl) {
            $data[] = [
                'url' => null,
                'title' => null,
            ];
        }

        return $data;
    }

    private function getClearUrl($url)
    {
        if (strpos($url, ':') !== false) {
            $index = strpos($url, ':');
            return substr($url, 0, $index - 1);
        } else {
            return $url;
        }
    }

    public function getAllParent($url, $pages, $prevTitle)
    {
        foreach ($pages as $page) {
            $currentPageTitle = !empty($page['settings']['title']) ? $page['settings']['title'] : '';
            if ($page['settings']['url'] == $url && $currentPageTitle != $prevTitle) {
                return [
                    'url' => $this->getClearUrl($page['settings']['url']),
                    'title' => $page['settings']['title']
                ];
            }
        }

    }
}

