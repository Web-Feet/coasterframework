@foreach ($tabs as $index => $name)

    <li id="navtab{!! $index !!}"{!! $index<0?' class="pull-right"':'' !!}>{!! HTML::link('#tab'.$index, $name, ['data-toggle' => 'tab']) !!}</li>

@endforeach