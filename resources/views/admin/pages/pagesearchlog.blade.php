<h1 class="pull-left">Search data</h1>


<p class="clearfix"></p>

<table class="table table-bordered">
    <tr>
        <th>Term</th>
        <th>Num. searches</th>
        <th>Last Searched</th>
    </tr>
    @foreach ($searchdata as $term)
        <tr id="st_{{ $term->id }}">
            <td>
              {{ $term->term }}
            </td>
            <td>{{ $term->count }}</td>
            <td>
              {{ $term->updated_at->format('d/m/Y') }}
            </td>

        </tr>
    @endforeach
</table>

@section('scripts')
<script type="text/javascript">

</script>
@stop
