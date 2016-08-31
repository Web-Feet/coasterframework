<h1>Form Rules {{ !empty($template)?' - '.$template:'' }}</h1>

@if (!empty($template))

    {!! Form::open() !!}

    <div class="table-responsive">
        <table id="rules" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Field</th>
                <th>Rule</th>
                <th>Remove</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rules as $rule)
                <tr id="rule_{!! $rule->id !!}">
                    <td>
                        {!! Form::text('rule['.$rule->id.'][field]', $rule->field, ['class' => 'form-control']) !!}
                    </td>
                    <td>
                        {!! Form::text('rule['.$rule->id.'][rule]', $rule->rule, ['class' => 'form-control']) !!}
                    </td>
                    <td>
                        <i class="glyphicon glyphicon-remove itemTooltip" title="Remove Rule" onclick="delete_rule('{!! $rule->id !!}')"></i>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="row textbox">
        <div class="col-sm-12">
            <button type="button" class="btn add_rule" onclick="add_rule()"><i class="fa fa-plus"></i>
                &nbsp; Add Another
            </button>
            <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp; Save Rules</button>
        </div>
    </div>

    {!! Form::close() !!}

    @section('scripts')
        <script type='text/javascript'>

            var new_rule = 1;

            function delete_rule(id) {
                $("#rule_" + id).remove();
            }

            function add_rule() {
                var id = 'new' + new_rule;
                new_rule++;
                $("#rules tbody").append('<tr id="rule_' + id + '">' +
                        '<td><input class="form-control" name="rule[' + id + '][field]" type="text"></td>' +
                        '<td><input class="form-control" name="rule[' + id + '][rule]" type="text"></td>' +
                        '<td><i class="glyphicon glyphicon-remove itemTooltip" title="Remove Rule" onclick="delete_rule(\'' + id + '\')"></i></td>' +
                        '</tr>'
                );
                $('.itemTooltip').tooltip({placement: 'bottom', container: 'body'});
            }

        </script>
    @stop


@else
    
    <p>Form validation rules are attached to a forms template, you can manage them below:</p>

    @if (!empty($templates))

        <h2>Form templates found</h2>

        <ul>

        @foreach($templates as $template)

            <li><a href="{{ route('coaster.admin.themes.forms', ['template' => $template]) }}">{{ $template }}</a></li>

        @endforeach

        </ul>

    @else

        <h2>No form templates found</h2>

    @endif

@endif