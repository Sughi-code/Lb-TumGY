<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Главная страница новостной ленты
     */
    public function index()
    {
        return view('news.index');
    }

    /**
     * Страница рубрики
     * @param string $slug - идентификатор рубрики
     */
    public function rubrika($slug)
    {
        // В будущем здесь будет логика получения новостей по рубрике из БД
        return view('news.rubrika', ['slug' => $slug]);
    }

    /**
     * Страница статьи
     * @param int $id - идентификатор статьи
     */
    public function article($id)
    {
        // В будущем здесь будет логика получения статьи из БД
        return view('news.statya', ['id' => $id]);
    }
}
