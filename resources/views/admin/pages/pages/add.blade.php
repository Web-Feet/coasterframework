<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>Adding New {!! $page_details->item_name !!}</h1>

@if (!empty($page_details->in_group))
    <div class="row textbox">
        <div class="col-sm-12">
            <p><a href="{!! URL::to(config('coaster::admin.url').'/groups/pages/'.$page_details->in_group) !!}">Back
                    to {!! $page_details->group_name !!}</a></p>
        </div>
    </div>
@endif

<br/>

{!! Form::open(['url' => URL::current(), 'class' => 'form-horizontal', 'id' => 'addForm', 'enctype' => 'multipart/form-data']) !!}

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
            selected_tab('#addForm', 0);
            $('#page_info\\[type\\]').change(function () {
                if ($(this).val() == 'link') {
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
            $('#page_info\\[name\\]').change(function () {
                console.log('546');
                $('#page_info_url').val(
                        $(this).val().toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '-').replace(/-{2,}/g, '-').replace(/^-+/g, '').replace(/-+$/g, '')
                );
            });
            load_editor_js();
        });
    </script>
@stop
