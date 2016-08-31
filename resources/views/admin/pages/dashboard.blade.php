<h1>Dashboard</h1>
<br/>

<div class="row">
    <div class="col-md-8">
        <div class="well well-home">
            <div class="row">
                <div class="col-md-7">
                    <h2>Hi <strong>{{ Auth::user()->getName() }}!</strong></h2>
                    <p>Welcome {{ $firstTimer?'':'back ' }}to the Coaster CMS control panel.</p>
                    <p>Click on the pages menu item to start editing page specific content, or for content on more than one page go to site-wide content.</p>
                </div>
                <div class="col-md-5 text-center">
                    <a href="{{ route('coaster.admin.account') }}" class="btn btn-default" style="margin-top:30px;">
                        <i class="fa fa-lock"></i>  &nbsp; Account settings
                    </a>
                    <a href="{{ config('coaster::admin.help_link') }}" class="btn btn-default" style="margin-top:30px;">
                        <i class="fa fa-life-ring"></i>  &nbsp; Help Docs
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="well well-home">
            <h3><i class="fa fa-info-circle" aria-hidden="true"></i> Version Details</h3>
            <ul>
                <li><strong>Your site:</strong> {{ $upgrade->from }}</li>
                <li><strong>Latest version:</strong> {{ $upgrade->to }}</li>
            </ul>
            @if($upgrade->allowed && $upgrade->required)
            <p><a class="btn btn-primary" href="{{ route('coaster.admin.system.upgrade') }}">(upgrade)</a></p>
            @endif
            @if ($canViewSettings)
            <p><a href="{{ route('coaster.admin.system') }}" class="btn btn-default"><i class="fa fa-cog"></i> View all settings</a></p>
            @endif
        </div>
    </div>
</div>

@if ($any_requests)
    <div class="row">
        <div class="col-md-12">
            <div class="well well-home">
                <h3><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Publish Requests To Moderate</h3>
                {!! $requests !!}
                <p><a class="btn btn-default" href="{{ route('coaster.admin.home.requests') }}">View all requests</a></p>
            </div>
        </div>
    </div>
@endif

@if ($any_user_requests)
    <div class="row">
        <div class="col-md-12">
            <div class="well well-home">
                <h3><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Your Pending Publish Requests</h3>
                {!! $user_requests !!}
                <p><a class="btn btn-default" href="{{ route('coaster.admin.home.your-requests') }}">View all your requests</a></p>
            </div>
        </div>
    </div>
@endif

<div class="row">
    @if ($searchLogNumber)
    <div class="col-md-6">
        <div class="well well-home">
            <h3><i class="fa fa-search" aria-hidden="true"></i> Search data {{ $searchLogNumber?' (top '.$searchLogNumber.')':'' }}</h3>
            {!! preg_replace('/<h1.*>(.*)<\/h1>/', '', $searchLogs) !!}
            <p><a class="btn btn-default" href="{{ route('coaster.admin.search') }}">View all search logs</a></p>
        </div>
    </div>
    @endif
    <div class="col-md-{{ $searchLogNumber ?'6':'12' }}">
        <div class="well well-home well-blog">
            <h3><i class="fa fa-rss" aria-hidden="true"></i> Latest from the Coaster Cms blog</h3>
            @if (!$coasterPosts->isEmpty())
                @foreach($coasterPosts as $coasterPost)
                    <h4><a href="{{ $coasterPost->link }}" target="_blank">{{ $coasterPost->title->rendered }}</a></h4>
                    <p>{{ CoasterCms\Helpers\Cms\StringHelper::cutString(strip_tags($coasterPost->content->rendered), $searchLogNumber?200:400) }}</p>
                @endforeach
            @else
                <p>Error connecting to blog.</p>
            @endif
            <p><a class="btn btn-default" href="{{ $coasterBlog }}" target="_blank">Visit our blog</a></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="well well-home">
            <h3><i class="fa fa-pencil" aria-hidden="true"></i> Site Updates</h3>
            {!! $logs !!}
            <p><a class="btn btn-default" href="{{ route('coaster.admin.home.logs') }}">View all admin logs</a></p>
        </div>
    </div>
</div>

