<a href="{{ $href ?: 'javascript:void()' }}" data-theme="{{ $thumb->name }}" data-theme-id="{{ $thumb->id }}" class="btn btn-default {{ implode(' ', $classes) }}">
    <span class="glyphicon glyphicon-{{ $glyphicon }}"></span> {{ $label }}
</a>