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

            {!! CmsBlockInput::make('string', ['label' => 'Group Name', 'name' => 'name']) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Item Name', 'name' => 'name']) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Default Template', 'name' => 'name']) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Url Priority', 'name' => 'name']) !!}

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