<h1>Change blog auto login details</h1>

<br/>

@if (isset($success))

<p class="text-success">Your blog auto login details have been successfully updated!</p>
<p>{!! HTML::link(URL::to(config('coaster::admin.url').'/account'), '&raquo; Return to Account Settings') !!}</p>

@else

{!! $form !!}

@endif