<?php AssetBuilder::setStatus('jquery-sortable', true); ?>

<h1>Menus</h1>

{!! $menus !!}

@section('scripts')
    <script type="text/javascript">

        var selected_menu;
        var selected_item;

        $('.sub-levels').click(function () {
            var spModal = $('#subLevelMIModal');
            spModal.find('.page-name').html($(this).data('name'));
            selected_item = $(this).closest('li');
            $.ajax({
                url: route('coaster.admin.menus.get-levels'),
                type: 'POST',
                dataType: 'json',
                data: {id: selected_item.data('id')},
                success: function (r) {
                    $('#subLevelMIModal').modal('show');
                    $('#sublevels').val(r.sub_levels);
                    spModal.find('.page-levels').html(r.max_levels);
                    spModal.find('.menu-max-levels').html(r.menu_max_levels);
                    spModal.find('option:gt(0)').hide();
                    spModal.find('option:lt(' + (r.menu_max_levels + 1) + ')').show();
                },
                error: function () {
                    cms_alert('danger', 'Error receiving data');
                }
            });
        });

        $('#subLevelMIModal .change').click(function () {
            var sl = $('#sublevels').val();
            $.ajax({
                url: route('coaster.admin.menus.save-levels'),
                type: 'POST',
                dataType: 'json',
                data: {id: selected_item.data('id'), sub_level: sl},
                success: function (r) {
                    selected_item.find('> ol').remove();
                    selected_item.find('.sl_numb').html(sl);
                    selected_item.removeClass().append(r.children);
                    if (r.children) {
                        selected_item.addClass('mjs-nestedSortable-branch mjs-nestedSortable-collapsed');
                    } else {
                        selected_item.addClass('mjs-nestedSortable-leaf');
                    }
                    initList();
                }, error: function() {
                    cms_alert('danger', 'Error updating menu item');
                }
            });
        });

        $('#renameMIModal .change').click(function () {
            var custom_name = $('#custom_name').val();
            $.ajax({
                url: route('coaster.admin.menus.rename'),
                type: 'POST',
                data: {id: selected_item.data('id'), pageId: selected_item.data('page-id'), customName: custom_name},
                success: function () {
                    console.log(custom_name, selected_item);
                    if (custom_name != '') {
                        selected_item.find('> div > .custom-name').html("&nbsp;(Custom Name: " + custom_name + ")");
                    } else {
                        selected_item.find('> div > .custom-name').html('');
                    }
                }, error: function() {
                    cms_alert('danger', 'Error updating menu item');
                }
            });
        });

        $('#addMIModal .add').click(function () {
            $.ajax({
                url: route('coaster.admin.menus.add'),
                type: 'POST',
                data: {id: $('#menu_item').val(), menu: selected_menu},
                success: function (r) {
                    if (r % 1 === 0) {
                        location.reload();
                    }
                    else {
                        cms_alert('danger', 'The menu item was not added');
                    }
                }
            });
        });

        function add_item(menu) {
            selected_menu = menu;
            $('#addMIModal').modal('show');
        }

        function sort_items_s(menu) {
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


        var initList = function() {
            $('.sortable').nestedSortable({
                forcePlaceholderSize: true,
                handle: 'div',
                helper: 'clone',
                items: 'li',
                opacity: .6,
                placeholder: 'placeholder',
                revert: 250,
                tabSize: 25,
                tolerance: 'pointer',
                toleranceElement: '> div',
                maxLevels: 10,
                isTree: true,
                expandOnHover: 700,
                startCollapsed: true,
                disableParentChange: true
            });

            $('.disclose').unbind('click').on('click', function () {
                $(this).toggleClass('glyphicon-plus-sign').toggleClass('glyphicon-minus-sign');
                $(this).closest('li').toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
            });

            initialize_sort('nestedSortable', sort_items_s, sort_items_f);
            watch_for_delete('.delete', 'menu item', function (el) {
                return el.closest('li').attr('id');
            }, route('coaster.admin.menus.delete', {itemId: ''}));

            $('.rename').click(function () {
                selected_item = $(this).closest('li');
                $('#renameMIModal').modal('show');
            });
        };

        $(document).ready(function () {
            initList();
        });

    </script>
@stop


