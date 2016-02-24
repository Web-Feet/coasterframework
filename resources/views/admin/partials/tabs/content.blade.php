@foreach ($tabs as $index => $content)

    <div class="tab-pane" id="tab{!! $index !!}">

        <br/><br/>

        {!! $content !!}

        @if ($index >= 0)

            @if ($button == 'add')
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp;
                            Add {{ $item }}</button>
                    </div>
                </div>
            @elseif ($button == 'publish')
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp;
                            Save {{ $item }}</button>
                        &nbsp;
                        @if ($can_publish)
                            <button class="btn btn-primary" name="publish" type="submit" value="publish"><i class="fa fa-floppy-o"></i>
                                &nbsp; Save & Publish {{ $item }}</button>
                        @else
                            <button class="btn btn-primary request_publish"><i class="fa fa-paper-plane"></i> &nbsp;
                                Save & Request Publish {{ $item }}</button>
                        @endif
                    </div>
                </div>
            @elseif ($button == 'edit')
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" name="publish" type="submit"><i class="fa fa-floppy-o"></i>
                            &nbsp; Update {{ $item }}</button>
                    </div>
                </div>
            @endif

        @endif

    </div>

@endforeach