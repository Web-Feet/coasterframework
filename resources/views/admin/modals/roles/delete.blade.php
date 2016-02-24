<div id="deleteModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Delete Role: <span class="roleName"></span></h3>
            </div>
            <div class="modal-body form-horizontal">
                <p>User's who currently have this role will need to be assigned another role.</p>
                <br/>
                <div class="form-group">
                    <div class="col-sm-3">
                        {!! Form::label('new_role', 'New Role:', ['class' => 'control-label']) !!}
                    </div>
                    <div class="col-sm-9 new_role_container">
                        {!! Form::select('new_role', $roles, null, ['class' => 'form-control']) !!}
                        <span id="new_role_help" class="help-block"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary delete"><i class="fa fa-trash"></i> &nbsp; Delete</button>
            </div>
        </div>
    </div>
</div>
