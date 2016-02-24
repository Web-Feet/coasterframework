<?php AssetBuilder::setStatus('jquery-sortable', true); ?>

<h1>Menus</h1>

{!! $menus !!}

@section('scripts')
    <script type="text/javascript">

        var selected_menu;
        var selected_item;

        $('.editsub').click(function () {
            var max_sublevel = $(this).data('max-sublevel');
            $('#editMIModal option:gt(0)').hide();
            $('#editMIModal option:lt(' + (max_sublevel + 1) + ')').show();
            selected_item = $(this).closest('li').attr('id');
            $.ajax({
                url: '{!! URL::Current() !!}/get-levels',
                type: 'POST',
                data: {id: selected_item},
                success: function (r) {
                    if (r % 1 === 0) {
                        $('#editMIModal').modal('show');
                        $('#sublevels').val(r);
                    }
                    else {
                        cms_alert('error', 'Error receiving data');
                    }
                }
            });
        });

        $('#editMIModal .change').click(function () {
            var sl = $('#sublevels').val();
            $.ajax({
                url: '{!! URL::Current() !!}/save-levels',
                type: 'POST',
                data: {id: selected_item, sub_level: sl},
                success: function (r) {
                    if (r % 1 === 0) {
                        $('#' + selected_item + ' .sl_numb').html(sl);
                    }
                    else {
                        cms_alert('error', 'Error updating menu item');
                    }
                }
            });
        });

        $('#renameMIModal .change').click(function () {
            var custom_name = $('#custom_name').val();
            $.ajax({
                url: '{!! URL::Current() !!}/rename',
                type: 'POST',
                data: {id: selected_item, custom_name: custom_name},
                success: function (r) {
                    if (r % 1 === 0) {
                        if (custom_name != '') {
                            $('#' + selected_item + ' .custom-name').html("&nbsp;(Custom Name: " + custom_name + ")");
                        } else {
                            $('#' + selected_item + ' .custom-name').html("");
                        }
                    }
                    else {
                        cms_alert('error', 'Error updating menu item');
                    }
                }
            });
        });

        $('#addMIModal .add').click(function () {
            $.ajax({
                url: '{!! URL::Current() !!}/add',
                type: 'POST',
                data: {id: $('#menu_item').val(), menu: selected_menu},
                success: function (r) {
                    if (r % 1 === 0) {
                        location.reload();
                    }
                    else {
                        cms_alert('error', 'Error Adding Menu Item', 'The menu item was not added');
                    }
                }
            });
        });

        $('.rename').click(function () {
            selected_item = $(this).closest('li').attr('id');
            $('#renameMIModal').modal('show');
        });

        function add_item(menu) {
            selected_menu = menu;
            $('#addMIModal').modal('show');
        }

        function sort_items_s(menu) {
            console.log(menu);
            $('#' + menu + '_saved').removeClass('hide');
            $('#' + menu + '_add').addClass('hide');
            setTimeout(function () {
                $('#' + menu + '_saved').addClass('hide');
                $('#' + menu + '_add').removeClass('hide');
            }, 1500);
        }

        function sort_items_f(menu) {
            $('#' + menu + '_failed').removeClass('hide');
            $('#' + menu + '_add').addClass('hide');
            setTimeout(function () {
                $('#' + menu + '_failed').addClass('hide');
                $('#' + menu + '_add').removeClass('hide');
            }, 1500);
        }

        $(document).ready(function () {
            initialize_sort('sortable', sort_items_s, sort_items_f);
            watch_for_delete('.delete', 'menu item', function (el) {
                return el.closest('li').attr('id');
            });
        });

    </script>
@stop


