<?php AssetBuilder::setStatus('cms-editor', true); ?>

<h1>{!! $name !!}</h1>

<div class="textbox">
    <div class="col-sm-12">
        @if (!empty($page))
            <p><a href="{!! URL::to(config('coaster::admin.url').'/pages/edit/'.$page->id) !!}">&raquo; Return and edit
                    settings/other content on the '{!! $page->name !!}' page</a></p>
        @elseif (empty($page))
            <p><a href="{!! URL::to(config('coaster::admin.url').'/blocks/') !!}">&raquo; Return and edit other
                    site-wide content and settings</a></p>
        @endif
        <p>&nbsp;</p>
    </div>
</div>

<form id="fileupload" action="#" method="POST" enctype="multipart/form-data">
    <!-- Redirect browsers with JavaScript disabled to the origin page -->
    <noscript><input type="hidden" name="redirect" value="http://blueimp.github.io/jQuery-File-Upload/"></noscript>
    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
    <div class="row textbox fileupload-buttonbar">
        <div class="col-sm-7">
            <!-- The fileinput-button span is used to style the file input field as button -->
            <span class="btn btn-warning fileinput-nice">
                <i class="glyphicon glyphicon-plus"></i> &nbsp; Add files...
                <input type="file" name="files[]" multiple>
            </span> &nbsp;
            <button type="submit" class="btn btn-primary start">Start upload</button>
            <!-- The loading indicator is shown during file processing -->
            <span class="fileupload-loading"></span>
        </div>
        <!-- The global progress information -->
        <div class="col-sm-5 fileupload-progress fade">
            <!-- The global progress bar -->
            <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0"
                 aria-valuemax="100">
                <div class="bar" style="width:0%;"></div>
            </div>
            <!-- The extended global progress information -->
            <div class="progress-extended">&nbsp;</div>
        </div>
    </div>
    <!-- The table listing the files available for upload/download -->
    <div class="table-responsive">
        <table role="presentation" class="table table-gallery" id="gallery">
            <tbody class="files"></tbody>
        </table>
    </div>

</form>


<!-- The blueimp Gallery widget -->
<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
    <div class="slides"></div>
    <h3 class="title"></h3>
    <a class="prev">‹</a>
    <a class="next">›</a>
    <a class="close">×</a>
    <a class="play-pause"></a>
    <ol class="indicator"></ol>
</div>

@section('scripts')
        <!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-upload fade">
        <td class="col-sm-1">
            <i class="icon-move"></i>
        </td>
        <td class="col-sm-2">
            <span class="preview"></span>
        </td>
        <td class="col-sm-5">
            <p class="name">{%=file.name%}</p>
            <strong class="error text-danger"></strong>
        </td>
        <td class="col-sm-2">
            <p class="size">Processing...</p>
            <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
        </td>
        <td class="col-sm-2">
            {% if (!i && !o.options.autoUpload) { %}
            <button class="btn btn-primary start" disabled>
                Start
            </button>
            {% } %}
            {% if (!i) { %}
            <button class="btn btn-warning cancel">
                <span class="fa fa-times"></span> &nbsp; Cancel
            </button>
            {% } %}
        </td>
    </tr>
    {% } %}

</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
    {% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade" data-file="{%=file.name %}">
        <td class="col-sm-1">
            <i class="icon-move"></i>
        </td>
        <td class="col-sm-2">
            <span class="preview">
                {% if (file.thumbnailUrl) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                {% } %}
            </span>
        </td>
        <td class="col-sm-5">
            <div class="textbox">
                <p class="name">
                    {% if (file.url) { %}
                        <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
                    {% } else { %}
                        <span>{%=file.name%}</span>
                    {% } %}
                </p>
                <div class="input-group">
                    <div class="input-group-addon">Caption: </div>
                    <input type="text" class="form-control" value="{%=file.caption?file.caption:''%}" {!! !($can_edit_caption)?'disabled':null !!}>
                    <span class="input-group-btn">
                        <button class="btn btn-default" data-file="{%=file.name%}" onclick="javascript:updateCaption(this)" type="button" {!! !($can_edit_caption)?'disabled':null !!}>Update</button>
                    </span>
                </div>
                {% if (file.error) { %}
                <div><span class="label label-important">Error</span> {%=file.error%}</div>
                {% } %}
            </div>
        </td>
        <td class="col-sm-2">
            <span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
        <td class="col-sm-2">
    @if($can_delete)
    <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
        <span class="glyphicon glyphicon-trash"></span> &nbsp; Delete
    </button>
    @endif
    </td>
</tr>
{% } %}

</script>

<?php $assets_path = URL::to(config('coaster::admin.public').'/jquery/gallery-upload') ?>

        <!-- The Templates plugin is included to render the upload/download listings -->
<script src="{!! $assets_path !!}/js/external/tmpl.min.js"></script>
<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
<script src="{!! $assets_path !!}/js/external/load-image.all.min.js"></script>
<!-- The Canvas to Blob plugin is included for image resizing functionality -->
<script src="{!! $assets_path !!}/js/external/canvas-to-blob.min.js"></script>
<!-- blueimp Gallery script -->
<script src="{!! $assets_path !!}/js/external/jquery.blueimp-gallery.min.js"></script>
<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="{!! $assets_path !!}/js/jquery.iframe-transport.js"></script>
<!-- The basic File Upload plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload.js"></script>
<!-- The File Upload processing plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-process.js"></script>
<!-- The File Upload image preview & resize plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-image.js"></script>
<!-- The File Upload audio preview plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-audio.js"></script>
<!-- The File Upload video preview plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-video.js"></script>
<!-- The File Upload validation plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-validate.js"></script>
<!-- The File Upload user interface plugin -->
<script src="{!! $assets_path !!}/js/jquery.fileupload-ui.js"></script>
<!-- The main application script -->
<script src="{!! $assets_path !!}/js/main.js"></script>

<script type="text/javascript">
    function updateCaption(image_btn) {

        var file = $(image_btn).attr('data-file');
        var caption = $($(image_btn).parent().parent().children('input')[0]).val();

        $(image_btn).addClass('btn-warning disabled').html('Updating ...');

        $.ajax({
            url: window.location.href.replace('edit', 'caption'),
            type: 'POST',
            data: {file_data: file, caption: caption},
            success: function (r) {
                $(image_btn).removeClass('btn-warning').html('Saved');
                setTimeout(function () {
                    $(image_btn).removeClass('disabled');
                    $(image_btn).html('Update');
                }, 2000);
            }
        });

    }

    $(document).ready(function () {

        $('.fileupload').fileupload();
        $('.fileupload .removeImage').click(function () {
            $('#' + $(this).attr('data-name')).val('true');
        });

        var fixHelper = function (e, ui) {
            ui.children().each(function () {
                $(this).width($(this).width());
            });
            return ui;
        };

        $("#gallery tbody").sortable({
            handle: 'td:first',
            helper: fixHelper,
            items: 'tr',
            update: function () {
                var sort_arr = $(this).sortable("toArray", {attribute: 'data-file'});
                $.ajax({
                    url: window.location.href.replace('edit', 'sort'),
                    type: 'POST',
                    data: {arr: sort_arr}
                });
            }
        });

    });

</script>
@stop

@section('styles')
<!-- blueimp Gallery styles -->
<link rel="stylesheet" href="http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css">
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="{!! $assets_path !!}/css/jquery.fileupload-ui.css">
@stop