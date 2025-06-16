@extends('layouts.app')

@section('title', 'Kelola Barang - Implementasi SVR') <!-- Menentukan title untuk halaman home -->

@section('content')
<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Kelola Barang</h1>
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<!-- Content Row -->
<div class="row">
    <a href="{{ route('kelola-barang.create') }}" class="btn btn-primary mb-3">Tambah Barang</a>
    <button type="button" class="btn btn-success mb-3 ml-2" data-toggle="modal" data-target="#importModal">Import Barang</button>
    <!-- <button type="button" class="btn btn-danger mb-3 ml-2" onclick="confirmTruncate()">Truncate Data Barang</button> -->

    <!-- Modal untuk Import Barang -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Barang dari Excel</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('barang.import') }}" method="POST" enctype="multipart/form-data">
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
    <!-- Modal Konfirmasi Truncate -->
    <div class="modal fade" id="confirmTruncateModal" tabindex="-1" role="dialog" aria-labelledby="confirmTruncateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTruncateModalLabel">Konfirmasi Truncate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus semua data barang?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <form id="truncateForm" action="{{ route('kelola-barang.truncate') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">Truncate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <table id="barangTable" class="table table-striped">
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Keterangan</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Margin</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($barangs as $barang)
            <tr>
                <td>{{ $barang->kode_barang }}</td>
                <td>{{ $barang->nama_barang }}</td>
                <td>{{ $barang->keterangan }}</td>
                <td>{{ $barang->harga_beli }}</td>
                <td>{{ $barang->harga_jual }}</td>
                <td>{{ $barang->harga_jual - $barang->harga_beli }}</td>
                <td>
                    <a href="{{ route('kelola-barang.edit', $barang->id) }}" class="btn btn-warning">Edit</a>

                    <!-- Tombol Hapus yang memunculkan modal -->
                    <button type="button" class="btn btn-danger" onclick="confirmDelete('{{ $barang->id }}')">Hapus</button>
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus barang ini?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form id="deleteForm" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#barangTable').DataTable();
    });

    function confirmDelete(id) {
        var url = '{{ route("kelola-barang.destroy", ":id") }}';
        url = url.replace(':id', id);
        $('#deleteForm').attr('action', url); // Set the action attribute of the form
        $('#confirmDeleteModal').modal('show'); // Show the modal
    }

    function confirmTruncate() {
        $('#confirmTruncateModal').modal('show'); // Show the truncate confirmation modal
    }
</script>
@endsection