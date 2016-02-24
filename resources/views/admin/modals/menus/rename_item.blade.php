<div id="renameMIModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Set Custom Name:</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('custom_name', 'Custom Name:', ['class' => 'control-label']) !!}
                    {!! Form::text('custom_name', '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary change" data-dismiss="modal"><i class="fa fa-check"></i> &nbsp; Change
                </button>
            </div>
        </div>
    </div>
</div>
