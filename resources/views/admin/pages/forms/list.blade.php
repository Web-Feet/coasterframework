<h1>Forms Submissions for {!! $page_name !!}</h1>

<ul>
    @foreach ($forms as $form)
        <li>
            <a href="{{ route('coaster.admin.forms.submissions', ['pageId' => $page_id, 'blockId' => $form->id]) }}/">{!! $form->label !!}</a>
        </li>
    @endforeach
</ul>