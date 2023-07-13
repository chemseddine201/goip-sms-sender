@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row w-100 p-2 pb-0">
            <div class="col-md-6 text-left">
                <h5>Lines</h5>
            </div>
            <div class="col-md-6 text-right">
                <button type="button" class="btn btn-sm btn-outline-warning" id="btn-reset">Free Lines</button>
            </div>
        </div>
        <div class="row w-100 table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Operator</th>
                        <th>Status</th>
                        <th>Busy</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if($lines && count($lines))
                        @foreach ($lines as $line)
                            <tr>
                                <td class="text-capitalize">{{ $line->id }}</td>
                                <td class="text-capitalize">{{ $line->operator->name }}</td>
                                <td class="{{$line->status ? "text-success" : "text-warning"}} text-capitalize">{{ $line->status ? "active" : "disabled" }}</td>
                                <td class="{{$line->busy ? "text-warning" : "text-success"}} text-capitalize">{{ $line->busy ? "busy" : "free" }}</td>
                                <td class="text-capitalize">
                                    <button type="button" class="btn btn-sm btn-outline-info btn-switch" data-id="{{$line->id}}">Switch</button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr class="p-2 w-100 text-center text-muted">
                            <td colspan="6">No Records.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            $('.btn-switch').click(function(e) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                        title: 'Are you sure you want to switch this line?',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        denyButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'api/lines/switch',
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: $(this).data('id')
                            },
                            success: function(response) {
                                location.reload();
                            },
                        });
                    } 
                });
            });
            $('#btn-reset').click(function(e) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                        title: 'Are you sure you want to free all lines?',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Free',
                        denyButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'api/lines/reset',
                            type: 'POST',
                            data: {
                                _token: "{{ csrf_token() }}",
                                id: $(this).data('id')
                            },
                            success: function(response) {
                                location.reload();
                            },
                        });
                    } 
                })
            });
        });
    </script>
@endsection