@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tambah Penjualan</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('penjualan.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="kode_barang">Barang</label>
            <select name="kode_barang" id="kode_barang" class="form-control" required>
    <option value="">Pilih Barang</option>
    @foreach($barangs as $barang)
        <option value="{{ $barang->kode_barang }}" data-harga="{{ $barang->harga_jual }}">{{ $barang->nama_barang }}</option>
    @endforeach
</select>

            @error('kode_barang')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="jumlah_terjual">Jumlah Terjual</label>
            <input type="number" name="jumlah_terjual" id="jumlah_terjual" class="form-control" required>
            @error('jumlah_terjual')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="total_harga">Total Harga</label>
            <input type="text" name="total_harga" id="total_harga" class="form-control" readonly>
            @error('total_harga')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="tanggal_penjualan">Tanggal Penjualan</label>
            <input type="date" name="tanggal_penjualan" id="tanggal_penjualan" class="form-control" required>
            @error('tanggal_penjualan')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('penjualan') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const barangSelect = $('#kode_barang');
        const jumlahTerjualInput = $('#jumlah_terjual');
        const totalHargaInput = $('#total_harga');

        // Initialize Select2
        barangSelect.select2();

        // Event listener for when the barang is changed
        barangSelect.on('change', function() {
            calculateTotalHarga();
        });

        // Event listener for when the jumlah terjual is changed
        jumlahTerjualInput.on('input', function() {
            calculateTotalHarga();
        });

        function calculateTotalHarga() {
            const selectedOption = barangSelect.find(':selected');
            const hargaJual = parseFloat(selectedOption.data('harga'));
            const jumlahTerjual = parseInt(jumlahTerjualInput.val()) || 0; // Fallback to 0 if NaN

            const totalHarga = hargaJual * jumlahTerjual;
            totalHargaInput.val(isNaN(totalHarga) ? '' : totalHarga.toFixed(2));
        }
    });
</script>
@endsection

@endsection
