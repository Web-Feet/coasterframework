<h1>Forms Submissions for {!! $page_name !!}</h1>

<ul>
    @foreach ($forms as $form)
        <li>
            <a href="{!! URL::to(config('coaster::admin.url')) !!}/forms/submissions/{!! $page_id !!}/{!! $form->id !!}/">{!! $form->label !!}</a>
        </li>
    @endforeach
</ul>