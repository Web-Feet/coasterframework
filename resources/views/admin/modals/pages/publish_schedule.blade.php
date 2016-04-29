<div id="versionPublishScheduleModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Schedule Version Publish:</h3>
            </div>
            <div class="modal-body">
                <div class="form-group version_info">
                    {!! Form::label('request_version', 'Version:', ['class' => 'control-label']) !!}
                    <span class="version" style="padding-top:5px;display:block;"></span>
                </div>
                <div class="form-group">
                    {!! Form::label('version_schedule_from', 'Date From:', ['class' => 'control-label']) !!}
                    {!! Form::text('version_schedule_from', '', ['class' => 'form-control datetimepicker']) !!}
                </div>
                <div class="form-group">
                    <label for="version_schedule_to" class="control-label"> DateTo (Optional, if set will revert to version #<span class="live_version_id">{{ $live_version }}</span>):</label>
                    {!! Form::text('version_schedule_to', '', ['class' => 'form-control datetimepicker', 'id' => 'version_schedule_to']) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('version_schedule_repeat', 'Repeat (Optional):', ['class' => 'control-label']) !!}
                    {!! Form::select('version_schedule_repeat', $intervals, '', ['class' => 'form-control']) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn cancel" data-dismiss="modal"><i class="fa fa-times"></i> &nbsp; Cancel</button>
                <button class="btn btn-primary schedule" data-dismiss="modal"><i class="fa fa-clock-o"></i>
                    &nbsp; Schedule
                </button>
            </div>
        </div>
    </div>
</div>
