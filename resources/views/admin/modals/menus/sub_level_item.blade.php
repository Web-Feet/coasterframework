<div id="subLevelMIModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h3>Menu Item Sub-page Levels:</h3>
            </div>
            <div class="modal-body form-horizontal">
                <p>The '<span class="page-name"></span>' page currently has <span class="page-levels"></span> levels of sub-pages.</p>
                <p>Set the maximum number of sub-page levels to show (this menu is limited to <span class="menu-max-levels"></span> max):</p>
                <br/>
                <div class="form-group">
                    <div class="col-sm-3">
                        {!! Form::label('sublevels', 'Shown Levels:', ['class' => 'control-label']) !!}
                    </div>
                    <div class="col-sm-9">
                        {!! Form::select('sublevels', range(0,9), 1, ['class' => 'form-control']) !!}
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
