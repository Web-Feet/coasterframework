<h1>Coaster Upgrade</h1>

@if (!empty($error))

    <p class="text-danger">{!! $error !!}</p>

@else

    <p class="text-success">{!! $message !!}</p>

@endif

@if ($run)
    <a class="btn btn-primary" href="{{ URL::to(config('coaster::admin.url').'/system/upgrade/1') }}">Start Upgrade</a>
@endif