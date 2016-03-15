<h1>Installed Themes</h1>

@if ($error)
    <p class="text-danger">{{ $error }}</p>
@endif

{!! $themes_installed !!}

{!! Form::open(['url' => Request::url(), 'id' => 'uploadThemeForm', 'enctype' => 'multipart/form-data']) !!}
<span class="btn btn-primary fileinput-nice">
    <i class="glyphicon glyphicon-upload glyphicon-white"></i>
    <span>Upload a new theme</span>
    <input type="file" name="newTheme" id="newTheme">
</span>
{{ Form::close() }}


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
                            $('.activeSwitch').toggleClass('hidden');
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