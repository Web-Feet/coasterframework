<div id="addMIModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Add Menu Item:</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('menu_item', 'Pages:', ['class' => 'control-label']) !!}
                    {!! Form::select('menu_item', $options, null, ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary add" data-dismiss="modal"><i class="fa fa-plus"></i> &nbsp; Add</button>
            </div>
        </div>
    </div>
</div>

