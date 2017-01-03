<div class="row textbox">
    <div class="col-sm-6">
        <h1>Beacon List</h1>
    </div>
    <div class="col-sm-6 text-right">
        <button class="btn btn-warning addButton"><i class="fa fa-plus"></i> &nbsp; Import All</button>
    </div>
</div>

<p>Coaster uses the <a href="http://kontakt.io" target="_blank">Kontakt.io</a> API to integrate with their beacon technology, for more info click <a href="http://www.coastercms.org/beacons" target="_blank">here</a>.</p>

@if ($rows)
    <p>EDDYSTONE beacon URLs can be updated form the "Page Info" tab when editing a page.</p>

    @if (!$bitly)
        <p class="text-danger">The bit.ly API is also required to update the beacons URLs, please add a valid <a href="https://bitly.com/a/oauth_apps" target="_blank">Access Token</a> in the system settings.</p>
    @else
        <p class="text-success">The bit.ly API will be used to update beacons with short URLs.</p>
    @endif

@else
    <p class="text-danger">No beacons found, run the import by clicking the button above. A valid <a href="https://support.kontakt.io/hc/en-gb/articles/201628731-How-do-I-find-my-developer-API-Key-" target="_blank">Kontakt API Key</a> will be required in the system settings.</p>
@endif

<p>&nbsp;</p>

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

<p class="text-danger">* sync with physical device required<p>

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            $('.addButton').click(function () {
                $.ajax({
                    url: route('coaster.admin.themes.beacons.post'),
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
                    url: route('coaster.admin.themes.beacons.post'),
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
