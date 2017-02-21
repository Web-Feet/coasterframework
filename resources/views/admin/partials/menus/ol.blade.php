<div class="menuLayout">

    <div class="row textbox">

        <h3 class="col-sm-6">{!! $menu->label !!}</h3>

        <div class="col-sm-6 text-right">
            <span class="hide btn btn-success disabled" id="menu_{!! $menu->id !!}_saved">Order Saved</span>
            <span class="hide btn btn-danger disabled" id="menu_{!! $menu->id !!}_failed">Sort Failed</span>
            @if ($permissions['can_add_item'])
                <button class="btn btn-warning" id="menu_{!! $menu->id !!}_add" onclick="add_item({!! $menu->id !!})"><i
                            class="fa fa-plus"></i> &nbsp; Add Menu Item
                </button>
            @endif
        </div>

    </div>

    <ol id="menu_{!! $menu->id !!}" class="sortable">
        {!! $renderedItems !!}
    </ol>

</div>