<div class="row installThumbs">
    @foreach($thumbs as $thumb)
    <div class="col-sm-6 col-md-3" id="theme{{ $thumb->name }}">
        <div class="thumbnail">
            <img src="{{ $thumb->image }}" class="img-responsive" alt="{{ $thumb->name }}">
            <div class="caption">
                <p>
                    <span class="label label-success {{ !isset($thumb->active)?' hidden':'' }} activeSwitch">Active</span>
                    @if (!isset($thumb->install))<span class="label label-default">Installed</span>
                    @else<span class="label label-danger">Not Installed</span>@endif
                </p>
                <h3>{{ $thumb->name }}</h3>
                <p>
                    <button data-theme="{{ $thumb->name }}" class="btn btn-default activateTheme activeSwitch {{ (isset($thumb->active)||isset($thumb->install))?' hidden':'' }}">Activate</button>
                @if (isset($thumb->install))
                    <button data-theme="{{ $thumb->name }}" class="btn btn-default installTheme">Install</button>
                @endif
                @if (isset($thumb->delete))
                    <button data-theme="{{ $thumb->name }}" class="btn btn-default deleteTheme">Delete</button>
                @endif
                </p>
            </div>
        </div>
    </div>
    @endforeach
</div>