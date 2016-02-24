<h1>Galleries for {!! $page_name !!}</h1>

<ul>
    @foreach ($galleries as $gallery)
        <li>
            <a href='{!! URL::to(config('coaster::admin.url')) !!}/gallery/edit/{!! $page_id !!}/{!! $gallery->id !!}'>{!! $gallery->label !!}</a>
        </li>
    @endforeach
</ul>