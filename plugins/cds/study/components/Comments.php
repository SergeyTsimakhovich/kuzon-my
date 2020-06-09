<?php namespace Cds\Study\Components;

use Cds\Study\Components\ComponentBase;
use Cds\Study\Models\Organization;
use Cds\Study\Models\Article;
use Cds\Study\Models\Comment;
use Cds\Study\Models\Program;
use Cds\Study\Models\About;
use Auth;
use Cds\Study\Models\UserAction;
use Flash;
use Session;

class Comments extends ComponentBase
{
    public $commentedClass = [
        'Article' => Article::class, 
        'Program' => Program::class, 
        'Organization' => Organization::class,
        'About' => About::class,
    ];

    public function componentDetails()
    {
        return [
            'name'        => 'Comments Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /**
     * Рендер комментариев на страницу
     */
    public function onRenderDefault()
    {
        $comments = Comment::byObject($this->properties)->with(['rates', 'my_rate', 'children'])->orderBy('id', 'desc')->get();

        return [
            'comments' => $comments,
            'prop' => $this->properties,
            'guest_name' => Session::get('guest_name'),
        ];
    }

    /**
     * Рендер отзывов для детальной страницы организации
     */
    public function onRenderOrgReviews()
    {
        $comments = Comment::byObject($this->properties)->with(['rates', 'my_rate', 'children'])->orderBy('id', 'desc')->take(4)->get();

        return [
            'comments' => $comments,
            'prop' => $this->properties,
            'guest_name' => Session::get('guest_name'),
        ];
    }

    /**
     * Добавление нового комментария
     */
    public function onAddComment()
    {
        $data = post();

        if (post('guest_name')) {
            Session::put('guest_name', post('guest_name'));
            $data['guest_session_token'] = Session::get('_token');
        }

        $class = $this->commentedClass[post('object_type')];
        $object = $class::find(post('object_id'));

        if (!$object) {
            Flash::error('Объект комментирования не найден');
            return;
        }

        if ($object instanceof About) {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }

        $comment = $object->comments()->create($data);

        //отрисовка добавленного комментария на странице
        if ($data['status'] === 1) {
            if (empty($data['parent_id'])) {
                return ['~#new-comment' => $this->renderPartial('@_card', ['item' => $comment, 'index' => 0, 'level' => 0])];
            } else {
                $id = $data['parent_id'];
                return ["~#new-comment-answer-{$id}" => $this->renderPartial('@_card', ['item' => $comment, 'index' => 0, 'level' => 1])];
            }
        }
        Flash::success('Спасибо за Ваш комментарий, он появится сразу же, как только пройдёт модерацию.');
        return;
    }

    /**
     * Оценка комментария (лайк, дизлайк)
     */
    public function onMakeRate()
    {
        $data = [];
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['session_token'] = Session::get('_token');
        }

        $rate = UserAction::updateOrCreate([
            'object_type' => Comment::class,
            'object_id' => post('comment_id')
        ] + $data, ['value' => post('status'), 'action' => 'rate']);

        return ['#my_rate-' . post('comment_id') => $this->renderPartial('@my_rate', ['value' => post('status'), 'id' => post('comment_id')])];

    }

    public function onGetButtonAnswer()
    {
        $data = post();
        return ["#comment-enter-{$data['parent_id']}" => $this->renderPartial('@comment-enter', [
            'object_type' => $data['object_type'],
            'object_id'   => $data['object_id'],
            'parent_id'   => $data['parent_id'],
            'guest_name'  => Session::get('guest_name'),
        ])];
    }
}
