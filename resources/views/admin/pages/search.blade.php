<h1 class="pull-left">Search data</h1>


<p class="clearfix"></p>

<div class="table-responsive">
    <table class="table table-bordered">
        <tr>
            <th>Term</th>
            <th>Num. searches</th>
            <th>Last Searched</th>
        </tr>
        @foreach ($search_data as $term)
            <tr id="st_{{ $term->id }}">
                <td>
                  {{ $term->term }}
                </td>
                <td>
                    {{ $term->count }}
                </td>
                <td>
                  {{ DateTimeHelper::display($term->updated_at, 'short') }}
                </td>
            </tr>
        @endforeach
    </table>
</div>
