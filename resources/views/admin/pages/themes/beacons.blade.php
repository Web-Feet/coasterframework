<div class="row textbox">
    <div class="col-sm-6">
        <h1>Beacon List</h1>
    </div>
    <div class="col-sm-6 text-right">
        <button class="btn btn-warning addButton"><i class="fa fa-plus"></i> &nbsp; Import All</button>
    </div>
</div>

<p>Coaster uses the <a href="http://kontakt.io" target="_blank">Kontakt.io</a> API to integrate with their beacon technology, for more info click <a href="http://coaster.web-feet.co.uk/beacons">here</a>.</p>

<table class="table table-bordered" id="beacons">

    <thead>
    <tr>
        <th>ID (Alias)</th>
        <th>UUID</th>
        <th>Type</th>
        <th>Url/Page Name</th>
        <th></th>
    </tr>
    </thead>

    <tbody>
    {!! $rows !!}
    </tbody>

</table>

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('.addButton').click(function () {
                $.ajax({
                    url: '{!! URL::Current() !!}',
                    type: 'POST',
                    data: {add: true},
                    success: function (r) {
                        $('#beacons tbody').html(r);
                    }
                });
            });
            $('.glyphicon-remove').click(function () {
                var button = $(this);
                $.ajax({
                    url: '{!! URL::Current() !!}',
                    type: 'POST',
                    data: {delete_id: $(this).data('id')},
                    success: function () {
                        button.parent().parent().remove();
                    }
                });
            });
        });
    </script>
@endsection
