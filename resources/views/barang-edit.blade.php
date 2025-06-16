@extends('layouts.app')

@section('title', 'Edit Barang')

@section('content')
<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Edit Barang</h1>

    <!-- Form untuk edit barang -->
    <form action="{{ route('kelola-barang.update', $barang->id) }}" method="POST">
        @csrf
        <!-- Menggunakan metode POST dengan _method PATCH -->
        @method('PATCH')

        <div class="form-group">
            <label for="kode_barang">Kode Barang</label>
            <input type="text" name="kode_barang" class="form-control" value="{{ $barang->kode_barang }}" required readonly>
        </div>

        <div class="form-group">
            <label for="nama_barang">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" value="{{ $barang->nama_barang }}" required>
        </div>

        <div class="form-group">
            <label for="keterangan">Keterangan</label>
            <textarea name="keterangan" class="form-control">{{ $barang->keterangan }}</textarea>
        </div>

        <div class="form-group">
            <label for="harga_beli">Harga Beli</label>
            <input type="text" name="harga_beli" class="form-control" value="{{ $barang->harga_beli }}" required>
        </div>

        <div class="form-group">
            <label for="harga_jual">Harga Jual</label>
            <input type="text" name="harga_jual" class="form-control" value="{{ $barang->harga_jual }}" required>
        </div>

        <div class="form-group">
            <label for="jumlah_awal">Jumlah Awal</label>
            <input type="text" name="jumlah_awal" class="form-control" value="{{ $barang->jumlah_awal }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
