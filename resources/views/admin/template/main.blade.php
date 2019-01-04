<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8"/>
    <title>{!! $site_name." | ".$title !!}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Coaster CMS {{ config('coaster::site.version') }}">
    <meta name="robots" content="noindex">
    <meta name="_token" content="{{ csrf_token() }}">

    <link href='//fonts.googleapis.com/css?family=Raleway:400,100,300,500,600,700,800,900' rel='stylesheet' type='text/css'>
    {!! AssetBuilder::styles() !!}
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    @yield('styles')

</head>

<body>

<nav class="navbar navbar-default navbar-fixhed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                    aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="logo" href="{{ route('coaster.admin') }}">
                <img src="{{ URL::to(config('coaster::admin.public')) }}/app/img/logo.png" alt="Coaster CMS"/>
            </a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav navbar-right">
                @if (isset($system_menu))
                    {!! $system_menu !!}
                @endif
            </ul>
        </div><!--/.nav-collapse -->
    </div><!--/.container-fluid -->
</nav>

@if (!empty($sections_menu))
    <nav class="navbar navbar-inverse subnav navbar-fixedg-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar2"
                        aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div id="navbar2" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    {!! $sections_menu !!}
                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </nav>
@endif

<div class="container{{ empty($sections_menu)?' loginpanel':'' }}" id="content-wrap">
    <div class="row">
        <div class="{{ empty($sections_menu)?'col-sm-6 col-sm-offset-3':'col-sm-12' }}">
            <div id="cmsNotifications">
                <div class="alert" id="cmsDefaultNotification" style="display: none;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            </div>
            {!! $content !!}
            <br /><br />
        </div>
    </div>
</div>

{!! $modals !!}

<script src="{{ URL::to(config('coaster::admin.public')).'/app/js/router.js' }}"></script>
<script type="text/javascript">
    var dateFormat = '{{ config('coaster::date.format.jq_date') }}';
    var timeFormat = '{{ config('coaster::date.format.jq_time') }}';
    var ytBrowserKey = '{{ config('coaster::key.yt_browser') }}';
    var adminPublicUrl = '{{ URL::to(config('coaster::admin.public')).'/' }}';
    router.addRoutes({!! $coaster_routes !!});
    router.setBase('{{ URL::to('/') }}');
</script>

{!! AssetBuilder::scripts() !!}
@yield('scripts')
@if (!empty($alerts))
    <script type="text/javascript">
        $(document).ready(function () {
            @foreach($alerts as $alert)
                cms_alert('{!! $alert->class !!}', '{!! $alert->content !!}');
            @endforeach
        });
    </script>
@endif

</body>

</html>
