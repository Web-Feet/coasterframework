<div class="form-group">
    {!! Form::label('page_info[menus]', 'Show in Menus', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        @foreach($page_details->menus as $menu)
            <div class="form-inline">
                <label>
                    <?php $options = []; if ($page_details->disabled):$options['disabled'] = true;endif; ?>
                    {!! Form::checkbox('page_info[menus]['.$menu->id.']', 1, $menu->in_menu, ['class' => 'form-control'] + $options) !!}
                    &nbsp; {!! $menu->label !!}
                </label>
            </div>
        @endforeach
    </div>
</div>