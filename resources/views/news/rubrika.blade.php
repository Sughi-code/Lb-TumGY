@extends('news.layout')

@section('title', 'Рубрика - Новости науки')

@section('header')
<header>
    <div class="row">
        <h1>Новости науки</h1>
    </div>
</header>
@endsection

@section('content')
<div class="section_main">
    <div class="row">
        <section class="eight columns">
          <h3>Искусственный интеллект</h3>

          <article class="blog_post">
             <div class="three columns">
             <a href="#" class="th"><img src="{{ asset('images/a1.jpg') }}" alt="desc" /></a>
             </div>
             <div class="nine columns">
              <a href="#"><h4>Название 1</h4></a>
              <p>Первое предложение новости 1.</p>
              <div><a href="">Удалить</a></div>
             </div>
          </article>

          <article class="blog_post">
             <div class="three columns">
             <a href="#" class="th"><img src="{{ asset('images/thumb2.jpg') }}" alt="desc" /></a>
             </div>
             <div class="nine columns">
              <a href="#"><h4>Название 2</h4></a>
              <p>Первое предложение новости 2.</p>
              <div><a href="">Удалить</a></div>
             </div>
          </article>
        </section>

        <section class="four columns">
            <h3>&nbsp;</h3>
            <div class="panel">
              <h3>Админ-панель</h3>
              <ul class="accordion">
                <li class="active">
                  <div class="title">
                     <a href="#"><h5>Добавить статью</h5></a>
                  </div>
                </li>
              </ul>
            </div>
        </section>
    </div>
</div>
@endsection

@section('extra-section')
<section>
   <div class="section_dark">
      <div class="row">
         <h2></h2>
         <div class="two columns">
            <img src="{{ asset('images/thumb1.jpg') }}" alt="desc" />
         </div>
         <div class="two columns">
            <img src="{{ asset('images/thumb2.jpg') }}" alt="desc" />
         </div>
         <div class="two columns">
            <img src="{{ asset('images/thumb3.jpg') }}" alt="desc" />
         </div>
         <div class="two columns">
            <img src="{{ asset('images/thumb4.jpg') }}" alt="desc" />
         </div>
         <div class="two columns">
            <img src="{{ asset('images/thumb5.jpg') }}" alt="desc" />
         </div>
         <div class="two columns">
            <img src="{{ asset('images/thumb6.jpg') }}" alt="desc" />
         </div>
      </div>
   </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
 //<![CDATA[
 $('ul#menu-header').nav-bar();
 //]]>
</script>
@endpush
