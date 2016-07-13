<h1>Themes</h1>
<br/>

<h2>Manage Themes</h2>

<p>View all uploaded themes. Can upload, install, activate and delete them here.</p>
<p>Can also update a themes blocks (this option may take a few seconds as it has to process all theme files):</p>

<br />

<div class="form-horizontal">
    <div class="form-inline">
        <a href="{{ route('coaster.admin.themes.list') }}" class="btn btn-warning"><i class="fa fa-tint"></i> &nbsp; Manage Themes</a>
    </div>
</div>

@if (!empty($blockSettings))

    <br />

    <h2>Block Settings</h2>

    @foreach($blockSettings as $name => $url)
        <p><a href="{{ $url }}">{{ $name }}</a></p>
    @endforeach

@endif
