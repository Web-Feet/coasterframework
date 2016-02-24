<div id="renameModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Rename Version:</h3>
            </div>
            <div class="modal-body">
                <p>You can change name of this version to something more memorable (leave blank to revert to the save
                    datetime).</p>
                <br/>
                <div class="form-group">
                    {!! Form::label('version_name', 'New name:', ['class' => 'control-label']) !!}
                    {!! Form::text('version_name', '', ['class' => 'form-control', 'id' => 'version_name']) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary" data-dismiss="modal"><i class="fa fa-check"></i> &nbsp; Change</button>
            </div>
        </div>
    </div>
</div>
