@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row w-100 p-2 pb-0">
            <div class="col-md-6 text-left">
                <h5>Operators</h5>
            </div>
        </div>
        <div class="row w-100 table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if($operators && count($operators))
                        @foreach ($operators as $operator)
                            <tr>
                                <td class="text-capitalize">{{ $operator->id }}</td>
                                <td class="text-capitalize">{{ $operator->name }}</td>
                                <td class="{{$operator->status ? 'text-success' : 'text-warning'}} text-capitalize">{{ (($operator->status) ? "active" : "disabled") }}</td>
                                <td class="text-capitalize">
                                    <button type="button" class="btn btn-sm btn-outline-info btn-switch" data-id="{{$operator->id}}">Switch</button>
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
                        title: 'Are you sure you want to switch this operator?',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        denyButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'api/operators/switch',
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
        });
    </script>
@endsection