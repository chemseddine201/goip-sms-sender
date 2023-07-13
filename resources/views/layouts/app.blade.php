<!DOCTYPE html>
<html>
<head>
    <title>SMS SERVER</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="messages">Server</a>
        <ul class="nav justify-content-center">
            <li class="nav-item">
                <a class="nav-link" href="messages">Messages</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="lines">Lines</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="operators">Operators</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logs">Logs</a>
            </li>
        </ul>
    </nav>
    @yield('content')
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        setInterval(() => {
            $.ajax({
                url: 'api/lines/freelongBusy',
                type: 'get',
                success: function(response) {
                    //console.log("updated");
                },
            });
        }, 300000);
    </script>
    @yield('scripts')
</body>
</html>