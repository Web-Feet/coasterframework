{!! Form::open() !!}

@if ($current_password)
        <!-- current password field -->
<div class="form-group {!! FormMessage::get_class('current_password') !!}">
    {!! Form::label('current_password', 'Current Password', ['class' => 'control-label']) !!}
    {!! Form::password('current_password', ['class' => 'form-control']) !!}
    <span class="help-block">{!! FormMessage::get_message('current_password') !!}</span>
</div>
@endif

        <!-- password field -->
<div class="form-group {!! FormMessage::get_class('new_password') !!}">
    {!! Form::label('new_password', 'New Password', ['class' => 'control-label']) !!}
    {!! Form::password('new_password', ['class' => 'form-control']) !!}
    <span class="help-block">{!! FormMessage::get_message('new_password') !!}</span>
</div>

<!-- confirm password field -->
<div class="form-group ">
    {!! Form::label('new_password_confirmation', 'Confirm Password', ['class' => 'control-label']) !!}
    {!! Form::password('new_password_confirmation', ['class' => 'form-control']) !!}
</div>

<!-- submit button -->
{!! Form::submit('Update Password', ['class' => 'btn btn-primary']) !!}

{!! Form::close() !!}