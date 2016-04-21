<h1>Website Themes</h1>

<p>Here is a list of all themes currently uploaded to Coaster CMS.<br /><br /></p>

{!! $themes_installed !!}

@if ($can_upload)
<div class="row">

    <div class="col-sm-12">

        {!! Form::open(['url' => URL::to(config('coaster::admin.url').'/themes/manage'), 'id' => 'uploadThemeForm', 'enctype' => 'multipart/form-data']) !!}
        <span class="btn btn-primary fileinput-nice">
            <i class="glyphicon glyphicon-upload glyphicon-white"></i>
            <span>Upload a new theme</span>
            <input type="file" name="newTheme" id="newTheme">
        </span>
        {{ Form::close() }}

    </div>

</div>
@endif

@section('scripts')
    <script type="text/javascript">
        var themeSelected, themeIdSelected, wPageData;
        $(document).ready(function () {

            // activate
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
                            $('.activeTheme .activeSwitch').toggleClass('hidden');
                            $('#theme'+themeSelected+' .activeSwitch').toggleClass('hidden');
                            $('.activeTheme').removeClass('activeTheme');
                            $('#theme'+themeSelected+' .thumbnail').addClass('activeTheme');

                        }
                    }
                });
            });

            // install
            function installTheme() {
                $('#theme'+themeSelected+' .installTheme').html('Installing please wait ...');
                $.ajax({
                    url: get_admin_url() + 'themes/manage',
                    type: 'POST',
                    data: {
                        theme: themeSelected,
                        install: 1,
                        check: 0,
                        withPageData: wPageData
                    },
                    success: function (r) {
                        if (r === '1') {
                            location.reload();
                        } else {
                            cms_alert('danger', 'Error', 'An error occurred installing the theme: <br />'+ r.replace("\r\n", '<br />'));
                        }
                    }, error: function() {
                        cms_alert('danger', 'Error', 'An error occurred installing the theme: <br />'+r.replace("\r\n", '<br />'));
                    }
                });
            }
            $('.installTheme').click(function () {
                themeSelected = $(this).data('theme');
                wPageData = 0;
                $('#installTheme .themeName').html(themeSelected);
                $.ajax({
                    url: get_admin_url() + 'themes/manage',
                    type: 'POST',
                    data: {
                        theme: themeSelected,
                        install: 1,
                        check: 1
                    },
                    success: function (r) {
                        r = parseInt(r);
                        if (r != 0) {
                            if (r === 2) {
                                $('#installTheme .page-data').removeClass('hidden');
                            } else {
                                $('#installTheme .page-data').addClass('hidden');
                            }
                            $('#installTheme').modal('show');
                        } else {
                            $('#installThemeError').modal('show');
                        }
                    }, error: function() {
                        $('#installThemeError').modal('show');
                    }
                });
            });
            $('#installTheme .no-page-data').click(installTheme);
            $('#installTheme .page-data').click(function () {
                wPageData= 1;
                $('#installThemeConfirm').modal('show');
            });
            $('#installThemeConfirm .yes').click(installTheme);

            // delete
            $('.deleteTheme').click(function () {
                themeSelected = $(this).data('theme');
                $('#deleteTheme .themeName').html(themeSelected);
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

            // export
            $('.exportTheme').click(function () {
                themeSelected = $(this).data('theme');
                themeIdSelected = $(this).data('theme-id');
                $('#exportTheme .themeName').html(themeSelected);
                $('#exportTheme').modal('show');
            });
            $('#exportTheme .btn').click(function () {
                window.location.href = '{{ URL::to(config('coaster::admin.url').'/themes/export') }}/'+themeIdSelected+'/'+$(this).data('option');
            });

            // new theme
            $("#newTheme").change(function() {
                $('#uploadThemeForm').submit();
            });

        });
    </script>
@endsection