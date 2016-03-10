<h1>Select Block Options {{ !empty($block)?' - '.$block->name:'' }}</h1>

@if (!empty($block))

    {!! Form::open(['url' => Request::fullUrl()]) !!}

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

    {!! Form::close() !!}

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

@else
    
    <p>Select options are attached to individual blocks, you can manage them below:</p>

    @if (!empty($blocks))

        <h2>Blocks with select found</h2>

        <ul>

        @foreach($blocks as $id => $name)

            <li><a href="{{ URL::to(config('coaster::admin.url').'/themes/selects/'.$id) }}">{{ $name }}</a></li>

        @endforeach

        </ul>

    @else

        <h2>No select blocks found</h2>

    @endif

@endif