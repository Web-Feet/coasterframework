<h4>Groups</h4>

<div class="form-group" id="groupContainer">
    {!! Form::label('page_groups', 'Top Level Group Page', ['class' => 'control-label col-xs-6 col-sm-2']) !!}
    <div class="col-sm-2 col-xs-6">
        <label class="radio-inline">
            {!! Form::radio('page_info_other[group_radio]', 1, $page->group_container ? 1 : 0) !!} Yes
        </label>
        <label class="radio-inline">
            {!! Form::radio('page_info_other[group_radio]', 0, $page->group_container ? 0 : 1) !!} No
        </label>
    </div>
    <div class="col-sm-7 col-xs-8 group-container-options">
        <select name="page_info[group_container]" title="Group Container" class="form-control">
            <option value="-1">-- New Group --</option>
            <option value="0">-- Not Top Level Group Page --</option>
            @foreach($groups as $groupPage)
                <option value="{{ $groupPage->id }}" {{ $page->group_container == $groupPage->id ? 'selected="selected"' : '' }}>{{ $groupPage->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-1 col-xs-4 group-container-options header_note" data-note="The url priority for canonicals. Group pages will use the path from the top level group page with the highest priority by default.">
        {!! Form::text('page_info[group_container_url_priority]', $page->group_container_url_priority ?: 0, ['class' => 'form-control form-inline', 'placeholder' => 50])  !!}
    </div>
</div>

@if (!$groups->isEmpty())
<div class="form-group" id="inGroup">
    {!! Form::label('page_groups', 'In Group', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        @foreach($groups as $group)
            <label class="checkbox-inline">
                {!! Form::checkbox('page_groups['.$group->id.']', 1, in_array($group->id, $page->groupIds())) !!} &nbsp; {!! $group->name !!}
            </label>
        @endforeach
    </div>
</div>
@endif
