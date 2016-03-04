<h1>System Settings</h1>

<br/>

{!! Form::open(['url' => Request::url()]) !!}

<div id="system_tabs" class="tabbable">

    <ul class="nav nav-tabs">
        <li id="navtab0"><a href="#tab0" data-toggle="tab">Settings</a></li>
        <li id="navtab1"><a href="#tab1" data-toggle="tab">Site Health</a></li>
    </ul>

    <div class="tab-content">

        <div class="tab-pane" id="tab0">

            <br/>

            <div class="table-responsive">
                <table class="table table-bordered">

                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Value</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($site_details as $setting)
                        <tr>
                            <td>{!!$setting->label !!}</td>
                            <td>
                                <?php $inputDetails = ($setting->editable) ? ['class' => 'form-control'] : ['class' => 'form-control', 'disabled' => true]; ?>
                                @if (is_string($setting->value))
                                    {!! Form::text($setting->name, $setting->value, $inputDetails) !!}
                                @else
                                    {!! Form::select($setting->name, $setting->value->options, $setting->value->selected, $inputDetails) !!}
                                @endif
                                @if ($setting->note)
                                    <span class="help-block">{{ $note }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>

            @if (Auth::action('system.update'))
                <div class="form-group">
                    {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
                </div>
            @endif
        </div>


        <div class="tab-pane" id="tab1">
            <br/>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tbody>
                    <tr>
                        <td>PHP Version</td>
                        <td><span class="{{ version_compare(phpversion(), '5.5.9')?'text-success':'text-danger' }}">{{ phpversion() }}
                                &nbsp; (required: 5.5.9+)</span></td>
                    </tr>
                    <tr>
                        <td>Database Structure</td>
                        <td>
                            @if (!empty($database_structure['errors']))
                                <span class="text-danger">{{ count($database_structure['errors']).' '.str_plural('error', count($database_structure['errors'])) }}
                                    found</span>
                            @elseif (!empty($database_structure['warnings']))
                                <span class="text-warning">{{ count($database_structure['warnings']).' '.str_plural('warning', count($database_structure['warnings'])) }}
                                    found</span>
                            @elseif (!empty($database_structure['notices']))
                                <span class="text-success">{{ count($database_structure['notices']).' '.str_plural('notice', count($database_structure['notices'])) }}
                                    found</span>
                            @else
                                <span class="text-success">No errors found</span>
                            @endif
                            @if ($can_validate)
                                <a href="{!! URL::to(config('coaster::admin.url').'/system/validate-db') !!}">(more
                                    details)</a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Search Index</td>
                        <td>
                            @if ($last_indexed_search)
                                <span id="last_indexed_search">Last ran on the {{ date('dS M Y H:i:s', $last_indexed_search) }}</span>
                                @if ($can_index_search)
                                    <a href="javascript:void(0)" id="search_index">(reindex)</a>
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {!! Form::close() !!}

    @section('scripts')
        <script type='text/javascript'>
            $(document).ready(function () {
                $('#system_tabs a:first').tab('show');
                $('#search_index').click(function () {
                    $('#search_index').html("(reindex in progress)");
                    var aj = $.ajax({
                        url: get_admin_url() + 'system/search',
                        type: 'GET',
                        success: function (r) {
                            if (r == 1) {
                                var index_date = new Date();
                                var month = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                $('#last_indexed_search').addClass("text-success");
                                $('#last_indexed_search').html("Last ran on the " + index_date.getDate() + '' + nth(index_date.getDate()) + ' ' + month[index_date.getMonth()] + ' ' + index_date.getFullYear() + ' ' + index_date.toLocaleTimeString());
                            }
                            else {
                                $('#last_indexed_search').addClass("text-danger");
                                $('#last_indexed_search').html("failed the reindex");
                            }
                            $('#search_index').html("(reindex)");
                        }
                    });
                });
                @if ($can_validate)
                $('#db_tables').click(function () {
                    $('#db_tables').addClass("btn-warning");
                    $('#db_tables').html("Validation (In Progress)");
                    var aj = $.ajax({
                        url: get_admin_url() + 'system/validate-db',
                        type: 'GET',
                        dataType: 'json',
                        success: function (r) {
                            $('#db_tables').removeClass("btn-warning");
                            if (r == 1) {
                                $('#db_tables').addClass("btn-success");
                                $('#db_tables').html("Reindex (Complete)");
                            }
                            else {
                                $('#db_tables').addClass("btn-danger");
                                $('#db_tables').html("Reindex (Error)");
                            }
                        }
                    });
                });
                @endif
            });
        </script>
@stop


