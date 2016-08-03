<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>Adding New {!! $item_name !!}</h1>

<br/>

{!! Form::open(['class' => 'form-horizontal', 'id' => 'addForm', 'enctype' => 'multipart/form-data']) !!}

<div class="tabbable">

    <ul class="nav nav-tabs">
        {!! $tab['headers'] !!}
    </ul>

    <div class="tab-content">
        {!! $tab['contents'] !!}
    </div>

</div>

{!! Form::close() !!}

@section('scripts')
    <script type='text/javascript'>
        var link_show, url_prefix;
        $(document).ready(function () {

            liveDateOptions();
            $('#page_info\\[live\\]').change(liveDateOptions);

            selected_tab('#addForm', 0);

            $('#page_info\\[link\\]').change(function () {
                if ($(this).is(':checked')) {
                    url_prefix = $('#url-prefix').detach();
                    if (link_show) {
                        link_show.appendTo('#url-group');
                    }
                    $('#template_select').hide();
                }
                else {
                    if (url_prefix) {
                        url_prefix.prependTo('#url-group');
                    }
                    link_show = $('.link_show').detach();
                    $('#template_select').show();
                }
            }).trigger('change');

            $('#page_info\\[parent\\]').change(function () {
                $('#url-prefix').html(urlArray[$(this).val()]);
            });

            $('#page_info_lang\\[name\\]').change(function () {
                if (!$('#page_info\\[link\\]').is(':checked')) {
                    $('#page_info_url').val(
                        $(this).val()
                            .toLowerCase()
                            .replace(/\s+/g, '-')
                            .replace(/[^\w-]/g, '-')
                            .replace(/-{2,}/g, '-')
                            .replace(/^-+/g, '')
                            .replace(/-+$/g, '')
                    );
                }
            });

            load_editor_js();

        });
    </script>
@stop
