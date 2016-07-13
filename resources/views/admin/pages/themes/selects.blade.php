<h1>Select Block Options {{ !empty($block)?' - '.$block->name:'' }}</h1>

@if (!empty($block))

    <p><a href="{{ route('coaster.admin.themes.selects') }}">&raquo; Back to select option blocks</a></p>

    {!! Form::open() !!}

    @if (!empty($import))

        <p><a href="{{ route('coaster.admin.themes.selects', ['blockId' => $block->id]) }}">&raquo; Manage options (simple add/edit/remove)</a></p>
        <p>&nbsp;</p>

        <div class="row">

            <div class="col-sm-12">

                <div class="form-group row">
                    <label for="selectOptionImport" class="control-label col-sm-2">Import From</label>
                    <div class="col-sm-10">
                        {!! Form::select('selectOptionImport', $import, null, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="selectOptionText" class="control-label col-sm-2">Import Option</label>
                    <div class="col-sm-10">
                        {!! Form::text('selectOptionValue', '<span class="fa $match"></span>', ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <label for="selectOptionValue" class="control-label col-sm-2">Import Option Text</label>
                    <div class="col-sm-10">
                        {!! Form::text('selectOptionText', 'fa $match', ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button class="btn btn-primary" type="submit"><span class="glyphicon glyphicon-upload glyphicon-white"></span>
                            &nbsp; Import
                        </button>
                    </div>
                </div>

            </div>

        </div>

    @else

        <p><a href="{{ route('coaster.admin.themes.selects', ['blockId' => $block->id, 'import' => 1]) }}">&raquo; Import options from source (will overwrite existing options on update)</a></p>
        <p>&nbsp;</p>

        <div class="table-responsive">
            <table id="selectOptions" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Option Text</th>
                    <th>Option Value</th>
                    <th>Remove</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($options as $option)
                    <tr id="selectOption_{!! $option->id !!}">
                        <td>
                            {!! Form::text('selectOption['.$option->id.'][option]', $option->option, ['class' => 'form-control']) !!}
                        </td>
                        <td>
                            {!! Form::text('selectOption['.$option->id.'][value]', $option->value, ['class' => 'form-control']) !!}
                        </td>
                        <td>
                            <i class="glyphicon glyphicon-remove itemTooltip" title="Remove Option" onclick="delete_option('{!! $option->id !!}')"></i>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="row textbox">
            <div class="col-sm-12">
                <button type="button" class="btn" onclick="add_option()"><i class="fa fa-plus"></i>
                    &nbsp; Add Another
                </button>
                <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp; Save Options</button>
            </div>
        </div>

@section('scripts')
    <script type='text/javascript'>

        var new_selectOption = 1;

        function delete_option(id) {
            $("#selectOption_" + id).remove();
        }

        function add_option() {
            var id = 'new' + new_selectOption;
            new_selectOption++;
            $("#selectOptions tbody").append('<tr id="selectOption_' + id + '">' +
                    '<td><input class="form-control" name="selectOption[' + id + '][option]" type="text"></td>' +
                    '<td><input class="form-control" name="selectOption[' + id + '][value]" type="text"></td>' +
                    '<td><i class="glyphicon glyphicon-remove itemTooltip" title="Remove Option" onclick="delete_option(\'' + id + '\')"></i></td>' +
                    '</tr>'
            );
            $('.itemTooltip').tooltip({placement: 'bottom', container: 'body'});
        }

    </script>
@stop

@endif

{!! Form::close() !!}

@else

    <p>Select options are attached to individual blocks, you can manage them below:</p>

    @if (!empty($blocks))

        <h2>Blocks with select found</h2>

        <ul>

            @foreach($blocks as $id => $name)

                <li><a href="{{ route('coaster.admin.themes.selects', ['blockId' => $id]) }}">{{ $name }}</a></li>

            @endforeach

        </ul>

    @else

        <h2>No select blocks found</h2>

    @endif

@endif