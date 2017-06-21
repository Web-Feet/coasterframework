{!! Form::open() !!}
<div class="panel">
  <div class="panel-heading">
    <h1>Import from Wordpress to a blog</h1>
    <p>This requires the Wordpress REST Api plugin to be installed on your Wordpress blog.</p>
    <a href="https://wordpress.org/plugins/rest-api/" title="rest PAI plugin for wordpress">Find out more</a>
  </div>
</div>
<div class="panel-body">
  @if ( ! empty($result))
    <dl class="">
    @foreach ($result as $postData)
        <dt>{!! $postData->message !!}</dt>
        <dd>{!! $postData->oldLink !!} -> {!! $postData->newLink !!}</dd>
        <dd>Categories: {!! $postData->categories !!}</dd>
        <dd>Tags: {!! $postData->tags !!}</dd>
        <dd>Comments: {!! $postData->comments !!}</dd>
        <dd><img src="{!! $postData->main_image !!}" class="img-responsive" style="max-height:200px;" alt=""></dd>
    @endforeach
    </dl>
  @endif
  <div class="col-md-10">
    <div class="row">
      <label>
        Blog URL (eg. https://www.example.com/blog))
        {!! Form::input('text', 'blog_url', $url, ['class' => 'form-control'])!!}
      </label>
    </div>
    <div class="row">
      {!! Form::submit('Create and/or import blog posts', ['class' => 'btn btn-primary']) !!}
    </div>
  </div>

</div>
{!! Form::close() !!}
