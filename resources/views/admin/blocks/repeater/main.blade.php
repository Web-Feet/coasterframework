<div class="row">
    <h4 class="col-sm-12">{{ $label }}</h4>

    <div class="col-sm-12 {!! $renderedRows ? '' : ' hide' !!}">
        <table id="repeater_{!! $content !!}" data-repeater="{{ $content }}" data-max="{{ $maxRows }}" class="table table-bordered repeater-table">
            <tbody>
                {!! $renderedRows !!}
            </tbody>
        </table>
    </div>

    <div class="col-sm-10">
        <div class="form-group">
            <div class="row">
                <div class="col-sm-12">
                    {!! Form::hidden($name . '[repeater_id]', $content) !!}
                    {!! Form::hidden($name . '[parent_repeater_id]', $_block->getRepeaterId()) !!}
                    {!! Form::hidden($name . '[parent_repeater_row_id]', $_block->getRepeaterRowId()) !!}
                    <button type="button" class="btn repeater_button" data-repeater="{{ $content }}" data-block="{{ $_block->id }}" data-page="{{ $_block->getPageId() }}">
                        {{ 'Add ' . ($itemName ?: 'Another Repeater Block') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>