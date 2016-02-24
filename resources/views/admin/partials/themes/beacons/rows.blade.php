@foreach($beaconsData as $beacon)
    <tr>
        <td>{{ $beacon->device->uniqueId }} ({{ $beacon->device->alias }})</td>
        <td>{{ $beacon->device->id }}</td>
        <td>{{ strtolower($beacon->device->deviceType)=='beacon'?$beacon->device->profiles[0]:$beacon->device->deviceType }}</td>
        <td>
            @if ($beacon->page_name)
                {{ $beacon->page_name }} (Page ID: {{ $beacon->page_id }})
            @elseif ($beacon->url)
                {{ $beacon->url }}
            @else
                N/A
            @endif
        </td>
        <td><i class="glyphicon glyphicon-remove" data-id="{{ $beacon->device->uniqueId }}"></i></td>
    </tr>
@endforeach