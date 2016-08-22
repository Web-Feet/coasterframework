@if($theme->theme !== $directory->directory && ! empty($directory->directory))
<ul>
  <li>
      <a class="tree-folder-link" href="#" data-path="{{ $directory->path }}">{{ $directory->directory }}</a>
@endif
      @if( ! empty($directory->folders))
        <ul>
          @foreach($directory->folders AS $folder)
            {!! \View::make('coaster::partials.themes.filetree', ['directory' => $folder, 'theme' => $theme]) !!}
          @endforeach
        </ul>
      @endif
      @if( ! empty($directory->files))
        <ul>
          @foreach($directory->files AS $file)
            <li><a href="#" class="load-template-file-link" data-path="{{ $directory->path }}/{{ $file }}">{{ $file }}</a></li>
          @endforeach
        </ul>
      @endif
@if($theme->theme !== $directory->directory  && ! empty($directory->directory))
    </li>
</ul>
@endif
