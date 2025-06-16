@extends('layouts.app')

@section('title', 'Data Penjualan - Implementasi SVR')

@section('content')
<!-- Page Heading -->
<h1 class="h3 mb-4 text-gray-800">Dashboard</h1>

@if($totalBarangTerjual == 0 && $totalKeuntungan == 0 && $totalTransaksi == 0)
    <div class="alert alert-warning" role="alert">
        Belum ada data penjualan. Silakan tambahkan data penjualan terlebih dahulu.
    </div>
@else
    <!-- Content Row -->
    <div class="row">
        <!-- Total Barang Terjual -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Barang Terjual
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalBarangTerjual }} Barang</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Keuntungan -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Keuntungan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp
                                {{ number_format($totalKeuntungan, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Transaksi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalTransaksi }} Transaksi</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row for Charts -->
    <div class="row">
        <!-- Grafik Penjualan Bulanan -->
        <div class="col-xl-12 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Penjualan Bulanan</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="penjualanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barang Terlaris -->
        <div class="col-xl-4 col-lg-5" style="display: none;">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Barang Terlaris</h6>
                </div>
                <div class="card-body">
                    @if($barangTerlaris)
                        <p><strong>{{ $barangTerlaris->nama_barang }}</strong> dengan total terjual sebanyak
                            <strong>{{ $barangTerlaris->total_terjual }}</strong> barang.</p>
                    @else
                        <p>Tidak ada data barang terlaris.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <div class="row"></div>
@endif
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Grafik Penjualan Bulanan
    var ctx = document.getElementById('penjualanChart').getContext('2d');
    var penjualanChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($penjualanBulanan->pluck('bulan')),
            datasets: [{
                label: 'Total Barang Terjual',
                data: @json($penjualanBulanan->pluck('total_terjual')),
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { display: true, title: { display: true, text: 'Bulan' } },
                y: { display: true, title: { display: true, text: 'Jumlah Terjual' } }
            }
        }
    });
</script>
@endsection