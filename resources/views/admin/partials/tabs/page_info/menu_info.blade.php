<div class="form-group">
    {!! Form::label('page_info_other[menus]', 'Show in Menus', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        @foreach($menus as $menu)
            <div class="form-inline">
                <label>
                    <?php $options = []; if ($disabled):$options['disabled'] = true; endif; ?>
                    {!! Form::checkbox('page_info_other[menus]['.$menu->id.']', 1, $menu->in_menu, ['class' => 'form-control'] + $options) !!}
                    &nbsp; {!! $menu->label !!}
                </label>
            </div>
        @endforeach
    </div>
</div>