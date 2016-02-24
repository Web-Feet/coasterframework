<div class="row textbox">
    <div class="col-sm-6">
        <h1>{!! $form !!} Submissions</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_export)
            <a href="{{ $export_link }}" class="btn btn-warning addButton" target="_blank"><i
                        class="fa fa-share-square-o"></i> &nbsp; Export</a>
        @endif
    </div>
</div>

{!! $links !!}

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>#</th>
            <th>Content</th>
            <th>Sent</th>
            <th>Date/Time</th>
            <th>Page</th>
        </tr>
        </thead>
        <tbody>
        {!! $submissions !!}
        </tbody>
    </table>
</div>