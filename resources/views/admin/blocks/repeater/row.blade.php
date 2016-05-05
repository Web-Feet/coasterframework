<tr id="{!! $repeater_id !!}_{!! $row_id !!}">
    <td class="repeater-action">
        {!! Form::hidden('repeater['.$repeater_id.']['.$row_id.'][order]', 1) !!}
        <i class="glyphicon glyphicon-move"></i>
    </td>
    <td>
        {!! $blocks !!}</td>
    <td class="repeater-action">
        <i class="glyphicon glyphicon-remove itemTooltip"
           onclick="repeater_delete({!! $repeater_id !!}, {!! $row_id !!})"></i>
    </td>
</tr>