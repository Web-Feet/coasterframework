<h1>Install Coaster CMS</h1>

<br/>

@if ($stage == 'complete')

    <p class="text-success">Install Complete</p>
    <p><a href="{{ URL::to(config('coaster::admin.url')) }}">Go to back-end and login</a></p>
    <p><a href="{{ URL::to('/') }}">Go to front-end</a></p>

@elseif ($stage == 'theme')

    {!! Form::open(['url' => route('coaster.install.themeInstall')]) !!}

    <p>Select a theme to use</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::get_class('theme') !!}">
        {!! Form::label('theme', 'Theme', ['class' => 'control-label']) !!}
        {!! Form::select('theme', $themes, $defaultTheme, ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('theme') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::get_class('page-data') !!} page-data-group">
        {!! Form::label('page-data', 'Install with page data (recommended)', ['class' => 'control-label']) !!}
        {!! Form::checkbox('page-data', 1, true, ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('page-data') !!}</span>
    </div>

    <!-- submit button -->
    {!! Form::submit('Complete Install', ['class' => 'btn btn-primary']) !!}

    {!! Form::close() !!}

@elseif ($stage == 'adduser')

    {!! Form::open(['url' => route('coaster.install.adminSave')]) !!}

    <p>Set up the admin user</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::get_class('email') !!}">
        {!! Form::label('email', 'Admin Email', ['class' => 'control-label']) !!}
        {!! Form::text('email', Request::input('user'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('email') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::get_class('password') !!}">
        {!! Form::label('password', 'Admin Password', ['class' => 'control-label']) !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('password') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::get_class('password_confirmation') !!}">
        {!! Form::label('password_confirmation', 'Admin Password Confirmation', ['class' => 'control-label']) !!}
        {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
    </div>

    <!-- submit button -->
    {!! Form::submit('Create User', ['class' => 'btn btn-primary']) !!}

    {!! Form::close() !!}

@elseif ($stage == 'database')

    {!! Form::open(['url' => route('coaster.install.databaseSave')]) !!}

    <p>Set up the database connection</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::get_class('host') !!}">
        {!! Form::label('host', 'Database Host', ['class' => 'control-label']) !!}
        {!! Form::text('host', Request::input('host'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('host') !!}</span>
    </div>

    <div class="form-group {!! FormMessage::get_class('user') !!}">
        {!! Form::label('user', 'Database User', ['class' => 'control-label']) !!}
        {!! Form::text('user', Request::input('user'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('user') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::get_class('password') !!}">
        {!! Form::label('password', 'Database User Password', ['class' => 'control-label']) !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>

    <div class="form-group {!! FormMessage::get_class('name') !!}">
        {!! Form::label('name', 'Database Name', ['class' => 'control-label']) !!}
        {!! Form::text('name', Request::input('name'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::get_message('name') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::get_class('prefix') !!}">
        {!! Form::label('prefix', 'Database Prefix', ['class' => 'control-label']) !!}
        {!! Form::text('prefix', Request::input('prefix'), ['class' => 'form-control']) !!}
    </div>

    <!-- submit button -->
    {!! Form::submit('Run Install', ['class' => 'btn btn-primary']) !!}

    {!! Form::close() !!}

@endif

@section('scripts')
    <script type="text/javascript">
        function updatePageDataOption() {
            if ($('#theme').val() == '') {
                $('.page-data-group').hide();
                $('#page-data').prop('checked', false);
            } else {
                $('.page-data-group').show();
                $('#page-data').prop('checked', true);
            }
        }
        $(document).ready(function() {
            updatePageDataOption();
            $('#theme').change(updatePageDataOption);
        });
    </script>
@endsection