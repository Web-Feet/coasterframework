<h1>Check Database</h1>

<br/>

<h2>Missing Tables / Columns</h2>
@if (!empty($errors))
    <ul>
        @foreach ($errors as $missing_table => $missing_columns)
            <li>The table '{!! $missing_table !!}' is missing the following columns:</li>
            <ul>
                @foreach ($missing_columns as $missing_column)
                    <li>{!! $missing_column->Field !!}</li>
                @endforeach
            </ul>
        @endforeach
    </ul>
@else
    <p>None</p>
@endif

<h2>Incorrect Column Settings</h2>
@if (!empty($warnings))
    <ul>
        @foreach ($warnings as $warning)
            <li>{!! $warning !!}</li>
        @endforeach
    </ul>
    <a href="{{ rtrim(rtrim(URL::current(),'/1'),'/').'/1' }}">Autofix column settings</a>
@else
    <p>None</p>
@endif

<h2>Other Notices</h2>
@if (!empty($notices))
    <ul>
        @foreach ($notices as $notice)
            <li>{!! $notice !!}</li>
        @endforeach
    </ul>
@else
    <p>None</p>
@endif

