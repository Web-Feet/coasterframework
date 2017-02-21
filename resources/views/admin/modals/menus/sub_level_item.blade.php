<div id="subLevelMIModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Menu Item Subpage Levels:</h3>
            </div>
            <div class="modal-body form-horizontal">
                <p>The number of subpage levels to show:</p>
                <br/>
                <div class="form-group">
                    <div class="col-sm-3">
                        {!! Form::label('sublevels', 'Levels:', ['class' => 'control-label']) !!}
                    </div>
                    <div class="col-sm-9">
                        {!! Form::select('sublevels', [0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], 1, ['class' => 'form-control']) !!}
                    </div>
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
