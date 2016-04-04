<div class="row installThumbs">
    @foreach($thumbs as $thumb)
        <div class="col-sm-6 col-md-3" id="theme{{ $thumb->name }}">
            <div class="thumbnail{{ isset($thumb->active)?' activeTheme':'' }}">
                <i class="glyphicon glyphicon-remove deleteTheme activeSwitch {{ (isset($thumb->active))?' hidden':'' }}" data-theme="{{ $thumb->name }}" title="Delete"></i>
                <img src="{{ $thumb->image }}" class="img-responsive" alt="{{ $thumb->name }}">
                <div class="caption">
                    <p>
                        <span class="label label-success {{ !isset($thumb->active)?' hidden':'' }} activeSwitch">Active</span>
                        @if (!isset($thumb->install))<span class="label label-default">Installed</span>
                        @else<span class="label label-danger">Not Installed</span>@endif
                    </p>
                    <h3>{{ $thumb->name }}</h3>
                    <p>
                        <button data-theme="{{ $thumb->name }}" class="btn btn-default activateTheme activeSwitch {{ (isset($thumb->active)||isset($thumb->install))?' hidden':'' }}"><span class="glyphicon glyphicon-ok"></span> Activate</button>
                        @if (isset($thumb->install))
                            <button data-theme="{{ $thumb->name }}" class="btn btn-default installTheme"><span class="glyphicon glyphicon-cog"></span> Install</button>
                        @else
                            <button data-theme="{{ $thumb->name }}" data-theme-id="{{ $thumb->id }}" class="btn btn-default exportTheme"><span class="glyphicon glyphicon-download"></span> Export</button>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>