<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<head>

  <meta charset="utf-8" />
  <!-- Set the viewport width to device width for mobile -->
  <meta name="viewport" content="width=device-width" />

  <title>@yield('title', 'Новости науки')</title>

  <!-- Included CSS Files (Compressed) -->
  <link rel="stylesheet" href="{{ asset('css/foundation.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/main.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">

  <script src="{{ asset('js/modernizr.foundation.js') }}"></script>

  <link rel="stylesheet" href="{{ asset('fonts/ligature.css') }}">

  <!-- Google fonts -->
  <link href='http://fonts.googleapis.com/css?family=Open+Sans+Condensed:300|Playfair+Display:400italic' rel='stylesheet' type='text/css' />

  <!-- IE Fix for HTML5 Tags -->
  <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

</head>

<body>

<!-- ######################## Main Menu ######################## -->

<nav>
     <div class="twelve columns header_nav">
     <div class="row">
        <ul id="menu-header" class="nav-bar horizontal">
          <li><a href="{{ route('news.index') }}">Главная</a></li>
          <li><a href="{{ route('news.rubrika', 'ai') }}">Искусственный интеллект</a></li>
          <li><a href="{{ route('news.rubrika', 'neural') }}">Искусственная нейронная сеть</a></li>
          <li><a href="{{ route('news.rubrika', 'patterns') }}">Распознавание образов</a></li>
          <li><a href="{{ route('news.rubrika', 'robotics') }}">Робототехника</a></li>
          <li><a href="{{ route('news.rubrika', 'info-society') }}">Информационное общество</a></li>
          <li><a href="{{ route('news.rubrika', 'text-processing') }}">Автоматическая обработка текста</a></li>
        </ul>
      </div>
      </div>
</nav><!-- END main menu -->

@yield('header')

<!-- ######################## Section ######################## -->
<section>
  @yield('content')
</section>

@yield('extra-section')

<!-- ######################## Footer ######################## -->

<footer>
      <div class="row">
          <div class="twelve columns footer">
              <a href="" class="lsf-icon" style="font-size:16px; margin-right:15px" title="twitter">Twitter</a>
              <a href="" class="lsf-icon" style="font-size:16px; margin-right:15px" title="facebook">Facebook</a>
              <a href="" class="lsf-icon" style="font-size:16px; margin-right:15px" title="pinterest">Pinterest</a>
              <a href="" class="lsf-icon" style="font-size:16px" title="instagram">Instagram</a>
          </div>
      </div>
</footer>

<!-- ######################## Scripts ######################## -->
    <!-- Included JS Files (Compressed) -->
    <script src="{{ asset('js/foundation.min.js') }}" type="text/javascript"></script>
    <!-- Initialize JS Plugins -->
    <script src="{{ asset('js/app.js') }}" type="text/javascript"></script>
    @stack('scripts')
</body>
</html>
