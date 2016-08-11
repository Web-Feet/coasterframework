<h4>Groups</h4>

<div class="form-group" id="groupContainer">
    {!! Form::label('page_groups', 'Group Page For', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div class="input-group">
            <select name="page_info[group_container]" class="form-control">
                <option value="0">-- Not Group Page --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ $page->group_container == $group->id ? 'selected="selected"' : '' }}>{{ $group->name }}</option>
                @endforeach
            </select>
            <span class="input-group-btn">
                <a href="#" class="btn btn-default">or create a thing</a>
            </span>
        </div>
    </div>
</div>

<div class="form-group" id="inGroup">
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