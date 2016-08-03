<h4>Groups</h4>

<div class="form-group">
    {!! Form::label('page_groups', 'In Group', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        @foreach($groups as $group)
            <div class="form-inline">
                <label>
                    {!! Form::checkbox('page_groups['.$group->id.']', 1, in_array($group->id, $page->groupIds()), ['class' => 'form-control']) !!}
                    &nbsp; {!! $group->name !!}
                </label>
            </div>
        @endforeach
    </div>
</div>