<div id="requestPublishModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Request Publish:</h3>
            </div>
            <div class="modal-body">
                <div class="form-group version_info">
                    {!! Form::label('request_version', 'Version:', ['class' => 'control-label']) !!}
                    <span class="version" style="padding-top:5px;display:block;"></span>
                </div>
                <div class="form-group">
                    {!! Form::label('request_note', 'Note:', ['class' => 'control-label']) !!}
                    {!! Form::textarea('request_note', '', ['class' => 'form-control', 'id' => 'request_note', 'rows' => 3]) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary make_request" data-dismiss="modal"><i class="fa fa-paper-plane"></i>
                    &nbsp; Make Request
                </button>
            </div>
        </div>
    </div>
</div>
