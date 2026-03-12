@extends('layouts.App')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body border-bottom">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">
                            Konfigurasi & Mutasi Aset
                        </h5>
                        <div class="flex-shrink-0">
                            <button type="submit" class="btn btn-primary" form="form-add-transaction">Kirim</button>
                            <a href="#!" class="btn btn-light"><i class="mdi mdi-refresh"></i></a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('transaction.store') }}" id="form-add-transaction"
                        class="needs-validation" novalidate>
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="">Teknisi Pemasang / Pencabut</label>
                                    <select name="employee_id"
                                        class="form-control select2-staff @error('employee_id') is-invalid @enderror">
                                        <option value="">Pilih Teknisi</option>
                                    </select>
                                </div>

                                @error('employee_id')
                                    <span class="invalid-feedback" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="">CID (Customer)</label>
                                    <select name="division_id"
                                        class="form-control select2-customer @error('division_id') is-invalid @enderror"
                                        id="">
                                        <option value="">Pilih Customer</option>
                                    </select>
                                </div>

                                @error('division_id')
                                    <span class="invalid-feedback" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="">Tipe Transaksi Utama</label>
                                    <select name="status"
                                        class="form-control select2-status @error('status') is-invalid @enderror"
                                        id="">
                                        <option value="1">OUT / Pemasangan Baru</option>
                                        <option value="0">IN / Penarikan Saja</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="">Pilih Kontrak</label>
                                    <select name="contract_type"
                                        class="form-control @error('contract_type') is-invalid @enderror">
                                        <option value="">Pilih Status</option>
                                        <option value="sewa">Sewa</option>
                                        <option value="cicil">Cicil</option>
                                        <option value="beli_putus">Beli Putus</option>
                                        <option value="pinjam">Pinjam</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-2 contract-dates" style="display: none;">
                                <div class="mb-3">
                                    <label for="">Masa Kontrak (Mulai)</label>
                                    <input type="date" class="form-control @error('contract_start_date') is-invalid @enderror"
                                        name="contract_start_date">
                                </div>
                            </div>

                            <div class="col-md-2 contract-dates" style="display: none;">
                                <div class="mb-3">
                                    <label for="">Masa Kontrak (Akhir)</label>
                                    <input type="date" class="form-control @error('contract_end_date') is-invalid @enderror"
                                        name="contract_end_date">
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="">Note</label>
                                    <input type="text" class="form-control @error('note') is-invalid @enderror"
                                        name="note">
                                </div>

                                @error('note')
                                    <span class="invalid-feedback" role="alert">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div id="customer-assets-section" style="display: none;" class="mt-4 mb-4">
                            <hr>
                            <h6 class="text-primary"><i class="bx bx-list-check"></i> Asset Terpasang di Pelanggan</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle" id="table-customer-assets">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>SN / UID</th>
                                            <th>Nama Barang</th>
                                            <th>Qty</th>
                                            <th>Tgl Pasang</th>
                                            <th>Status Saat Ini</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Will be populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                        </div>

                        <h6 class="mt-4"><i class="bx bx-plus-circle"></i> Input Transaksi Barang Baru (Gudang)</h6>

                        <div class="row mt-3">
                            <div class="col-md">
                                <table class="table align-middle" id="table-asset">
                                    <thead>
                                        <th>UID</th>
                                         <th>Qty</th>
                                         <th>Kategori</th>
                                         <th>Spesifikasi</th>
                                         <th>Tahun Produksu</th>
                                         <th>Tanggal Pembelian</th>
                                         <th>Harga</th>
                                         <th>Kondisi</th>
                                         <th>Status</th>
                                         <th>Action</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                 <input type="hidden" name="action_type[0]" class="action-type" value="normal">
                                                 <select name="uid[0]" class="form-control select2-asset" id="">
                                                     <option value="">Cari Asset</option>
                                                 </select>
                                             </td>
                                             <td>
                                                 <div class="input-group" style="min-width: 120px;">
                                                     <input type="number" step="0.01" name="qty[0]" class="form-control qty" value="1" min="0">
                                                     <span class="input-group-text uom">pcs</span>
                                                 </div>
                                             </td>
                                            <td class="category"></td>
                                            <td class="specification"></td>
                                            <td class="production_year"></td>
                                            <td class="purchase_date"></td>
                                            <td class="purchase_price"></td>
                                            <td class="condition"></td>
                                            <td class="status"></td>
                                            <td class="action"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!--end card-->
        </div>
        <!--end col-->
    </div>
    <!--end row-->

    <!-- Modal Konfirmasi Pencabutan -->
    <div class="modal fade" id="modal-rollback" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Penarikan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apa yang ingin Anda lakukan dengan <b id="rollback-sn"></b>?</p>
                    <input type="hidden" id="rollback-id">
                    
                    <div class="row">
                        <div class="col-6">
                            <h6 class="text-muted mb-2">Penarikan Saja (IN)</h6>
                            <div class="d-grid gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-action-rollback" data-type="cabut">
                                    Kembali (Bagus)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning btn-action-rollback" data-type="rusak">
                                    Kembali (Rusak)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-action-rollback" data-type="dibuang">
                                    Rusak (Dibuang)
                                </button>
                            </div>
                        </div>
                        <div class="col-6 border-start">
                            <h6 class="text-muted mb-2">Ganti Baru (REPLACE)</h6>
                            <div class="d-grid gap-1">
                                <button type="button" class="btn btn-sm btn-primary btn-action-rollback" data-type="ganti_cabut">
                                    Ganti & Tarik OK
                                </button>
                                <button type="button" class="btn btn-sm btn-warning btn-action-rollback" data-type="ganti_rusak">
                                    Ganti & Tarik Rusak
                                </button>
                                <button type="button" class="btn btn-sm btn-danger btn-action-rollback" data-type="ganti_dibuang">
                                    Ganti & Dibuang
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('title')
    Asset
@endsection

@section('plugin')
    <script src="{{ asset('libs/select2/js/select2.min.js') }}"></script>

    <script>
        $(function() {
            var i = 0;
            select2();

            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

            $('#form-add-transaction').submit(function() {
                $('#form-add-transaction small.text-danger').remove();

                var data = $(this).serialize();

                var arr = [];
                var duplicates = false;

                $('.select2-asset').each(function() {
                    var value = $(this).val();
                    if (arr.indexOf(value) == -1) {
                        arr.push(value);
                    } else {
                        duplicates = true;
                    }
                });

                // console.log(arr);

                if (duplicates) {
                    notification('error',
                        'Ada asset yang sama, silahkan diubah terlebih dahulu!');
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: $(this).attr('action'),
                    data: data,
                    dataType: "JSON",
                    success: function(response) {
                        if (response.success == false) {
                            if (response.hasOwnProperty('data')) {
                                $.each(response.data.error, function(i, v) {
                                    // console.log(v);
                                    $('#form-add-transaction [name="' + i + '"]').after(
                                        '<small class="text-danger">' + v +
                                        '</small>');
                                });
                            } else {
                                notification('error', response.message);
                            }

                            return false;
                        }

                        notification('success', response.message);
                        window.location.href = "{{ route('transaction') }}";
                    }
                });

                return false;
            });

            function select2() {
                $('.select2-asset').select2({
                    width: '100%',
                    ajax: {
                        url: "{{ route('select.asset') }}",
                        type: "get",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term, // search term
                                type: "{{ $type == 'in' ? 'stock_in' : 'stock_out' }}"
                            };
                        },
                        processResults: function(response) {
                            return {
                                results: response
                            };
                        },
                        cache: true
                    }
                });
            }


            $('#table-asset').on('click', '.btn-delete', function() {
                $(this).closest('tr').remove();
            })

            $('.select2-staff').select2({
                width: '100%',
                ajax: {
                    url: "{{ route('select.staff') }}",
                    type: "get",
                    dataType: 'json',
                    data: function(params) {
                        return {
                            search: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });

            $('.select2-status').select2({
                width: '100%',
                minimumResultsForSearch: Infinity // Sembunyikan search box untuk status
            });

            $('.select2-customer').select2({
                width: '100%',
                ajax: {
                    url: "{{ route('select.customer') }}",
                    type: "get",
                    dataType: 'json',
                    data: function(params) {
                        return {
                            search: params.term // search term
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: response
                        };
                    },
                    cache: true
                }
            });

            // Listener CID change untuk tarik data aset terpasang
            $('.select2-customer').on('change', function() {
                var cid = $(this).val();
                if (!cid) {
                    $('#customer-assets-section').hide();
                    return;
                }

                $.ajax({
                    url: "{{ route('select.customer.assets') }}",
                    type: "GET",
                    data: { cid: cid },
                    success: function(response) {
                        var tbody = $('#table-customer-assets tbody');
                        tbody.empty();

                        if (response.length > 0) {
                            $('#customer-assets-section').show();
                            $.each(response, function(index, asset) {
                                var actionHtml = '';
                                var statusHtml = '';

                                if (asset.ownership_status === 'sold_to_customer') {
                                    actionHtml = '<span class="badge bg-secondary">Beli Putus (Milik Pelanggan)</span>';
                                    statusHtml = '<span class="badge" style="background-color: #6f42c1;">Beli Putus</span>';
                                } else if (asset.ownership_status === 'rented_to_customer') {
                                    actionHtml = `<button type="button" class="btn btn-sm btn-outline-danger btn-rollback" 
                                            data-id="${asset.id}" data-sn="${asset.serial_number || asset.id}">
                                            <i class="bx bx-trash"></i> Cabut / Ganti
                                        </button>`;
                                    statusHtml = '<span class="badge bg-warning text-dark">Sewa / Cicil</span>';
                                } else {
                                    actionHtml = `<button type="button" class="btn btn-sm btn-outline-danger btn-rollback" 
                                            data-id="${asset.id}" data-sn="${asset.serial_number || asset.id}">
                                            <i class="bx bx-trash"></i> Cabut / Ganti
                                        </button>`;
                                    statusHtml = '<span class="badge bg-info">Dipinjamkan</span>';
                                }

                                var uom = asset.asset_type && asset.asset_type.uom ? asset.asset_type.uom : 'pcs';

                                var row = `<tr>
                                    <td>${asset.serial_number || asset.id}</td>
                                    <td>${asset.asset_type ? asset.asset_type.name : '-'}</td>
                                    <td>${asset.quantity} ${uom}</td>
                                    <td>${asset.updated_at.split('T')[0]}</td>
                                    <td>${statusHtml}</td>
                                    <td>${actionHtml}</td>
                                </tr>`;
                                tbody.append(row);
                            });
                        } else {
                            $('#customer-assets-section').hide();
                        }
                    }
                });
            });

            $('#table-customer-assets').on('click', '.btn-rollback', function() {
                var id = $(this).data('id');
                var sn = $(this).data('sn');
                $('#rollback-id').val(id);
                $('#rollback-sn').text(sn);
                $('#modal-rollback').modal('show');
            });

            $('.btn-action-rollback').on('click', function() {
                var type = $(this).data('type');
                var id = $('#rollback-id').val();
                var sn = $('#rollback-sn').text();

                var itemAction = type;
                if (type.startsWith('ganti_')) {
                    itemAction = type.replace('ganti_', '');
                    $('.select2-status').val('1').trigger('change');
                    notification('info', 'Item lama diproses. Silahkan pilih barang PENGGANTI di tabel bawah.');
                } else {
                    $('.select2-status').val('0').trigger('change');
                }

                addAssetToTransaction(id, itemAction);
                $('#modal-rollback').modal('hide');
            });

            function addAssetToTransaction(assetId, actionType = 'normal') {
                $.ajax({
                    type: "get",
                    url: "{{ URL('select/asset') }}/" + assetId,
                    dataType: "JSON",
                    success: function(response) {
                        var targetSelect = $('.select2-asset').last();
                        
                        // Set action type on the row and assign name
                        var rowEl = targetSelect.closest('tr');
                        rowEl.find('.action-type').val(actionType).attr('name', 'action_type[' + i + ']');
                        
                        var newOption = new Option(response.text, response.id, true, true);
                        targetSelect.append(newOption).trigger('change');
                        
                        if (actionType == 'rusak') {
                             $('input[name="note"]').val('Penarikan barang rusak: ' + response.text);
                        } else if (actionType == 'dibuang') {
                             $('input[name="note"]').val('Barang rusak & ditinggal di lokasi: ' + response.text);
                        }
                    }
                });
            }

            // Listener ketika status transaksi (IN/OUT) diubah
            $('.select2-status').on('change', function() {
                var status = $(this).val();
                if (status == '0') { // Jika diubah ke IN (Cabut)
                    var firstAsset = $('.select2-asset').first();
                    if (firstAsset.val()) {
                        firstAsset.trigger('change');
                    }
                }
            });

            $('select[name="contract_type"]').on('change', function() {
                var ctype = $(this).val();
                if (ctype == 'sewa' || ctype == 'cicil') {
                    $('.contract-dates').show();
                } else {
                    $('.contract-dates').hide();
                }
            });

            $('#table-asset').on('change', '.select2-asset', function() {
                var id = $(this).val();
                var row = $(this);

                if (!id) return;

                $.ajax({
                    type: "get",
                    url: "{{ URL('select/asset') }}/" + id,
                    dataType: "JSON",
                    success: function(response) {
                        row.attr('name', 'uid[' + i + ']');
                        row.closest('tr').find('.action-type').attr('name', 'action_type[' + i + ']');
                        row.parent().parent().find('.category').html(response.category.name);
                        row.parent().parent().find('.qty').val(response.quantity || 1);
                        row.parent().parent().find('.qty').attr('name', 'qty[' + i + ']');
                        row.parent().parent().find('.uom').html(response.uom || 'pcs');
                        row.parent().parent().find('.specification').html(response.specification);
                        row.parent().parent().find('.production_year').html(response.production_year);
                        row.parent().parent().find('.purchase_date').html(response.purchase_date);
                        row.parent().parent().find('.purchase_price').html(response.purchase_price);
                        row.parent().parent().find('.condition').html(response.condition);

                        var status = '';
                        var statusSelect = $('.select2-status');
                        
                        if (response.status == 0) {
                            status = '<span class="badge bg-success">Standby</span>';
                        } else {
                            status = '<span class="badge bg-danger">Not Standby</span>';
                            
                            // Jika ini Pencabutan (IN) dan asset ada CID-nya, auto-fill Customer
                            if ("{{ $type }}" == "in" && response.cid) {
                                var customerSelect = $('.select2-customer');
                                var newOption = new Option(response.cid + ' - ' + response.customer_name, response.cid, true, true);
                                customerSelect.append(newOption).trigger('change');
                                notification('info', 'Customer otomatis disesuaikan berdasarkan lokasi aset.');
                            }
                        }

                        row.parent().parent().find('.status').html(status);
                        row.parent().parent().find('.action').html(
                            '<a href="#" class="text-danger btn-delete"><i class="bx bx-x-circle bx-sm"></i></a>'
                        );

                        if (row.attr('add') != 'yes') {
                            $('#table-asset tbody').append('<tr>' +
                                '                              <td>' +
                                '                                  <select name="" class="form-control select2-asset" id="">' +
                               '                                      <option value="">Cari Asset</option>' +
                               '                                  </select>' +
                               '                              </td>' +
                               '                              <td>' +
                               '                                  <div class="input-group" style="min-width: 120px;">' +
                               '                                      <input type="number" step="0.01" name="" class="form-control qty" value="1" min="0">' +
                               '                                      <span class="input-group-text uom">pcs</span>' +
                               '                                  </div>' +
                               '                              </td>' +
                               '                              <td class="category"></td>' +
                               '                              <td class="specification"></td>' +
                               '                              <td class="production_year"></td>' +
                               '                              <td class="purchase_date"></td>' +
                               '                              <td class="purchase_price"></td>' +
                               '                              <td class="condition"></td>' +
                               '                              <td class="status"></td>' +
                               '                              <td class="action"></td>' +
                               '                          </tr>');
                        }

                        row.attr('add', 'yes');
                        select2();
                        i++;
                    }
                });

                return false;
            });
        });
    </script>
@endsection
