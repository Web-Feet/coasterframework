<h1>Themes</h1>
<br/>

<h2>Manage Themes</h2>

<p>View all uploaded themes. Can upload, install, activate and delete them here.</p>
<div class="form-horizontal">
    <div class="form-inline">
        <a href="{{ URL::to(config('coaster::admin.url').'/themes/manage') }}" class="btn btn-warning"><i class="fa fa-tint"></i> &nbsp; Manage Themes</a>
    </div>
</div>

<br />

<h2>Update Existing Themes</h2>

<p>Update template blocks for a particular theme (may take a few seconds to process files):</p>
<div class="form-horizontal">
    <div class="form-inline">
        {!! Form::select('theme', $themes, config('coaster::frontend.theme'), ['id' => 'selectTheme', 'class' => 'form-control long-select']) !!}
        &nbsp;
        <button id="updateTheme" class="btn btn-warning"><i class="fa fa-flag"></i> &nbsp; Review and Update</button>
    </div>
</div>

@if (!empty($blockSettings))

    <br />

    <h2>Block Settings</h2>

    @foreach($blockSettings as $name => $url)
        <p><a href="{{  $url }}">{{ $name }}</a></p>
    @endforeach

@endif

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#updateTheme').click(function () {
                window.location.href += '/update/' + $('#selectTheme').val();
            });
        });
    </script>
@endsection