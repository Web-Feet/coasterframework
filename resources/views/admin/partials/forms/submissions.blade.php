<tr>
    <td>{!! $submission->numb !!}</td>
    <td>
        @foreach ($submission->content as $field => $value)
            <p><b>{!! ucfirst($field) !!}</b>: {!! $value !!}</p>
        @endforeach
    </td>
    <td>{!! !empty($submission->sent)?'<span class="glyphicon glyphicon-ok"></span>':'<span class="glyphicon glyphicon-remove"></span>' !!}</td>
    <td>{!! $submission->created_at !!}</td>
    <td>{!! $submission->from_page !!}</td>
</tr>