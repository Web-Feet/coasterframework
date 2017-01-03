<h1>Theme Editor ({!! $theme->theme !!})</h1>
<p>If you are familiar with HTML and CSS, then the theme editor will be perfect for you to edit your website theme.</p>
<p>NB: If you add a block here then you will need to got to Themes >> Manage Themes >> Review Blocks in order to add to the database.</p>
<div class="col-md-4 file theme_file_tree_sidebar">
  <div class="tabtable">
    <ul class="nav nav-tabs">
      <li class="active">
        <a href="#tab0">Views</a>
      </li>
      <li>
        <a href="#tab1">CSS</a>
      </li>
    </ul>
  </div>
  <div class="tab-content theme_file_tree">
    <div id="tab0" class="tab-pane active">
      {!! $filetree !!}
    </div>
    <div id="tab1" class="tab-pane">
      {!! $css_filetree !!}
    </div>
  </div>
</div>
<div class="col-md-8 content">
  {!! Form::open() !!}
    {!! Form::hidden('theme_id', $theme->id) !!}
    {!! Form::hidden('path', '', array('id' => 'path-inp')) !!}
    {!! Form::textarea('file', '', array('class' => 'hidden', 'id' => 'file_content_ta')) !!}
    <pre id="editor"></pre>
    {!! Form::submit('Save file', ['class' => 'btn btn-promary']) !!}
  {!! Form::close() !!}
  <a href="{{ route('coaster.admin.themes.list') }}" class="btn btn-default pull-right"><span class="fa fa-chevron-left"></span>&nbsp;back to themes</a>
</div>



@section('scripts')
    <script type='text/javascript'>
        $(document).ready(function () {
          $('.tabtable a').click(function (e) {
            e.preventDefault();
            $(this).tab('show')
          });
          var rte = route('coaster.admin.themes.edit.loadfile');
          var loadLinks = $('.load-template-file-link');

          var folderLinks = $('.tree-folder-link');

          // Init editor
          var ta = $('#file_content_ta');
          var pathInp = $('#path-inp');
          var editobj = $('#editor');
          editobj.hide();
          var resizeEditor = function()
          {
            editobj.css({'height': $(window).height() - 280});
          };
          resizeEditor();
          $(window).resize(resizeEditor);

          var editor = ace.edit("editor");
          editor.setTheme("ace/theme/pastel_on_dark");
          editor.session.setMode("ace/mode/html_blade");
          editor.setOptions({
            enableBasicAutocompletion: true
          });

          // Init link clicking
          folderLinks.bind('click', function(e)
          {
            e.preventDefault();
            var flderLnk = $(this);
            var ulFiles = flderLnk.siblings('ul');
            ulFiles.toggle();
          });
          folderLinks.trigger('click');
          loadLinks.bind('click', function(e)
          {
            e.preventDefault();
            var linkEl = $(this);
            loadLinks.removeClass('selected');
            linkEl.addClass('selected');

            // Load file to edit
            $.ajax({
              url: rte,
              type: 'POST',
              dataType: 'JSON',
              data: 'template=' + linkEl.attr('data-path'),
              success: function(r)
              {
                editobj.show();
                pathInp.val(r.path);
                var pAr = r.path.split('.');
                var fileExt = pAr[pAr.length - 1];
                if (fileExt === 'css')
                {
                  editor.session.setMode("ace/mode/css");
                }
                else {
                  editor.session.setMode("ace/mode/html_blade");
                }
                ta[0].innerHTML = String(r.file);
                editor.setValue(r.file, 1);

                editor.on('input', function(){
                  ta.html(editor.getValue());
                });
              }
            });
          });

          // Monitor form for submit
          var frm = $('.content form');
          frm.bind('submit', function(e)
          {
            e.preventDefault();
            $.ajax({
              url: frm.attr('action'),
              type: 'POST',
              dataType: 'JSON',
              data: frm.serialize(),
              success: function(r)
              {
                if (r.success)
                {
                  cms_alert('success', 'File saved!');
                }
                else {
                  cms_alert('danger', 'Error saving!');
                }
              }
            });

          });

        });
    </script>
@append
