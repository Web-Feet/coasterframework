<div class="row">
    <div id="version_pagination" class="pull-left">
        {!! $pagination !!}
    </div>

    <div class="pull-right">
        <p>Published Version: #<span class="live_version_id">{{ $live_version }}</span></p>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped">

        <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Author</th>
            <th>Actions</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($versions as $version)
            <tr id="v_{{ $version->version_id }}">
                <td>{{ $version->version_id }}</td>
                <td>{{ $version->label }}</td>
                <td>
                    {{ ($version->user)?$version->user->email:'Undefined' }}
                </td>
                <td>
                    <a href="{{ PageBuilder::page_url($version->page_id).'?preview='.$version->preview_key }}"
                       target="_blank"><i class="glyphicon glyphicon-eye-open itemTooltip" title="Preview"></i></a>
                    <a href="{{ URL::to(config('coaster::admin.url').'/pages/edit/'.$version->page_id.'/'.$version->version_id) }}"><i
                                class="delete glyphicon glyphicon-pencil itemTooltip" title="Edit"></i></a>
                    @if ($can_publish || $version->user_id == Auth::user()->id)
                        <i class="version_rename glyphicon glyphicon-bookmark itemTooltip"
                           data-version="{{ $version->version_id }}" title="Rename"></i>
                    @endif
                    @if ($can_publish)
                        <i class="version_publish glyphicon glyphicon-ok-circle itemTooltip"
                           data-version="{{ $version->version_id }}" title="Publish"></i>
                    @else
                        <i class="request_publish glyphicon glyphicon-ok-circle itemTooltip"
                           data-version="{{ $version->version_id }}" title="Request Publish"></i>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
</div>