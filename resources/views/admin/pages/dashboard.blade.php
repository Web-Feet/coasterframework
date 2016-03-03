<h1>Dashboard</h1>
<br/>

{!! $welcome_message !!}

@if ($any_requests)
    <h2>Publish Requests To Moderate</h2>
    {!! $requests !!}
    <p><a href="{{ URL::to(config('coaster::admin.url').'/home/requests') }}">View all requests</a></p>
    <br/>
@endif

@if ($any_user_requests)
    <h2>Your Publish Requests</h2>
    {!! $user_requests !!}
    <p><a href="{{ URL::to(config('coaster::admin.url').'/home/your-requests') }}">View all your requests</a></p>
    <br/>
@endif

<h2>Site Updates:</h2>
{!! $logs !!}
<p><a href="{{ URL::to(config('coaster::admin.url').'/home/logs') }}">View all admin logs</a></p>