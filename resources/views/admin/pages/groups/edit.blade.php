<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>Group: {{ $group->name }}</h1>

<br />

{!! Form::open(['class' => 'form-horizontal', 'id' => 'editForm', 'enctype' => 'multipart/form-data']) !!}

<div class="tabbable">

    <ul class="nav nav-tabs">
        <li class="active"><a href="#group" data-toggle="tab">Group Settings</a></li>
        <li><a href="#group-attributes" data-toggle="tab">Group Attributes</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="group">

            <br /> <br />

            {!! CmsBlockInput::make('string', ['label' => 'Group Name', 'name' => 'name', 'value' => $group->name]) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Item Name', 'name' => 'item_name', 'value' => $group->item_name]) !!}
            {!! CmsBlockInput::make('select', ['label' => 'Default Template', 'name' => 'default_template', 'value' => $templateSelectContent]) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Url Priority', 'name' => 'url_priority', 'value' => $group->url_priority]) !!}

        </div>
        <div class="tab-pane" id="group-attributes">



        </div>
    </div>

</div>

{!! Form::close() !!}

@section('scripts')
    <script type='text/javascript'>
        $(document).ready(function () {



        });
    </script>
@append