<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon">
            <i class="fas fa-store"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Toko Eshan</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('home') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Menu
    </div>

    <!-- Kelola Barang -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('kelola-barang') }}">
            <i class="fas fa-box"></i>
            <span>Kelola Barang</span></a>
    </li>

    <!-- Prediksi Penjualan -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('prediksi') }}">
            <i class="fas fa-chart-line"></i>
            <span>Prediksi Penjualan</span></a>
    </li>

    <!-- Penjualan -->
    <li class="nav-item">
        <a class="nav-link" href="{{ route('penjualan') }}">
            <i class="fas fa-cash-register"></i>
            <span>Penjualan</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">
</ul>
<!-- End of Sidebar -->
