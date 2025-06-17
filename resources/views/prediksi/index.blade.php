@extends('layouts.app')

@section('title', 'Evaluasi Model Prediksi')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Prediksi Penjualan Model Support Vector Regression</h1>

@if(isset($pythonError) && $pythonError)
<div class="alert alert-danger" role="alert">
    <strong>Error dari Python:</strong> {{ $pythonError }}
</div>
@endif

{{-- Card untuk Tabel Evaluasi Model --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tabel Perbandingan Aktual vs. Prediksi (SVR) Tahun 2025
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTableEvaluation" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Penjualan Aktual</th>
                                <th>Prediksi Penjualan</th>
                                <th>Keuntungan Aktual</th>
                                <th>Prediksi Keuntungan</th>
                                <th>Selisih Keuntungan</th>
                                <th>MAPE (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableData as $data)
                            <tr>
                                <td>{{ $data['month'] }}</td>
                                <td>{{ number_format($data['actual_sales'], 0, ',', '.') }}</td>
                                <td>{{ number_format($data['predicted_sales'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($data['keuntungan_aktual'], 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($data['prediksi_keuntungan'], 2, ',', '.') }}</td>
                                <td class="{{ $data['selisih_keuntungan'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($data['selisih_keuntungan'], 2, ',', '.') }}
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $data['mape'] <= 10 ? 'badge-success' : ($data['mape'] <= 25 ? 'badge-warning' : 'badge-danger') }}">
                                        {{ $data['mape'] }}%
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">Data evaluasi belum tersedia.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <small>
                        <strong>Keterangan MAPE (Mean Absolute Percentage Error):</strong>
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item"><span class="badge badge-success">&le; 10%</span>: Prediksi
                                Sangat Baik</li>
                            <li class="list-inline-item"><span class="badge badge-warning">11% - 25%</span>: Prediksi
                                Baik</li>
                            <li class="list-inline-item"><span class="badge badge-danger">&gt; 25%</span>: Prediksi
                                Kurang Akurat</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Card untuk Grafik Evaluasi Model --}}
<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Perbandingan Penjualan Aktual vs Prediksi Tahun
                    2025</h6>
            </div>
            <div class="card-body">
                <div style="height: 400px;">
                    <canvas id="chartEvaluation"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
{{-- Pastikan Chart.js sudah di-load, bisa dari CDN atau file lokal --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data chart dari controller
        const chartData = @json($chartData ?? null);

        if (chartData && chartData.labels && chartData.datasets) {
            const ctxEvaluation = document.getElementById('chartEvaluation');
            if (ctxEvaluation) {
                new Chart(ctxEvaluation.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: chartData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Jumlah Barang Terjual'
                                },
                                ticks: {
                                    callback: value => Number(value).toLocaleString('id-ID')
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Bulan'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += Number(context.parsed.y).toLocaleString('id-ID');
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    });
</script>
@endsection