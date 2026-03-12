@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">Monitoring Asset</h5>
                    </div>
                </div>
                <div class="card-body">

                    <table class="table table-striped align-middle dt-responsive nowrap w-100" id="monitoring-asset-table">
                        <thead>
                            <th scope="col" style="width: 10px">No</th>
                            <th scope="col">UID</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Spesifikasi</th>
                            <th scope="col">Jumlah Transaksi</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created At</th>
                            <th scope="col" style="width:30px">Action</th>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                    <!--end row-->
                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->

    </div>

    <div class="modal fade" id="modal-hitory-transaction" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        role="dialog" aria-labelledby="title-hitory-transaction" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="title-hitory-transaction">History Transaksi Per Asset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <tr>
                            <td style="width: 20%">UID</td>
                            <td style="width: 3%">:</td>
                            <td id="uid-detail"></td>
                        </tr>

                        <tr>
                            <td style="width: 20%">Kategori</td>
                            <td style="width: 3%">:</td>
                            <td id="category-detail"></td>
                        </tr>

                        <tr>
                            <td style="width: 20%">Spesifikasi</td>
                            <td style="width: 3%">:</td>
                            <td id="specification-detail"></td>
                        </tr>

                        <tr>
                            <td style="width: 20%">Status</td>
                            <td style="width: 3%">:</td>
                            <td id="status-detail"></td>
                        </tr>
                    </table>

                    <h5 class="mt-5">Detail Transaksi</h5>
                    <table class="table">
                        <thead>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Status Transaksi</th>
                            <th>Customer (CID)</th>
                            <th>Status Barang</th>
                            <th>Technician</th>
                            <th>Catatan</th>
                        </thead>
                        <tbody id="table-body-transaksi"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <!-- DataTables -->
    <link href="{{ asset('libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />

    <!-- Responsive datatable examples -->
    <link href="{{ asset('libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet"
        type="text/css" />

    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('title')
    Asset
@endsection

@section('plugin')
    <!-- Required datatable js -->
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>

    <script>
        $(function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            var table = $("#monitoring-asset-table").DataTable({
                lengthChange: !1,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('monitor.asset.datatable') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = CSRF_TOKEN;
                    }
                },
                columnDefs: [{
                    className: "align-middle",
                    targets: "_all"
                }, ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'uid',
                        name: 'uid'
                    },
                    {
                        data: 'category.name',
                        name: 'category'
                    },
                    {
                        data: 'specification',
                        name: 'specification'
                    },
                    {
                        data: '_jumlah_transaksi',
                        name: '_jumlah_transaksi'
                    },
                    {
                        data: '_status',
                        name: 'status'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(value) {
                            if (value === null) return "";
                            return moment(value).lang('id').format(
                                'Do MMMM YYYY H:mm:ss');
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });


            $('#monitoring-asset-table').on('click', '.btn-history', function() {
                var id = $(this).data('id');
                var uid = $(this).data('uid');
                var category = $(this).data('category');
                var specification = $(this).data('specification');
                var status = $(this).data('status');

                $('#modal-hitory-transaction').modal('show');

                var action = "{{ url('monitoring/asset/detail') }}/" + id;

                $.ajax({
                    type: "get",
                    url: action,
                    dataType: "JSON",
                    success: function(response) {
                        $('#uid-detail').html(uid);
                        $('#category-detail').html(category);
                        $('#specification-detail').html(specification);

                        if (status == 0) {
                            $('#status-detail').html(
                                '<span class="badge bg-success">Standby</span>');
                        } else {
                            $('#status-detail').html(
                                '<span class="badge bg-danger">Not Standby</span>');
                        }

                        var html = "";

                        if (response.data.length > 0) {
                            $.each(response.data, function(i, v) {
                                // Detail Transaksi
                                var transactionType = "";
                                if (v.transaction.type == 'stock_in') {
                                    transactionType = '<span class="badge bg-success">Masuk / Cabut</span>';
                                } else {
                                    transactionType = '<span class="badge bg-danger">Keluar / Pasang</span>';
                                }

                                var customerInfo = "-";
                                if (v.transaction.customer) {
                                    customerInfo = v.transaction.customer.name + ' (' + (v.transaction.customer.external_id || '-') + ')';
                                }

                                var contractType = "-";
                                if (v.transaction.contract_type == 'sewa') {
                                    contractType = '<span class="badge badge-soft-info">Sewa</span>';
                                } else if (v.transaction.contract_type == 'beli_putus') {
                                    contractType = '<span class="badge badge-soft-primary">Beli Putus</span>';
                                }

                                var techName = v.transaction.tridatu_user_name || '-';
                                var note = v.notes || v.transaction.notes || "-";
                                var created_at = moment(v.transaction.created_at).lang('id').format('Do MMMM YYYY H:mm:ss');

                                html += '<tr>' +
                                    '       <td>' + (i + 1) + '</td>' +
                                    '       <td>' + created_at + '</td>' +
                                    '       <td>' + transactionType + '</td>' +
                                    '       <td>' + customerInfo + '</td>' +
                                    '       <td>' + contractType + '</td>' +
                                    '       <td>' + techName + '</td>' +
                                    '       <td>' + note + '</td>' +
                                    '    </tr>';
                            });
                        } else {
                            html =
                                '<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>';
                        }


                        $('#table-body-transaksi').html(html);
                        // console.log(response)
                    }
                });

                return false;
            })

            function drawTable(param) {
                table.draw();

                if (param != null) {
                    $('#form-' + param)[0].reset();
                    $('#form-' + param).trigger('reset');
                    $('#modal-' + param).modal('hide');
                }
            }
        });
    </script>
@endsection
