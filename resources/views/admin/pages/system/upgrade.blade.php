<h1>Coaster Upgrade</h1>

@if (!empty($error))

    <p class="text-danger">{!! $error !!}</p>

@else

    <p class="text-success">{!! $message !!}</p>

@endif

@if ($run)
    <a class="btn btn-primary" href="{{ route('coaster.admin.system.upgrade', ['update' => 1]) }}">Start Upgrade</a>
@endif