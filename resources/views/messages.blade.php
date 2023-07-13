@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row w-100 p-2 pb-0">
            <div class="col-md-6 text-left">
                <h5>Messages&nbsp<span class="text-muted ms-2" style="cursor:pointer;" id="refresh-table">&#x21bb;</span></h5>
            </div>
            <div class="col-md-6 text-right">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear">Delete All</button>
            </div>
        </div>
        <div class="row w-100 table-container">
            <table id="table" class="table table-striped table-responsive">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>line</th>
                        <th>User</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Sent</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#table').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                deferRender: true,
                sDom: 'rtlfip',
                ajax: {
                    url: 'api/messages/fetch',
                    type: 'GET',
                },
                order: [[0, 'desc']],
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'line', name: 'line' },
                    { data: 'user', name: 'user' },
                    { data: 'phone', name: 'phone' },
                    { data: 'message', name: 'message' },
                    { data: 'sent_status', name: 'sent_status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                rowCallback: function(row, data, index) {
                    if (data?.sent_status == 1) {
                        $(row).find('td:eq(5)').html(`<span class='badge badge-pill badge-success'>Sent</span>`);
                    } else {
                        $(row).find('td:eq(5)').html(`<span class='badge badge-pill badge-danger'>No</span>`);
                    }
                    if (data?.line != 0) {
                        $(row).find('td:eq(1)').html(`<span class='badge badge-pill badge-info'>${data?.line}</span>`);
                    } else {
                        $(row).find('td:eq(1)').html(`<span class='badge badge-pill badge-light'>-</span>`);
                    }
                },
                columnDefs: [
                    {
                        targets: -1,
                        render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row?.id}">&times</button>`
                        }
                    }
                ]
            });
            
            setInterval(function() {
                table.ajax.reload(null, false);
            }, 300000);

            $(document).on('click', '#refresh-table', function() {
                table.ajax.reload(null, false);
            })

            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                        title: 'Are you sure you want to delete this message?',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        denyButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'api/messages',
                            type: 'DELETE',
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: $(this).data('id')
                            },
                            success: function(response) {
                                table.ajax.reload(null, false);
                            },
                        });
                    } 
                });
            });

            $('#btn-clear').click(function() {
                Swal.fire({
                        title: 'Are you sure you want to delete all message?',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        denyButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                        url: 'api/messages/deleteAll',
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            table.ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            console.log(xhr.responseText);
                        }
                    });
                    } 
                });
            });
        });
    </script>
@endsection