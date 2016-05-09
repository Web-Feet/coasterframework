<table class="table table-bordered">
    <tbody>
    <tr>
        <td>Role</td>
        <td>{!! $user->role->name !!}</td>
    </tr>
    <tr>
        <td>Login</td>
        <td>{!! $user->email !!}</td>
    </tr>
    <tr>
        <td>Password</td>
        <td>**********</td>
    </tr>
    <tr>
        <td>Date Created</td>
        <td>{!! DateTimeHelper::display($user->created_at, 'short') !!}</td>
    </tr>
    <tr>
        <td>Account Status</td>
        <td>{!! !empty($user->active)?'Active':'Disabled' !!}</td>
    </tr>
    </tbody>
</table>