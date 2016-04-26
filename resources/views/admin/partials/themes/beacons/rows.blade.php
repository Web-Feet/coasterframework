@foreach($beaconsData as $beacon)
    <tr>
        <td>{{ $beacon->device->uniqueId }}{{ $beacon->device->alias?' ('.$beacon->device->alias.')':'' }}</td>
        <td>{{ $beacon->device->id }}</td>
        <td>{{ strtolower($beacon->device->deviceType)=='beacon'?$beacon->device->profiles[0]:$beacon->device->deviceType }}</td>
        <td>
            @if ($beacon->page_name)
                <a class="{{ $beacon->device->pending?'text-danger':'' }}" href="{{ $beacon->url }}" target="_blank">{{ $beacon->page_name }} (Page ID: {{ $beacon->page_id }}){{ $beacon->device->pending?' *':'' }}</a>
            @elseif ($beacon->url)
                <a class="{{ $beacon->device->pending?'text-danger':'' }}" href="{{ $beacon->url }}" target="_blank">{{ $beacon->url }}{{ $beacon->device->pending?' *':'' }}</a>
            @else
                N/A
            @endif
        </td>
        <td><i class="glyphicon glyphicon-remove" data-id="{{ $beacon->device->uniqueId }}"></i></td>
    </tr>
@endforeach