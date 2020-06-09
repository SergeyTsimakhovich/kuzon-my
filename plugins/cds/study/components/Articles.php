<?php namespace Cds\Study\Components;

use Cds\Study\Components\ComponentBase;
use Cds\Study\Models\Article;
use Cds\Study\Models\ArticleReview;
use App;
use Auth;
use Flash;
use Session;
use Response;
use View;

class Articles extends ComponentBase
{
    public $article_id = null;

    private $sortList = [
        'popular' => 'views_count',
        'date' => 'published_at',
        'comments' => 'comments_new_count',
    ];

    public function componentDetails()
    {
        return [
            'name'        => 'Компонент Статьи',
            'description' => 'Вывод статей'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        // ищем статью по слагу, иначе выкидываем 404
        $mode = $this->property('mode');
        if ($mode == 'article_detail' && $slug = $this->controller->param('slug')) {
            $data = Article::where('slug', 'ILIKE', '%'.$slug.'%')->isPublished()->first();
            if (empty($data)) return Response::make($this->controller->run('404')->getContent(), 404);
        }
    }

    public function onRenderModalHelpfulAsk($params)
    {
        return $params;
    }

    /**
     * Рендер списка новостей на страницу
     */
    public function onRenderList()
    {
        $sortParams = get('sort');
        if (!empty($sortParams)) {
            $sortParams = explode('_', $sortParams);
        }

        //получаем название и тип сортировки для запроса
        $sort = [
            'sort' => !empty($sortParams[0]) ? $sortParams[0] : 'date',
            'sort_order' => !empty($sortParams[1]) ? $sortParams[1] : 'desc',
        ];

        //взависимости от переданный свойств выводим нужное количество новостей на страницу
        if ($num = $this->property('num')) {
            $articles = $this->getArticles($sort)->byUserId($user_id)->take($num)->get();
        } else {
            $articles = $this->getArticles($sort)->byUserId($user_id)->paginate($this->property('count'));
            if ($user_id && $this->property('area') != 'lk') {
                $this->page->title = 'Автор публикаций ' . $articles->first()->user->fullName;
            }
        }

        return ['articles' => $articles, 'sort' => $sort];
    }

    /**
     * Рендер детальной страницы новости
     */
    public function onRenderView()
    {
        $article_id = $this->property('action_id');
        $area = $this->property('area', 'default');

        $article = $this->getArticles()->where('slug', 'ILIKE', '%'.$article_id.'%')->with('image')->first();

        if (empty($article)) {
            return null;
        }

        $this->article_id = $article->id;

        //добавляем в статистику просмотры
        $article->addView();

        $this->page->title = $this->property('title', $article->title);

        return $article;
    }

    /**
     * Получаем статьи с нужной сортировкой
     */
    public function getArticles($sort = [])
    {
        $article = Article::isPublished()
            ->with('image')
            ->with('reviews')
            ->withCount('views')
            ->withCount('comments')
            ->withCount('comments_new');

        if (empty($sort)) {
            return $article;
        }

        $sort['sort'] = $this->sortList[$sort['sort']];

        return $article->orderByRaw("{$sort['sort']} {$sort['sort_order']}");
    }

    /**
     * Обработчик для кнопки оценки статьи (лайк, дизлайк)
     */
    public function onHelpfulAsk()
    {
        $data = post();
        $status = false;

        if (!Auth::check()) {
            $data += ['session_token' => Session::get('_token')];
        } else {
            $data += ['user_id' => Auth::id()];
        }

        if (post('status') == '1') {
            $status = true;
        }

        if (!empty($data['body'])) {
            $data['text'] = $data['body'];
        }

        $data += ['status' => $status];

        $review = ArticleReview::create($data);

        Flash::success('Спасибо за отзыв!');
        return ['#button_review_article' => $this->renderPartial('@my_review', ['review' => $review])];
    }
}
