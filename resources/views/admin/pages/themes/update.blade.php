<h1>Theme Blocks - {{ $theme->theme }}</h1>

@if (isset($saved))

    <p class="text-success">Blocks have been successfully updated</p>
    <p><a href="{{ route('coaster.admin.themes.update', ['themeId' => $theme->id]) }}">&raquo; Return to review page</a></p>

@else

    <h4>How to use the form:</h4>
    <ul>
        <li>Blocks with the "update template" option checked will have their associated templates updated</li>
        <li>A block with the "show in site-wide content" option checked will appear in the site-wide content section of the admin.</li>
        <li>A block with the "show in pages" option checked will be created as a site-wide block but still be shown on any page templates it's in.</li>
        <li>If both the options above are checked, the block will appear in both pages and site-wide content.</li>
    </ul>
    <div class="row">
        <div class="col-md-4 well-sm">
            <h4>Summary:</h4>
            <p><b>Templates Found:</b> </p>
            <p><b>Number of blocks found:</b> </p>
        </div>
        <div class="col-md-8 well-sm">
            <h4>Key:</h4>
            <ul>
                <li class="well-sm bg-success">Newly found blocks that aren't currently in the theme are highlighted green.</li>
                <li class="well-sm bg-warning">Existing theme blocks that have template changes and repeater blocks with changes to their repeaters template are highlighted yellow.</li>
                <li class="well-sm bg-danger">Blocks that are no longer found in the theme templates are highlighted red.</li>
                <li class="well-sm bg-info">For blocks that rely on a page_id or page data, the templates can't be determined exactly and they are highlighted blue.</li>
                <li class="well well-sm">Blocks with no changes detected.</li>
            </ul>
        </div>
    </div>

    {!! Form::open() !!}

    <div class="form-group">
        {!! Form::submit('Update Blocks', ['class' => 'btn btn-primary']) !!}
    </div>

    <div class="table-responsive">
        <table id="themes-table" class="table table-striped table-bordered">

            <thead>
            <tr>
                <th>{!! Form::checkbox('update_all', 1, false, ['id' => 'update-all']) !!} <i class="glyphicon glyphicon-info-sign header_note" data-note="Blocks with the update template option checked will have their associated templates updated (untick on a red row to stop block being deleted)"></i> Update Templates</th>
                <th>Name</th>
                <th>Label</th>
                <th>Category/Tab</th>
                <th>Type</th>
                <th><i class="glyphicon glyphicon-info-sign header_note" data-note="The block will now appear in the site-wide content section of the admin instead of pages. (saved as a theme block rather than template block)"></i>Show in Site-wide Content</th>
                <th><i class="glyphicon glyphicon-info-sign header_note" data-note="The block will still appear in the pages when editing them. (saved as a theme block rather than template block)"></i>Show in Pages</th>
            </tr>
            </thead>

            <tbody>
            <?php $rowClasses = ['new' => 'success', 'delete' => 'danger', 'update' => 'warning', 'info' => 'info', 'none' => '']; ?>
            @foreach($blockChanges as $blockName => $dataGroups)
                <?php
                $dataFrom = empty($dataGroups['block']['import']) ? 'current' : 'import';
                $toAdd = empty($dataGroups['templates']['current']) && empty($dataGroups['other_view']['current']['repeaters']);
                $toDelete = empty($dataGroups['block']['import']);
                ?>
                <tr class="{{ $rowClasses[$dataGroups['display_class']] }}">
                    <td>{!! ($dataGroups['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][update_templates]', 1, $dataGroups['update_templates'], ['class' => 'form-control run-template-updates']):'' !!}</td>
                    <td><i class="glyphicon glyphicon-info-sign block_note" data-note="{{ $blockName }}_note"></i> {!! $dataGroups['block'][$dataFrom]['name'] !!}</td>
                    <td>{!! Form::text('block['.$blockName.'][label]', $dataGroups['block'][$dataFrom]['label'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($dataGroups['update_templates'] >= 0)?Form::select('block['.$blockName.'][category_id]', $categoryList, $dataGroups['block'][$dataFrom]['category_id'], ['class' => 'form-control']):'' !!}</td>
                    <td>{!! Form::select('block['.$blockName.'][type]', $typeList, $dataGroups['block'][$dataFrom]['type'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($dataGroups['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][show_in_global]', 1, $dataGroups['global'][$dataFrom]['show_in_global'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                    <td>{!! ($dataGroups['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][show_in_pages]', 1, $dataGroups['global'][$dataFrom]['show_in_pages'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                </tr>
                <tr class="hidden" id="{{ $blockName }}_note">
                    <td colspan="7" style="padding-bottom: 20px">
                        <div class="col-sm-6">
                            <h4>Current Info</h4>
                            @if ($toAdd)
                                This is a block is not currently in the theme.<br />
                            @else
                                @if (!empty($dataGroups['templates']['current']))
                                    @if (!empty($dataGroups['global']['current']['show_in_pages']) || !empty($dataGroups['global']['current']['show_in_global']))
                                        <?php
                                        $themeBlocks = [];
                                        if(!empty($dataGroups['global']['current']['show_in_pages'])) {
                                            $themeBlocks[] = 'pages';
                                        }
                                        if(!empty($dataGroups['global']['current']['show_in_global'])) {
                                            $themeBlocks[] = 'site-wide content';
                                        }
                                        ?>
                                        This is a theme block that is shown in {{ implode(' and ', $themeBlocks) }}.<br /><br />
                                    @endif
                                    <b>Currently in templates:</b> {!! implode(', ', $dataGroups['templates']['current']) !!}<br />
                                @endif
                                @if (!empty($dataGroups['other_view']['current']['repeaters']))
                                    <b>Currently used by repeater blocks:</b> {!! implode(', ', $dataGroups['other_view']['current']['repeaters']) !!}<br />
                                @endif
                            @endif
                            @if (!empty($dataGroups['other_view']['current']['repeater_children']))
                                <b>Current repeater child blocks:</b> {!! implode(', ', $dataGroups['other_view']['current']['repeater_children']) !!}<br />
                            @endif
                            @if (!empty($dataGroups['block']['current']))
                                <br />
                                @foreach($dataGroups['block']['current'] as $field => $value)
                                    <b>{{ ucwords(str_replace('_', ' ', $field)) }}</b>: <i>{{ $value }}</i><br />
                                @endforeach
                            @else
                                Also this block does not exist in the database.
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <h4>Updates found</h4>
                            @if ($toDelete)
                                Block not found in theme anymore.<br /><br />
                            @endif
                            @if ($addedToTemplates = array_diff($dataGroups['templates']['import'], $dataGroups['templates']['current']))
                                <b>Added from templates:</b> {!! implode(', ', $addedToTemplates) !!}<br />
                            @endif
                            @if ($removedFromTemplates = array_diff($dataGroups['templates']['current'], $dataGroups['templates']['import']))
                                <b>Removed from templates:</b> {!! implode(', ', $removedFromTemplates) !!}<br />
                            @endif
                            @if ($addedRepeaterChildren = array_diff($dataGroups['other_view']['import']['repeater_children'], $dataGroups['other_view']['current']['repeater_children']))
                                <b>Repeater child blocks added:</b> {!! implode(', ', $addedRepeaterChildren) !!}<br />
                            @endif
                            @if ($removedRepeaterChildren = array_diff($dataGroups['other_view']['current']['repeater_children'], $dataGroups['other_view']['import']['repeater_children']))
                                <b>Repeater child blocks removed:</b> {!! implode(', ', $removedRepeaterChildren) !!}<br />
                            @endif
                            @if ($addedToRepeaterTemplates = array_diff($dataGroups['other_view']['import']['repeaters'], $dataGroups['other_view']['current']['repeaters']))
                                <b>Added to repeater blocks</b>: {!! implode(', ', $addedToRepeaterTemplates) !!}<br />
                            @endif
                            @if ($removedFromRepeaterTemplates = array_diff($dataGroups['other_view']['current']['repeaters'], $dataGroups['other_view']['import']['repeaters']))
                                <b>Removed from repeater blocks</b>: {!! implode(', ', $removedFromRepeaterTemplates) !!}<br />
                            @endif
                            @if ($toDelete)
                                <?php
                                $deleteText = [];
                                if($removedFromTemplates) {
                                    $deleteText[] = 'this blocks templates';
                                }
                                if($removedFromRepeaterTemplates) {
                                    $deleteText[] = 'the repeater blocks templates';
                                }
                                ?>
                                <br />Once you update {{ implode(' and ', $deleteText) }} it will be removed from this theme.<br />
                            @endif
                            <br />
                            @if (!$toDelete)
                                @foreach($dataGroups['block']['current'] as $field => $currentValue)
                                    @if ($dataGroups['block']['import'][$field] != $currentValue)
                                        <b>{{ ucwords(str_replace('_', ' ', $field)) }}</b>: <i>{{ $currentValue }}</i> => <i>{{ $dataGroups['block']['import'][$field] }}</i><br />
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>

        </table>
    </div>

    <div class="form-group">
        {!! Form::submit('Update Blocks', ['class' => 'btn btn-primary']) !!}
    </div>

    {!! Form::close() !!}

@section('scripts')

    <script type="text/javascript">
        function disable_template_settings() {
            $(this).parent().parent().find('.based-on-template-updates').attr('disabled', !$(this).is(':checked'));
        }
        $(document).ready(function () {
            headerNote();
            $('.block_note').click(function () {
                $('#'+$(this).data('note')).toggleClass('hidden');
            });
            $('.run-template-updates').each(disable_template_settings).click(disable_template_settings);
            $('#update-all').click(function () {
                $('.run-template-updates').prop('checked', $(this).is(':checked')).each(disable_template_settings);
            });
        });
    </script>
@endsection

@endif
