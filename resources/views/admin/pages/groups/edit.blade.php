<?php AssetBuilder::setStatus('cms-editor', true); ?>

<div class="row">
<h1 class="pull-left">Group: {{ $group->name }}</h1>
<div class="pull-right">
    <a href="{{ route('coaster.admin.groups.pages', ['groupId' => $group->id]) }}" class="btn btn-warning addButton">
        <i class="fa fa-chevron-left"></i> &nbsp; Back to page list
    </a>
</div>
</div>

<br />

{!! Form::open(['class' => 'form-horizontal', 'id' => 'editForm', 'enctype' => 'multipart/form-data']) !!}

<div class="tabbable">

    <ul class="nav nav-tabs">
        <li class="active"><a href="#group" data-toggle="tab">Group Settings</a></li>
        <li><a href="#group-attributes" data-toggle="tab">Group Attributes</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="group">

            <br /><br />

            {!! CmsBlockInput::make('string', ['label' => 'Group Name', 'name' => 'group[name]', 'value' => $group->name]) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Item Name', 'name' => 'group[item_name]', 'value' => $group->item_name]) !!}
            {!! CmsBlockInput::make('select', ['label' => 'Default Template', 'name' => 'group[default_template]', 'selectOptions' => $templateSelectOptions, 'value' => $defaultTemplate]) !!}
            {!! CmsBlockInput::make('string', ['label' => 'Url Priority', 'name' => 'group[url_priority]', 'value' => $group->url_priority]) !!}

            <div class="col-sm-10 col-sm-offset-2">
                <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp; Save Group</button>
            </div>

        </div>
        <div class="tab-pane" id="group-attributes">

            <br /><br />

            <div class="table-responsive">
                <table id="attributes" class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Block</th>
                        <th>Order Priority</th>
                        <th>Order Dir</th>
                        <th>Delete ?</th>
                    </thead>
                    <tbody>
                        @foreach($group->groupAttributes as $attribute)
                            <tr id="groupAttribute_{!! $attribute->id !!}">
                                <td>
                                    {!! Form::select('groupAttribute['.$attribute->id.'][item_block_id]', $blockList, $attribute->item_block_id, ['class' => 'form-control']) !!}
                                </td>
                                <td>
                                    {!! Form::text('groupAttribute['.$attribute->id.'][item_block_order_priority]', $attribute->item_block_order_priority, ['class' => 'form-control']) !!}
                                </td>
                                <td>
                                    {!! Form::select('groupAttribute['.$attribute->id.'][item_block_order_dir]', ['asc' => 'Ascending', 'desc' => 'Descending'], $attribute->item_block_order_dir, ['class' => 'form-control']) !!}
                                </td>
                                <td>
                                    <i class="glyphicon glyphicon-remove itemTooltip" title="Remove Attribute"></i>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="groupAttribute_0" class="hidden">
                            <td>
                                {!! Form::select('groupAttribute[0][item_block_id]', $blockList, 0, ['class' => 'form-control']) !!}
                            </td>
                            <td>
                                {!! Form::text('groupAttribute[0][item_block_order_priority]', 0, ['class' => 'form-control']) !!}
                            </td>
                            <td>
                                {!! Form::select('groupAttribute[0][item_block_order_dir]', ['asc' => 'Ascending', 'desc' => 'Descending'], 0, ['class' => 'form-control']) !!}
                            </td>
                            <td>
                                <i class="glyphicon glyphicon-remove itemTooltip" title="Remove Attribute"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-sm-12">
                <button class="btn add_another" id="newAttribute" type="button"><i class="fa fa-plus"></i> &nbsp; New Attribute</button> &nbsp;
                <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp; Save Group</button>
            </div>

        </div>
    </div>

</div>

{!! Form::close() !!}

@section('scripts')
    <script type='text/javascript'>
        $(document).ready(function () {

            function deleteAttributeListener() {
                $('.glyphicon-remove').click(function() {
                    $(this).parent().parent().remove();
                });
            }

            deleteAttributeListener();

            var newRow = 0;
            $('#newAttribute').click(function() {
                newRow++;
                var i = 'new' + newRow;
                var rowHTML = $('#groupAttribute_0')[0].outerHTML.replace('groupAttribute_0', 'groupAttribute_' + i).replace(new RegExp('\\[0\\]', 'g'), '[' + i + ']').replace('class="hidden"', '');
                $("#attributes > tbody").append(rowHTML);
                deleteAttributeListener();
            });

        });
    </script>
@append