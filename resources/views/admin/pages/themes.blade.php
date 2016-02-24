<h1>Themes</h1>
<br/>

<p>Update template blocks for a particular theme (may take a few seconds to process files):</p>
<div class="form-horizontal">
    <div class="form-inline">
        {!! Form::select('theme', $themes, config('coaster::frontend.theme'), ['id' => 'selectTheme', 'class' => 'form-control long-select']) !!}
        &nbsp;
        <button id="updateTheme" class="btn btn-warning"><i class="fa fa-flag"></i> &nbsp; Review and Update</button>
    </div>
</div>

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('#updateTheme').click(function () {
                window.location.href += '/update/' + $('#selectTheme').val();
            });
        });
    </script>
@endsection