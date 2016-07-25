<div class="table-responsive">
    <table class="table table-striped">

        <thead>
        <tr>
            <th>{!! $item_name !!}</th>
            @foreach ($blocks as $block)
                <th>
                    {!! $block->label !!}
                </th>
            @endforeach
            <th>Actions</th>
        </tr>
        </thead>

        <tbody>
            {!! $rows !!}
        </tbody>

    </table>
</div>