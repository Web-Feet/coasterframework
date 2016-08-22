<div class="row installThumbs">
    @foreach($thumbs as $thumb)
        <div class="col-sm-6 col-md-3" id="theme{{ $thumb->name }}">
            <div class="thumbnail{{ isset($thumb->active)?' activeTheme':'' }}">
                @if ($auth['manage'])
                    <i class="glyphicon glyphicon-remove deleteTheme activeSwitch {{ (isset($thumb->active))?' hidden':'' }}" data-theme="{{ $thumb->name }}" title="Delete"></i>
                @endif
                <div class="img-container">
                    <img src="{{ $thumb->image }}" class="img-responsive" alt="{{ $thumb->name }}">
                </div>
                <div class="caption">
                    <p>
                        <span class="label label-success {{ !isset($thumb->active)?' hidden':'' }} activeSwitch">Active</span>
                        @if (!isset($thumb->install))<span class="label label-default">Installed</span>
                        @else<span class="label label-danger">Not Installed</span>@endif
                    </p>
                    <h3>{{ $thumb->name }}</h3>
                    <p>
                        @if (isset($thumb->install))
                            @if ($auth['manage'])
                                <button data-theme="{{ $thumb->name }}" class="btn btn-default installTheme"><span class="glyphicon glyphicon-cog"></span> Install</button>
                            @endif
                        @else
                            @if ($auth['manage'])
                                <button data-theme="{{ $thumb->name }}" class="btn btn-default activateTheme activeSwitch {{ (isset($thumb->active)||isset($thumb->install))?' hidden':'' }}"><span class="glyphicon glyphicon-ok"></span> Activate</button>
                            @endif
                            @if ($auth['edit'])
                                <a href="{{ route('coaster.admin.themes.edit', ['themeId' => $thumb->id]) }}" class="btn btn-default"><span class="glyphicon glyphicon-pencil"></span> Edit</a>
                            @endif
                            @if ($auth['export'])
                                <button data-theme="{{ $thumb->name }}" data-theme-id="{{ $thumb->id }}" class="btn btn-default exportTheme"><span class="glyphicon glyphicon-download"></span> Export</button>
                            @endif
                            @if ($auth['update'])
                                <a href="{{ route('coaster.admin.themes.update', ['themeId' => $thumb->id]) }}" class="btn btn-default"><span class="glyphicon glyphicon-flag"></span> Review Block Changes</a>
                            @endif
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
