{!! Form::open() !!}
<div class="panel">
  <div class="panel-heading">
    <h1>Import from Wordpress to a blog</h1>
    <p>This requires the Wordpress REST Api plugin to be installed on you Wordpress blog.</p>
    <a href="https://wordpress.org/plugins/rest-api/" title="rest PAI plugin for wordpress">Find out more</a>
  </div>
</div>
<div class="panel-body">
  @if ( ! empty($result))
    <pre>
      @php
        var_dump($result);
      @endphp
    </pre>
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
