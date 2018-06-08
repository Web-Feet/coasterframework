<h1>Theme Blocks - {{ $theme->theme }}</h1>

@if (!empty($themeErrors))

    <div class="table-responsive">
        <table id="themes-table" class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Errors found in theme, can not continue.</th>
            </tr>
            </thead>
            <tbody>
            @foreach($themeErrors as $error)
                <tr>
                    <td>
                        {{ $error->getMessage() }}<br />
                        <i>{{ $error->getFile() . ':' . $error->getLine() }}</i>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

@elseif (isset($saved))

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
            <p><b>Templates Used:</b> {{ implode(', ', $importBlocks->getTemplates()) }}</p>
            <p><b>Number of blocks found:</b> {{ count($importBlocksList) }}</p>
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
            @php $rowClasses = ['new' => 'success', 'delete' => 'danger', 'update' => 'warning', 'info' => 'info', 'none' => '']; @endphp
            @foreach($importBlocksList as $blockName => $listInfo)
                @php
                $importBlock = $importBlocks->getAggregatedBlock($blockName);
                $currentBlock = $importBlocks->getBlock($blockName, 'db');
                @endphp
                <tr class="{{ $rowClasses[$listInfo['display_class']] }}">
                    <td>{!! ($listInfo['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][update_templates]', 1, $listInfo['update_templates'], ['class' => 'form-control run-template-updates']):'' !!}</td>
                    <td><i class="glyphicon glyphicon-info-sign block_note" data-note="{{ $blockName }}_note"></i> {!! $blockName !!}</td>
                    <td>{!! Form::text('block['.$blockName.'][blockData][label]', $importBlock->blockData['label'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($listInfo['update_templates'] >= 0)?Form::select('block['.$blockName.'][blockData][category_id]', $categoryList, $importBlock->blockData['category_id'], ['class' => 'form-control']):'' !!}</td>
                    <td>{!! Form::select('block['.$blockName.'][blockData][type]', $typeList, $importBlock->blockData['type'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($listInfo['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][globalData][show_in_global]', 1, $importBlock->globalData['show_in_global'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                    <td>{!! ($listInfo['update_templates'] >= 0)?Form::checkbox('block['.$blockName.'][globalData][show_in_pages]', 1, $importBlock->globalData['show_in_pages'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                </tr>
                <tr class="hidden" id="{{ $blockName }}_note">
                    <td colspan="7" style="padding-bottom: 20px">
                        <div class="col-sm-6">
                            <h4>Current Info (From Database)</h4>
                            @if ($listInfo['display_class'] == 'new')
                                This is a block is not currently in the theme.<br /><br />
                            @else
                                @if ($globalData = array_filter($currentBlock->globalData))
                                    This is a theme block that is shown in {{ implode(' and ', array_intersect_key(['show_in_pages' => 'pages', 'show_in_global' => 'site-wide content'], $globalData)) }}.<br /><br />
                                @endif
                                @if ($currentBlock->templates || $currentBlock->inRepeaterBlocks)
                                    @if ($currentBlock->templates)
                                        <b>In templates:</b> {!! implode(', ', $currentBlock->templates) !!}<br />
                                    @endif
                                    @if ($currentBlock->inRepeaterBlocks)
                                        <b>In repeater blocks:</b> {!! implode(', ', $currentBlock->inRepeaterBlocks) !!}<br />
                                    @endif
                                    <br />
                                @endif
                            @endif
                            @if ($currentBlock->repeaterChildBlocks)
                                <b>Has repeater child blocks:</b> {!! implode(', ', $currentBlock->repeaterChildBlocks) !!}<br /><br />
                            @endif
                            @if (count($currentBlock->blockData) > 1)
                                @foreach($currentBlock->blockData as $field => $value)
                                    <b>{{ ucwords(str_replace('_', ' ', $field)) }}</b>: <i>{{ $value }}</i><br />
                                @endforeach
                            @else
                                Also this block does not exist in the database.
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <h4>Updates found</h4>
                            @if ($importBlock->inCategoryTemplates || $importBlock->specifiedPageIds)
                                Found some instances where the template could not be determined:<br />
                                @if ($importBlock->inCategoryTemplates)
                                    <b>Found in category templates:</b> {!! implode(', ', $importBlock->inCategoryTemplates) !!}<br />
                                @endif
                                @if ($importBlock->specifiedPageIds)
                                    <b>Using set page ids:</b> {!! implode(', ', $importBlock->specifiedPageIds) !!}<br />
                                @endif
                                <br />
                            @endif
                            @if ($updatedGlobalValues = $importBlocks->updatedValues($importBlock, 'globalData'))
                                @foreach($updatedGlobalValues as $field => $changedValues)
                                    <b>{{ ucwords(str_replace('_', ' ', $field)) }}</b>: <i>{{ $changedValues['old'] }}</i> => <i>{{ $changedValues['new'] }}</i><br />
                                @endforeach
                            @endif
                            @if ($addedToTemplates = $importBlocks->newElements($importBlock, 'templates'))
                                <b>Added to templates:</b> {!! implode(', ', $addedToTemplates) !!}<br />
                            @endif
                            @if ($removedFromTemplates = $importBlocks->deletedElements($importBlock, 'templates'))
                                <b>Removed from templates:</b> {!! implode(', ', $removedFromTemplates) !!}<br />
                            @endif
                            @if ($addedRepeaterChildren = $importBlocks->newElements($importBlock, 'repeaterChildBlocks'))
                                <b>Repeater child blocks added:</b> {!! implode(', ', $addedRepeaterChildren) !!}<br />
                            @endif
                            @if ($removedRepeaterChildren = $importBlocks->deletedElements($importBlock, 'repeaterChildBlocks'))
                                <b>Repeater child blocks removed:</b> {!! implode(', ', $removedRepeaterChildren) !!}<br />
                            @endif
                            @if ($addedToRepeaterTemplates = $importBlocks->newElements($importBlock, 'inRepeaterBlocks'))
                                <b>Added to repeater blocks</b>: {!! implode(', ', $addedToRepeaterTemplates) !!}<br />
                            @endif
                            @if ($removedFromRepeaterTemplates = $importBlocks->deletedElements($importBlock, 'inRepeaterBlocks'))
                                <b>Removed from repeater blocks</b>: {!! implode(', ', $removedFromRepeaterTemplates) !!}<br />
                            @endif
                            @if ($listInfo['display_class'] == 'delete')
                                @if ($listInfo['update_templates'] >= 0 || $removedFromTemplates || $removedFromRepeaterTemplates)
                                    Once you update the templates on {{ implode(' and ', array_keys(array_filter([
                                        'this block' => $currentBlock->templates,
                                        'the repeater blocks above' => $currentBlock->inRepeaterBlocks
                                    ]))) }} it will be removed from this theme.<br />
                                @else
                                    Block declared, but not found in files.<br />
                                    To delete remove the declaration (possibly in the import csv).
                                @endif
                            @elseif ($addedToTemplates || $removedFromTemplates || $addedToRepeaterTemplates || $removedFromRepeaterTemplates || $updatedGlobalValues)
                                Changes will be saved when templates on {{ implode(' and ', array_keys(array_filter([
                                    'this block' => $addedToTemplates || $removedFromTemplates  || $updatedGlobalValues,
                                    'the repeater blocks above' => $addedToRepeaterTemplates || $removedFromRepeaterTemplates
                                ]))) }} are updated.<br />
                            @endif
                            @if ($addedToTemplates || $removedFromTemplates || $addedRepeaterChildren || $removedRepeaterChildren || $addedToRepeaterTemplates || $removedFromRepeaterTemplates || $updatedGlobalValues)
                                <br />
                            @endif
                            @if ($listInfo['display_class'] != 'delete' && $updatedValues = $importBlocks->updatedValues($importBlock, 'blockData'))
                                Data changes will always be saved on update.<br />
                                @foreach($updatedValues as $field => $changedValues)
                                    <b>{{ ucwords(str_replace('_', ' ', $field)) }}</b>: <i>{{ $changedValues['old'] }}</i> => <i>{{ $changedValues['new'] }}</i><br />
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
