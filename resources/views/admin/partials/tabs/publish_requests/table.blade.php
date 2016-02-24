<?php AssetBuilder::setStatus('cms-versions', true); ?>

@if (!empty($pagination))

    {!! $pagination !!}

@endif

@if (is_string($requests))

    <p>{{ $requests }}</p>

@else

    <div class="table-responsive">
        <table class="table table-striped">

            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                @if ($show['page'])
                    <th>Page</th>
                @endif
                <th>Note</th>
                @if ($show['requested_by'])
                    <th>Requested By</th>
                @endif
                @if ($show['status'])
                    <th>Status</th>
                @endif
                <th>Actions</th>
            </tr>
            </thead>

            <tbody>
            @foreach($requests as $request)
                <tr>
                    <td>{!! $request->page_version->version_id !!}</td>
                    <td>{!! $request->page_version->label !!}</td>
                    <?php $page_name = PageBuilder::page_full_name($request->page_version->page_id); ?>
                    @if ($show['page'])
                        <td>{!! $page_name !!}</td>
                    @endif
                    <td>{!! $request->note !!}</td>
                    @if ($show['requested_by'])
                        <td>{!! $request->user?$request->user->email:'Undefined' !!}</td>
                    @endif
                    @if ($show['status'])
                        <td>{!! $request->status !!}</td>
                    @endif
                    <td>
                        <a href="{{ PageBuilder::page_url($request->page_version->page_id).'?preview='.$request->page_version->preview_key }}"
                           target="_blank"><i class="glyphicon glyphicon-eye-open itemTooltip" title="Preview"></i></a>
                        <a href="{{ URL::to(config('coaster::admin.url').'/pages/edit/'.$request->page_version->page_id.'/'.$request->page_version->version_id) }}"><i
                                    class="delete glyphicon glyphicon-pencil itemTooltip" title="Edit"></i></a>
                        @if ($request->status == 'awaiting' && Auth::action('pages.version-publish', ['page_id' => $request->page_version->page_id]))
                            <i class="request_publish_action glyphicon glyphicon-ok-circle itemTooltip"
                               data-page="{{ $request->page_version->page_id }}"
                               data-version_id="{{ $request->page_version->version_id }}" data-name="{{ $page_name }}"
                               data-request="{{ $request->id }}" data-action="approved" title="Approve & Publish"></i>
                            <i class="request_publish_action glyphicon glyphicon-remove-circle itemTooltip"
                               data-page="{{ $request->page_version->page_id }}" data-name="{{ $page_name }}"
                               data-request="{{ $request->id }}" data-action="denied" title="Deny"></i>
                        @elseif ($request->status == 'awaiting' && Auth::user()->id == $request->user_id)
                            <i class="request_publish_action glyphicon glyphicon-remove-circle itemTooltip"
                               data-page="{{ $request->page_version->page_id }}" data-name="{{ $page_name }}"
                               data-request="{{ $request->id }}" data-action="cancelled" title="Cancel"></i>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>

        </table>
    </div>

@endif