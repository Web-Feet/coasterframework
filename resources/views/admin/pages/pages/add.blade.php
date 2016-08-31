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
        $(document).ready(function () {

            selected_tab('#addForm', 0);
            updateListenPageUrl();
            updateListenGroupFields();
            updateListenLiveOptions();
            load_editor_js();
            headerNote();

            var link_show, url_prefix;
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

        });
    </script>
@append
