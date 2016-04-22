@foreach ($tabs as $index => $name)

    <li id="navtab{!! $index !!}"{!! $index<0?' class="pull-right"':'' !!}>
        <a href="{{ '#tab'.$index }}" data-toggle="tab" aria-expanded="true">{!! $name !!}</a>
    </li>

@endforeach