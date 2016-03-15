<h1>Theme Blocks - {{ $theme }}</h1>

@if (isset($saved))

<p class="text-success">Blocks have been successfully updated</p>
<p>{!! HTML::link(URL::Current(), '&raquo; Return to review page') !!}</p>

@else

<p>&nbsp;</p>

<p>
    Here is a list of all blocks found in the theme.
</p>
<p>
    Blocks with the "update template" option checked will be added to any templates they are found in (and removed from ones they are no longer in).<br/>
    Newly found blocks will have the option automatically checked (and are highlighted green).<br />
    Existing blocks found in new templates or no longer found in others will have this option checked as well (and are highlighted yellow).
</p>
<p>
    Repeater blocks will also be highlighted yellow if blocks within the repeaters template change.
</p>
<p>
    A block with the "show in site-wide content" option checked will appear in the site-wide content section of the admin.<br/>
    A block with the "show in pages" option checked will be created as a site-wide block and shown on any page templates it's in.<br />
    If both options check the block will appear in both pages and site-wide content.
</p>

<p>&nbsp;</p>

<p><b>Templates Found:</b> {{ $templateList }}</p>

<p><b>Number of blocks found:</b> {{ count($blocksData) }}</p>

{!! Form::open(['url' => Request::url()]) !!}

<div class="table-responsive">
    <table id="themes-table" class="table table-striped table-bordered">

        <thead>
        <tr>
            <th>{!! Form::checkbox('update_all', 1, false, ['id' => 'update-all']) !!} Update Templates</th>
            <th>Name</th>
            <th>Label</th>
            <th>Category/Tab</th>
            <th>Type</th>
            <th>Show in Site-wide Content</th>
            <th>Show in Pages</th>
        </tr>
        </thead>

        <tbody>
        <?php $rowClasses = [1 => 'success', 2 => 'warning', 3 => '', 4 => '']; ?>
        @foreach($blocksData as $block => $blockData)
            <tr class="{{ isset($blockData['rowClass'])?$rowClasses[$blockData['rowClass']]:'' }}">
                <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][run_template_update]', 1, $blockData['run_template_update'], ['class' => 'form-control run-template-updates']):'' !!}</td>
                <td><i class="glyphicon glyphicon-info-sign block_note" data-note="{{ $block }}_note"></i> {!! $blockData['name'] !!}</td>
                <td>{!! Form::text('block['.$block.'][label]', $blockData['label'], ['class' => 'form-control']) !!}</td>
                <td>{!! Form::select('block['.$block.'][category_id]', $categoryList, $blockData['category_id'], ['class' => 'form-control']) !!}</td>
                <td>{!! Form::select('block['.$block.'][type]', $typeList, $blockData['type'], ['class' => 'form-control']) !!}</td>
                <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][global_site]', 1, $blockData['global_site'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
                <td>{!! ($blockData['run_template_update'] >= 0)?Form::checkbox('block['.$block.'][global_pages]', 1, $blockData['global_pages'], ['class' => 'form-control based-on-template-updates']):'' !!}</td>
            </tr>
            <tr class="hidden" id="{{ $block }}_note">
                <td colspan="7">{!! !empty($blockData['updates'])?'<b>Updates: </b>'.$blockData['updates'].'<br />':'' !!}<b>Found in templates: </b>{{ $blockData['templates'] }}</td>
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