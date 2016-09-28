@foreach ($tabs as $index => $content)

    <div class="tab-pane" id="tab{!! $index !!}">

        <br/><br/>

        {!! $content !!}

        @if ($index >= 0 && ((!empty($page) && !$page->link) || $can_publish))

            @if ($new_page)
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp;
                            Add Page</button>
                    </div>
                </div>
            @elseif (!$publishing)
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" name="publish" value="publish" type="submit"><i class="fa fa-floppy-o"></i>
                            &nbsp; Update Page</button>
                    </div>
                </div>
            @elseif ($publishing)
                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp;
                            Save Version</button>
                        &nbsp;
                        @if ($can_publish)
                            <button class="btn btn-primary" name="publish" type="submit" value="publish"><i class="fa fa-floppy-o"></i>
                                &nbsp; Save {{ $page->is_live() ? 'Version & Publish':' & Set As Version Ready To Go Live' }}</button>
                        @else
                            <button class="btn btn-primary request_publish"><i class="fa fa-paper-plane"></i> &nbsp;
                                Save & Request Publish Of Version</button>
                        @endif
                    </div>
                </div>
            @endif

        @endif

    </div>

@endforeach