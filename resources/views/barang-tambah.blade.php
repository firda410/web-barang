@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')
<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Tambah Barang</h1>

                <form action="{{ route('kelola-barang.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="kode_barang">Kode Barang</label>
                        <input type="text" class="form-control" name="kode_barang" id="kode_barang" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_barang">Nama Barang</label>
                        <input type="text" class="form-control" name="nama_barang" id="nama_barang" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="keterangan"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="harga_beli">Harga Beli</label>
                        <input type="number" class="form-control" name="harga_beli" id="harga_beli" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="harga_jual">Harga Jual</label>
                        <input type="number" class="form-control" name="harga_jual" id="harga_jual" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="jumlah_awal">Jumlah Awal</label>
                        <input type="number" class="form-control" name="jumlah_awal" id="jumlah_awal" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('kelola-barang') }}" class="btn btn-secondary">Kembali</a>
                </form>

@if ($errors->any())
    <div class="alert alert-danger mt-3">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success mt-3">
        {{ session('success') }}
    </div>
@endif
@endsection
