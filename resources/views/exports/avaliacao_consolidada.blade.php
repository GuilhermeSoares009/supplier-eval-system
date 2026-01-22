<table>
    <thead>
        <tr>
            <th>Fornecedor</th>
            <th>Mês</th>
            <th>Ótimo</th>
            <th>Bom</th>
            <th>Regular</th>
            <th>Insatisfatório</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($linhas as $linha)
            <tr>
                <td>{{ $linha['fornecedor'] }}</td>
                <td>{{ $linha['mes'] }}</td>
                <td>{{ $linha['otimo'] }}</td>
                <td>{{ $linha['bom'] }}</td>
                <td>{{ $linha['regular'] }}</td>
                <td>{{ $linha['insatisfatorio'] }}</td>
                <td>{{ $linha['total'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
