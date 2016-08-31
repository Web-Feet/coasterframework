<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>{!! $item_name !!}: {!! $page_lang->name !!}</h1>

<div class="row textbox">
    <div class="col-sm-6">
        @foreach($page->groups as $group)
            <p><a href="{!! route('coaster.admin.groups.pages', ['groupId' => $group->id]) !!}">Back
                    to {!! $group->name !!}</a></p>
        @endforeach
        @if ($publishingOn && $page->link == 0)
            <p id="version-well" class="well">
                Published Version: #<span class="live_version_id">{{ $version['live'] }}</span>
                @if ($page->is_live())
                    <?php $published = '<b>&nbsp;<span class="text-success version-p"> - latest version live</span></b>'; ?>
                    <?php $unPublished = '<b>&nbsp;<span class="text-danger version-up"> - latest version not published</span></b>'; ?>
                @else
                    <?php $published = '<b>&nbsp;<span class="text-warning version-p"> - latest version published (page not live)</span></b>'; ?>
                    <?php $unPublished = ' <b>&nbsp;<span class="text-danger version-up"> - latest version not published & page not live</span></b>'; ?>
                @endif
                @if ($version['live'] != $version['latest'])
                    {!! str_replace('version-p', 'version-p hidden', $published).$unPublished !!}
                @else
                    {!! $published.str_replace('version-up', 'version-up hidden', $unPublished) !!}
                @endif
                <br />
                Editing From Version: #{{ $version['editing'] }} &nbsp;&nbsp; (Latest Version: #{{ $version['latest'] }})
            </p>
        @endif
    </div>
    <div class="col-sm-6 text-right">
        @if ($auth['can_duplicate'])
            <button class="btn btn-danger" onclick="duplicate_page()">
                <i class="fa fa-files-o"></i> &nbsp; Duplicate Page
            </button> &nbsp;
        @endif
        @if ($page->link == 1)
            <a href="{{ $frontendLink }}" class="btn btn-warning"
               target="_blank">
                <i class="fa fa-eye"></i> &nbsp; View Doc / Url
            </a>
        @elseif (!$page->is_live())
            <a href="{{ $frontendLink }}" class="btn btn-warning"
               target="_blank">
                <i class="fa fa-eye"></i> &nbsp; Preview
            </a>
        @else
            <a href="{{ $frontendLink }}" class="btn btn-warning" target="_blank">
                <i class="fa fa-eye"></i> &nbsp; View Live Page
            </a>
        @endif

    </div>
</div>

<br/>

{!! Form::open(['class' => 'form-horizontal', 'id' => 'editForm', 'enctype' => 'multipart/form-data']) !!}

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

            selected_tab('#editForm', {{ $page->link ? 0 : 1 }});
            updateListenPageUrl(true);
            updateListenLiveOptions();
            updateListenGroupFields();
            load_editor_js();
            headerNote();

            page_id = parseInt({{ $page->id }});
            latest_version = '{{ $version['latest'] }}';

        });
    </script>
@append