<h1>Dashboard</h1>
<br/>

{!! $welcome_message !!}
<div class="row">
  <div class="col-md-8">
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
  </div>
    <div class="col-md-4">
      <div class="well well-sm">
        <h4>Version Details</h4>
        <ul>
          <li><strong>Your site:</strong> {{ $upgrade->from }}</li>
          <li>
            <strong>Latest version:</strong> {{ $upgrade->to }}
            @if($upgrade->allowed && $upgrade->required)
              <a class="btn btn-primary" href="{{ URL::to(config('coaster::admin.url').'/system/upgrade') }}">(upgrade)</a>
            @endif
          </li>
        </ul>
        <p><a href="{{ URL::to(config('coaster::admin.url').'/system') }}">View all settings</a></p>
      </div>
      @if($any_searches)
        <div class="well">
          {!! preg_replace('/<h1.*>(.*)<\/h1>/', '<h4>$1 (top 5)</h4>', $search_logs) !!}
          <p><a href="{{ URL::to(config('coaster::admin.url').'/search') }}">View all search logs</a></p>
        </div>
      @endif
    </div>
</div>
