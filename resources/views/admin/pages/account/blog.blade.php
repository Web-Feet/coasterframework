<h1>Change blog auto login details</h1>

<br/>

@if (isset($success))

<p class="text-success">Your blog auto login details have been successfully updated!</p>
<p><a href="{{ route('coaster.admin.account') }}">&raquo; Return to Account Settings</a></p>

@else

{!! $form !!}

@endif