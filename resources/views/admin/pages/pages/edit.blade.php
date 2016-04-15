<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>{!! $page_details->item_name !!}: {!! $page_details->name !!}</h1>

<div class="row textbox">
    <div class="col-sm-6">
        @if (!empty($page_details->in_group))
            <p><a href="{!! URL::to(config('coaster::admin.url').'/groups/pages/'.$page_details->in_group) !!}">Back
                    to {!! $page_details->group_name !!}</a></p>
        @endif
        @if ($publishing)
            <p class="well">
                Published Version: #<span class="live_version_id">{{ $live_version }}</span>
                @if ($page_details->currently_live)
                    @if ($live_version != $latest_version) &nbsp; <b><span class="text-danger"> - latest version not published</span></b>
                    @else &nbsp; <b><span class="text-success"> - latest version live</span></b>@endif
                @else
                    @if ($live_version != $latest_version) &nbsp; <b><span class="text-danger"> - latest version not published & page not live</span></b>
                    @else &nbsp; <b><span class="text-danger"> - latest version published (page not live)</span></b>@endif
                @endif
                <br />

                Editing From Version: #{{ $editing_version }} &nbsp;&nbsp; (Latest Version: #{{ $latest_version }})
            </p>
        @endif
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_duplicate)
            <button class="btn btn-danger" onclick="duplicate_page()">
                <i class="fa fa-files-o"></i> &nbsp; Duplicate Page
            </button> &nbsp;
        @endif
        @if($preview)
            <a href="{{ PageBuilder::page_url($page_details->id).'?preview='.$preview }}" class="btn btn-warning"
               target="_blank">
                <i class="fa fa-eye"></i> &nbsp; Preview
            </a>
        @else
            <a href="{{ $page_details->full_url }}" class="btn btn-warning" target="_blank">
                <i class="fa fa-eye"></i> &nbsp; View Live Page
            </a>
        @endif

    </div>
</div>

<br/>

{!! Form::open(['url' => Request::url(), 'class' => 'form-horizontal', 'id' => 'editForm', 'enctype' => 'multipart/form-data']) !!}

<div class="tabbable">

    <ul class="nav nav-tabs">
        {!! $tab['headers'] !!}
    </ul>

    <div class="tab-content">
        {!! $tab['contents'] !!}
    </div>

</div>

<input type="hidden" name="duplicate" value="0" id="duplicate_set">

{!! Form::close() !!}

@section('scripts')
    <script type='text/javascript'>
        function duplicate_page() {
            $('#duplicate_set').val(1);
            $('#editForm').trigger('submit');
        }
        $(document).ready(function () {

            liveDateOptions();
            $('#page_info\\[live\\]').change(liveDateOptions);

            page_id = {{ $page_details->id }};

            @if ($page_details->link == 1)
            selected_tab('#editForm', 0);
            @else
            selected_tab('#editForm', 1);
            @endif

            $('#page_info\\[name\\]').change(function () {
                if ($('#page_info_url').val().substr($('#page_info_url').val().length - 10) == '-duplicate') {
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