<div class="row">
    <h4 class="col-sm-2 text-right">{{ $label }}</h4>

    <div class="col-sm-12 {!! $content?'':' hide' !!}">
        <table id="repeater_{!! $input_id[0] !!}" class="table table-bordered repeater-table">
            <tbody>
            {!! $content !!}
            </tbody>
        </table>
    </div>

    <div class="col-sm-10">
        <div class="form-group">
            <div class="row">
                <div class="col-sm-12">
                    {!! Form::hidden('repeater_id['.$input_id[0].'][page_id]', $page_id) !!}
                    {!! Form::hidden('repeater_id['.$input_id[0].'][block_id]', $block_id) !!}
                    {!! Form::hidden('repeater_id['.$input_id[0].'][parent_repeater]', $parent_repeater) !!}
                    <button type="button" class="btn repeater_button" data-repeater="{{ $input_id[0] }}"
                            data-block="{{ $block_id }}" data-page="{{ $page_id }}">Add Another Repeater Block
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>