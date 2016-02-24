<div id="publish_requests_table">

    {!! $requests_table !!}

</div>

<a href="#" id="viewAllRequests" data-type="awaiting">View all requests</a>

<input type="hidden" name="request_note" id="request_note_input">
<input type="hidden" name="publish_request" id="publish_request">
<input type="hidden" name="overwriting_version_id" value="{{ $version_id }}">
