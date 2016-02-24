@if ($level > 1)
    <ol>
        @else
            <ol id='sortablePages' class='sortable'>
                @endif
                {!! $pages_li !!}
            </ol>
