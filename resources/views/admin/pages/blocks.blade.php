<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>Site-wide Content</h1>
<br/>

{!! Form::open(['url' => Request::url(), 'id' => 'blocksForm', 'class' => 'form-horizontal', 'enctype' => 'multipart/form-data']) !!}
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
            selected_tab('#blocksForm', 1);
            load_editor_js();
        });

    </script>
@stop
