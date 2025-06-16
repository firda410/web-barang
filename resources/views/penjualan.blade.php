@extends('layouts.app')

@section('title', 'Data Penjualan - Implementasi SVR') <!-- Menentukan title untuk halaman home -->
@section('content')
<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Data Penjualan</h1>

<!-- Content Row -->
<div class="row">
    <a href="{{ route('penjualan.create') }}" class="btn btn-primary mb-3">Tambah Data Penjualan</a>
    <!-- Tombol untuk membuka modal import -->
    <button type="button" class="btn btn-success mb-3 ml-2" data-toggle="modal" data-target="#importModal">Import
        Data</button>


    <!-- Modal untuk Import Data -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Data dari Excel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('penjualan.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="file">Pilih File Excel</label>
                            <input type="file" class="form-control" id="file" name="file" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
<div class="row">
    <table id="penjualanTable" class="table table-striped">
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th> <!-- Added column for Nama Barang -->
                <th>Jumlah Terjual</th>
                <th>Total Harga</th>
                <th>Tanggal Penjualan</th>
            </tr>
        </thead>
        <tbody>
            @if($penjualans->isEmpty())
                <tr>
                    <td colspan="5">Tidak ada data penjualan yang ditemukan.</td>
                </tr>
            @else
                @foreach($penjualans as $penjualan)
                    <tr>
                        <td>{{ $penjualan->kode_barang }}</td>
                        <td>{{ $penjualan->barang->nama_barang ?? 'Nama barang tidak ditemukan' }}</td>
                        <!-- Display Nama Barang -->
                        <td>{{ $penjualan->jumlah_terjual }}</td>
                        <td>{{ $penjualan->total_harga }}</td>
                        <td>{{ $penjualan->tanggal_penjualan }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>


    </table>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('#penjualanTable').DataTable();
    });
</script>
@endsection