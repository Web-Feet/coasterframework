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
        <p><b>Templates Found:</b> {{ $templateList }}</p>
        <p><b>Number of blocks found:</b> {{ count($blocksData) }}</p>
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

    <div class="table-responsive">
        <table id="themes-table" class="table table-striped table-bordered">

            <thead>
            <tr>
                <th>{!! Form::checkbox('update_all', 1, false, ['id' => 'update-all']) !!} <i class="glyphicon glyphicon-info-sign header_note" data-note="Blocks with the update template option checked will have their associated templates updated (untick on a red row to stop block being deleted)"></i> Update Templates</th>
                <th>Name</th>
                <th>Label</th>
                <th>Category/Tab</th>
                <th>Type</th>
                <th><i class="glyphicon glyphicon-info-sign header_note" data-note="A block with the show in site-wide content option checked will appear in the site-wide content section of the admin."></i>Show in Site-wide Content</th>
                <th><i class="glyphicon glyphicon-info-sign header_note" data-note="If both the show in pages and show in site-wide content boxes above are checked, the block will appear in both pages and site-wide content."></i>Show in Pages</th>
            </tr>
            </thead>

            <tbody>
            <?php $rowClasses = [1 => 'success', 2 => 'danger', 3 => 'warning', 4 => 'info', 5 => '']; ?>
            @foreach($blocksData as $block => $blockData)
                <tr class="{{ isset($blockData['rowClass'])?$rowClasses[$blockData['rowClass']]:'' }}">
                    <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][run_template_update]', 1, $blockData['run_template_update'], ['class' => 'form-control run-template-updates']):'' !!}</td>
                    <td><i class="glyphicon glyphicon-info-sign block_note" data-note="{{ $block }}_note"></i> {!! $blockData['name'] !!}</td>
                    <td>{!! Form::text('block['.$block.'][label]', $blockData['label'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($blockData['run_template_update'] >= 0)?Form::select('block['.$block.'][category_id]', $categoryList, $blockData['category_id'], ['class' => 'form-control']):'' !!}</td>
                    <td>{!! Form::select('block['.$block.'][type]', $typeList, $blockData['type'], ['class' => 'form-control']) !!}</td>
                    <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][global_site]', 1, $blockData['global_site'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                    <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][global_pages]', 1, $blockData['global_pages'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                </tr>
                <tr class="hidden" id="{{ $block }}_note">
                    <td colspan="7">{!! !empty($blockData['updates'])?'<b>Updates: </b>'.$blockData['updates'].'<br />':'' !!}<b>Found in templates: </b>{!! $blockData['templates'] !!}</td>
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
