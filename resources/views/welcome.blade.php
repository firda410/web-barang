<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di Toko Eshan</title>
    <!-- Link ke Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .hero {
            background: url('https://c.ndtvimg.com/2022-11/2c92b5t_grocery-store_625x300_14_November_22.jpg') no-repeat center center;
            background-size: cover;
            height: 100vh;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .hero h1 {
            font-size: 3rem;
            font-weight: bold;
        }
        .hero p {
            font-size: 1.5rem;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="hero">
    <div>
        <h1>Selamat Datang di Toko Eshan!</h1>
        <p>Prediksi Tren Penjualan dengan Support Vector Regression</p>
        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-custom btn-lg mr-3">Login</a>
            <a href="{{ route('register') }}" class="btn btn-light btn-lg">Daftar</a>
        </div>
    </div>
</div>

<!-- Link ke Bootstrap JS dan jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
