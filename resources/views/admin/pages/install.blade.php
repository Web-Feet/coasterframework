<h1>Install Coaster CMS</h1>

<br/>

@if ($stage == 'complete')

    <p class="text-success">Install Complete</p>
    <p><a href="{{ route('coaster.admin.login') }}">Go to admin and login</a></p>
    <p><a href="{{ URL::to('/') }}" target="_blank">Go to front-end</a></p>

@elseif ($stage == 'theme')

    {!! Form::open(['url' => route('coaster.install.themeInstall')]) !!}

    <p>Select a theme to use</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::getErrorClass('theme') !!}">
        {!! Form::label('theme', 'Theme', ['class' => 'control-label']) !!}
        {!! Form::select('theme', $themes, $defaultTheme, ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('theme') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::getErrorClass('page-data') !!} page-data-group">
        {!! Form::label('page-data', 'Install with example page data (recommended)', ['class' => 'control-label']) !!}
        {!! Form::checkbox('page-data', 1, true, ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('page-data') !!}</span>
    </div>

    <!-- submit button -->
    {!! Form::submit('Complete Install', ['class' => 'btn btn-primary']) !!}

    {!! Form::close() !!}

@elseif ($stage == 'adduser')

    {!! Form::open(['url' => route('coaster.install.adminSave')]) !!}

    <p>Set up the admin user</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::getErrorClass('email') !!}">
        {!! Form::label('email', 'Admin Email', ['class' => 'control-label']) !!}
        {!! Form::text('email', Request::input('user'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('email') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::getErrorClass('password') !!}">
        {!! Form::label('password', 'Admin Password', ['class' => 'control-label']) !!}
        {!! Form::password('password', ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('password') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::getErrorClass('password_confirmation') !!}">
        {!! Form::label('password_confirmation', 'Admin Password Confirmation', ['class' => 'control-label']) !!}
        {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
    </div>

    <!-- submit button -->
    {!! Form::submit('Create User', ['class' => 'btn btn-primary']) !!} &nbsp; {!! $currentUsers > 0 ? Form::submit('Skip', ['name' => 'skip', 'class' => 'btn btn-default']) : ''  !!}

    {!! Form::close() !!}

@elseif ($stage == 'envCheck')

    <p class="text-warning">The database config has bee saved in the .env file, however the environment variables loaded are different to what's in the .env file</p>

    @if (php_sapi_name() == 'cli-server')
        <p>If you are running artisan serve you may need to restart it.</p>
    @endif

    <p>&nbsp;</p>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Environment Variable</th>
            <th>Current Value</th>
            <th>.env File Value</th>
        </tr>
        </thead>
        <tbody>
        @foreach($unMatchedEnvVars as $envVar => $envValue)
            <tr>
                <td>{!! $envVar !!}</td>
                <td>{!! getenv($envVar) !!}</td>
                <td>{!! $envValue !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p>&nbsp;</p>

    <a href="{{ route('coaster.install.databaseMigrate', ['shipEnvCheck' => 1]) }}" class="btn btn-default">Ignore Warning</a> &nbsp; <a href="{{ route('coaster.install.databaseMigrate') }}" class="btn btn-primary">Recheck & Continue</a>

@elseif ($stage == 'database')

    {!! Form::open(['url' => route('coaster.install.databaseSave')]) !!}

    <p>Set up the database connection<br />
        Will load current values if set in the .env file</p>
    <p>&nbsp;</p>

    <div class="form-group {!! FormMessage::getErrorClass('host') !!}">
        {!! Form::label('host', 'Database Host', ['class' => 'control-label']) !!}
        {!! Form::text('host', Request::input('host')?:env('DB_HOST'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('host') !!}</span>
    </div>

    <div class="form-group {!! FormMessage::getErrorClass('user') !!}">
        {!! Form::label('user', 'Database User', ['class' => 'control-label']) !!}
        {!! Form::text('user', Request::input('user')?:env('DB_USERNAME'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('user') !!}</span>
    </div>
    <div class="form-group {!! FormMessage::getErrorClass('password') !!}">
        {!! Form::label('password', 'Database User Password', ['class' => 'control-label']) !!}
        {!! Form::input('password', 'password', env('DB_PASSWORD'), ['class' => 'form-control']) !!}
    </div>

    <div class="form-group {!! FormMessage::getErrorClass('name') !!}">
        {!! Form::label('name', 'Database Name', ['class' => 'control-label']) !!}
        {!! Form::text('name', Request::input('name')?:env('DB_DATABASE'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('name') !!}</span>
    </div>
    @if ($allowPrefix)
    <div class="form-group {!! FormMessage::getErrorClass('prefix') !!}">
        {!! Form::label('prefix', 'Database Prefix', ['class' => 'control-label']) !!}
        {!! Form::text('prefix', Request::input('prefix')?:env('DB_PREFIX'), ['class' => 'form-control']) !!}
    </div>
    @endif

    <!-- submit button -->
    {!! Form::submit('Run Database Setup', ['class' => 'btn btn-primary']) !!}

    {!! Form::close() !!}

@elseif ($stage == 'permissions')

    <p>Checking the following folders and files are writable:</p>
    <p>&nbsp;</p>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Location</th>
                <th>Writable ?</th>
            </tr>
        </thead>
        <tbody>
        @foreach($dirs as $dir => $isWritable)
            <tr>
                <td>{!! $dir . (base_path('.env') == $dir ? ' *' : '') !!}</td>
                <td class="{{ $isWritable ? 'text-success' : 'text-danger' }}">{{ $isWritable ? 'Yes' : 'Nah' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p>* only required to be writable for installation</p>
    <p>&nbsp;</p>

    <a href="{{ route('coaster.install.permissions') }}" class="btn btn-default">Recheck</a> &nbsp; <a href="{{ $continue ? route('coaster.install.permissions', ['next' => 1]) : '#' }}" class="btn btn-primary" {{ $continue ? '' : 'disabled="disabled"' }}>Continue</a>

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