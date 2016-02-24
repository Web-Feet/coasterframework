<li>
    <div>
        <span style="min-width:300px;display: inline-block;">{!! $page_lang->name !!}:</span>
        @foreach($actions as $action => $value)
            {!! Form::checkbox('page['.$page_lang->page_id.']['.$action.']',1,$value,['style' => 'margin-bottom:5px;margin-left:40px;', 'class' => 'page-'.$action]).' ' . str_replace(['index', 'version-publish'], ['list', 'publish'], $action) .' ' !!}
        @endforeach
    </div>
    {!! $sub_pages !!}
</li>