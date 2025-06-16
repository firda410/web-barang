import sys
import json
import pandas as pd
import numpy as np
from sklearn.svm import SVR
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import make_pipeline
import warnings

# Mengabaikan peringatan untuk kebersihan output
warnings.filterwarnings('ignore', category=FutureWarning)
warnings.filterwarnings('ignore', category=UserWarning)

def train_and_predict(data):
    """
    Melatih model SVR dan memprediksi data untuk 12 bulan ke depan.

    Args:
        data (list): Daftar dictionary berisi data historis
                     [{'year': y, 'month': m, 'total_sales': s, 'total_profit': p}, ...]

    Returns:
        dict: Dictionary berisi prediksi penjualan dan keuntungan per bulan.
              Returns None jika data tidak cukup.
    """
    if not data or len(data) < 12: # Membutuhkan setidaknya 12 bulan data
        return None

    df = pd.DataFrame(data)
    df['date'] = pd.to_datetime(df['year'].astype(str) + '-' + df['month'].astype(str) + '-01')
    df = df.sort_values('date').reset_index(drop=True)

    # Buat 'time_index' sebagai fitur utama
    df['time_index'] = np.arange(1, len(df) + 1).reshape(-1, 1)

    X = df['time_index']
    y_sales = df['total_sales']
    y_profit = df['total_profit']

    # Buat dan latih model SVR (menggunakan pipeline untuk scaling)
    # Kernel 'rbf' (Radial Basis Function) seringkali bekerja baik untuk data non-linear.
    # C dan gamma adalah hyperparameter, mungkin memerlukan tuning untuk hasil optimal.
    c_value = 5000    # Naikkan C
    gamma_value = 'scale' # Coba 'scale' atau 0.01, 0.5, dll.
    epsilon_value = 2 # Biarkan default atau coba ubah sedikit

    svr_sales_model = make_pipeline(StandardScaler(), SVR(kernel='rbf', C=c_value, gamma=gamma_value, epsilon=epsilon_value))
    svr_profit_model = make_pipeline(StandardScaler(), SVR(kernel='rbf', C=c_value, gamma=gamma_value, epsilon=epsilon_value))

    svr_sales_model.fit(X, y_sales)
    svr_profit_model.fit(X, y_profit)

    # Siapkan 'time_index' untuk prediksi (12 bulan ke depan)
    last_time_index = X.iloc[-1][0]
    future_time_index = np.arange(last_time_index + 1, last_time_index + 13).reshape(-1, 1)

    # Lakukan prediksi
    predicted_sales = svr_sales_model.predict(future_time_index)
    predicted_profit = svr_profit_model.predict(future_time_index)

    # Pastikan prediksi tidak negatif
    predicted_sales = np.maximum(0, predicted_sales)
    predicted_profit = np.maximum(0, predicted_profit)

    # Format hasil prediksi
    predictions = {}
    for i in range(12):
        month_number = i + 1
        predictions[month_number] = {
            'predicted_sales': round(predicted_sales[i]),
            'predicted_profit': round(predicted_profit[i], 2)
        }

    return predictions

if __name__ == "__main__":
    try:
        # Baca data JSON dari standard input (dikirim oleh PHP)
        input_json = sys.stdin.read()
        historical_data = json.loads(input_json)

        # Lakukan training dan prediksi
        results = train_and_predict(historical_data)

        # Cetak hasil sebagai JSON ke standard output (untuk dibaca PHP)
        if results:
            print(json.dumps(results))
        else:
            # Kirim JSON kosong jika data tidak cukup
            print(json.dumps({}))

    except Exception as e:
        # Menangani error dan mengirimkannya sebagai output error
        # (bisa ditangkap oleh PHP jika diperlukan)
        print(json.dumps({"error": str(e)}), file=sys.stderr)
        sys.exit(1)