<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8"/>
    <title>{!! $site_name." | ".$title !!}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Coaster CMS {{ config('coaster::site.version') }}">
    <meta name="_token" content="{{ csrf_token() }}">

    <link href='https://fonts.googleapis.com/css?family=Raleway:400,100,300,500,600,700,800,900' rel='stylesheet' type='text/css'>
    {!! AssetBuilder::styles() !!}
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
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
            <a class="logo" href="#"><img src="{{ URL::to(config('coaster::admin.public')) }}/app/img/logo.png" alt="Coaster CMS"/></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <?php $system_menu_icons += ['Logout' => 'fa fa-sign-out', 'Login' => 'fa fa-lock', 'Help' => 'fa fa-life-ring', 'My Account' => 'fa fa-lock', 'System Settings' => 'fa fa-cog', 'Open Frontend' => 'fa fa-tv'] ?>
            @if (!empty($system_menu))
                <ul class="nav navbar-nav navbar-right">
                    @foreach($system_menu as $system_item_name => $system_item_link)
                        <li><a href="{!! $system_item_link !!}"><i
                                        class="{{ $system_menu_icons[$system_item_name] }}"></i> {{ $system_item_name }}
                            </a></li>
                    @endforeach
                </ul>
            @endif

        </div><!--/.nav-collapse -->
    </div><!--/.container-fluid -->
</nav>

@if (isset($menu))
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
                    {!! $menu !!}
                </ul>
            </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
    </nav>
@endif

<div class="container{{ !isset($menu)?' loginpanel':'' }}" id="content-wrap">
    <div class="row">
        <div class="{{ isset($menu)?'col-sm-12':'col-sm-4 col-sm-offset-4' }}">
            <div class="alert alert-success" id="cms_notification" style="display: none;">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <h4 class="note_header"></h4>
                <p class="note_content"></p>
            </div>
            {!! $content !!}
            <br/><br/>
        </div>
    </div>
</div>

{!! $modals !!}

<script type="text/javascript">
    var adminUrl = '{{ URL::to(config('coaster::admin.url')).'/' }}';
    var adminPublicUrl = '{{ URL::to(config('coaster::admin.public')).'/' }}';
    var dateFormat = '{{ config('coaster::date.format.jq_date') }}';
    var timeFormat = '{{ config('coaster::date.format.jq_time') }}';
    var ytBrowserKey = '{{ config('coaster::key.yt_browser') }}';
</script>
{!! AssetBuilder::scripts() !!}
@yield('scripts')
@if (!empty($alert))
    <script type="text/javascript">
        $(document).ready(function () {
            cms_alert('{!! $alert->type !!}', '{!! $alert->header !!}', '{!! $alert->content !!}');
        });
    </script>
@endif

</body>

</html>
