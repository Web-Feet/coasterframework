<h1>Galleries for {!! $page_name !!}</h1>

<ul>
    @foreach ($galleries as $gallery)
        <li>
            <a href='{{ route('coaster.admin.gallery.edit', ['pageId' => $page_id, 'blockId' => $gallery->id]) }}'>{!! $gallery->label !!}</a>
        </li>
    @endforeach
</ul>