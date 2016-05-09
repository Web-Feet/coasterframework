<table class="table table-striped">
    <tr>
        <th>#</th>
        <th>Action</th>
        <th>User</th>
        <th>Date</th>
        <th></th>
    </tr>

    @foreach ($logs as $log)

        <tr>
            <td>{!! $log->id !!}</td>
            <td>{!! $log->log !!}</td>
            <td>{!! ($log->user)?$log->user->email:'Undefined' !!}</td>
            <td>{!! DateTimeHelper::display($log->created_at) !!}</td>
            <td>
                @if($log->backup && (((time()-strtotime($log->created_at)) < config('coaster::admin.undo_time') && $log->user_id == Auth::user()->id) || Auth::action('backups.restore')))
                    <a href="javascript:undo_log({!! $log->id !!})">Restore</a>
                @endif
            </td>
        </tr>

    @endforeach

</table>
