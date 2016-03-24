<h1>Installed Themes</h1>

@if ($error)
    <p class="text-danger">{{ $error }}</p>
@endif

{!! $themes_installed !!}

<div class="row">

    {!! Form::open(['url' => Request::url(), 'id' => 'uploadThemeForm', 'enctype' => 'multipart/form-data']) !!}
    <span class="btn btn-primary fileinput-nice">
        <i class="glyphicon glyphicon-upload glyphicon-white"></i>
        <span>Upload a new theme</span>
        <input type="file" name="newTheme" id="newTheme">
    </span>
    {{ Form::close() }}
    &nbsp;
    <a href="{{ URL::to(config('coaster::admin.url').'/themes/export') }}" class="btn btn-default"><i class="glyphicon glyphicon-download"></i> Export active theme</a>

</div>

@section('scripts')
    <script type="text/javascript">
        var themeSelected;
        $(document).ready(function () {
            $('.activateTheme').click(function () {
                themeSelected = $(this).data('theme');
                $.ajax({
                    url: get_admin_url() + 'themes/manage',
                    type: 'POST',
                    data: {
                        theme: themeSelected,
                        activate: 1
                    },
                    success: function (r) {
                        if (r == 1) {
                            console.log(1);
                            $('.activeTheme .activeSwitch').toggleClass('hidden');
                            $('#theme'+themeSelected+' .activeSwitch').toggleClass('hidden');
                            $('.activeTheme').removeClass('activeTheme');
                            $('#theme'+themeSelected+' .thumbnail').addClass('activeTheme');

                        }
                    }
                });
            });
            $('.installTheme').click(function () {
                themeSelected = $(this).data('theme');
                $.ajax({
                    url: get_admin_url() + 'themes/manage',
                    type: 'POST',
                    data: {
                        theme: themeSelected,
                        install: 1
                    },
                    success: function (r) {
                        r = parseInt(r);
                        if (r != 0) {
                            window.location.href = get_admin_url() + 'themes/update/' + r;
                        }
                    }
                });
            });
            $('.deleteTheme').click(function () {
                themeSelected = $(this).data('theme');
                $('#deleteTheme').modal('show');
            });
            $('#deleteTheme .yes').click(function () {
                $.ajax({
                    url: get_admin_url() + 'themes/manage',
                    type: 'POST',
                    data: {
                        theme: themeSelected,
                        remove: 1
                    },
                    success: function (r) {
                        if (r == 1) {
                            $(jq('theme'+themeSelected)).remove();
                        }
                    }
                });
            });
            $("#newTheme").change(function() {
                $('#uploadThemeForm').submit();
            });
        });
    </script>
@endsection