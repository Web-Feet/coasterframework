<div class="row installThumbs">
    @foreach($thumbs as $thumb)
        <div class="col-sm-6 col-md-3" id="theme{{ $thumb->name }}">
            <div class="thumbnail{{ $thumb->active?' activeTheme':'' }}">
                @if ($auth['manage'])
                    <i class="glyphicon glyphicon-remove deleteTheme activeSwitch {{ ($thumb->active)?' hidden':'' }}" data-theme="{{ $thumb->name }}" title="Delete"></i>
                @endif
                <div class="img-container">
                    <img src="{{ $thumb->image }}" class="img-responsive" alt="{{ $thumb->name }}">
                </div>
                <div class="caption">
                    <p>
                        <span class="label label-success {{ !$thumb->active?' hidden':'' }} activeSwitch">Active</span>
                        @if (!$thumb->install)<span class="label label-default">Installed</span>
                        @else<span class="label label-danger">Not Installed</span>@endif
                    </p>
                    <h3>{{ $thumb->name }}</h3>
                    <p>
                        @foreach($thumb->buttons as $button)
                            {!! $button !!}
                        @endforeach
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
