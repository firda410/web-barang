import pandas as pd
from sklearn.svm import SVR
from sklearn.preprocessing import StandardScaler
import mysql.connector
import json
import sys
from datetime import datetime
from dateutil.relativedelta import relativedelta
import numpy as np

# Fungsi koneksi database (tetap sama)
def get_db_connection():
    return mysql.connector.connect(
        host="127.0.0.1", user="root", password="", database="app_barang_regresi"
    )

def create_features(df, window_size=6):
    df_copy = df.copy()
    for i in range(1, window_size + 1):
        df_copy[f'penjualan_sebelum_{i}_bulan'] = df_copy['total_penjualan'].shift(i)
    df_copy = df_copy.dropna().reset_index(drop=True)
    X = df_copy.drop(['bulan', 'total_penjualan'], axis=1)
    y = df_copy[['total_penjualan']]
    return X, y

def evaluate_period(start_date_str, end_date_str, window_size=6):
    try:
        conn = get_db_connection()
        # Ambil semua data penjualan yang relevan (sejak 18 bulan terakhir dari end_date)
        query = f"""
            SELECT 
                DATE_FORMAT(tanggal_penjualan, '%Y-%m-01') as bulan,
                SUM(jumlah_terjual) as total_penjualan
            FROM penjualan
            WHERE tanggal_penjualan < '{end_date_str}'
            GROUP BY bulan
            ORDER BY bulan
        """
        all_sales = pd.read_sql(query, conn)
        all_sales['bulan'] = pd.to_datetime(all_sales['bulan'])
        
        evaluation_months = pd.to_datetime(pd.date_range(start=start_date_str, end=end_date_str, freq='MS'))
        
        predictions = []

        for target_month in evaluation_months:
            # Tentukan data training: semua data sebelum bulan target
            training_data = all_sales[all_sales['bulan'] < target_month]
            
            if len(training_data) < window_size + 1:
                predictions.append({"bulan": target_month.strftime('%Y-%m-%d'), "prediksi_terjual": None})
                continue
            
            # Buat fitur dan target dari data training
            X_train, y_train = create_features(training_data, window_size)
            
            # Scaling
            scaler_X = StandardScaler()
            scaler_y = StandardScaler()
            X_train_scaled = scaler_X.fit_transform(X_train)
            y_train_scaled = scaler_y.fit_transform(y_train)

            # Latih model HANYA dengan data historis
            model = SVR(kernel='rbf', C=1000, gamma='auto')
            model.fit(X_train_scaled, y_train_scaled.ravel())
            
            # Siapkan fitur untuk prediksi (6 bulan sebelum bulan target)
            prediction_features_raw = training_data.tail(window_size)['total_penjualan'].values[::-1]
            prediction_features_scaled = scaler_X.transform(prediction_features_raw.reshape(1, -1))
            
            # Prediksi
            prediction_scaled = model.predict(prediction_features_scaled)
            prediction_original = scaler_y.inverse_transform(prediction_scaled.reshape(1, -1))[0][0]
            
            predictions.append({
                "bulan": target_month.strftime('%Y-%m-%d'),
                "prediksi_terjual": round(prediction_original)
            })
            
        return predictions

    except Exception as e:
        return {"error": str(e)}
    finally:
        if 'conn' in locals() and conn.is_connected():
            conn.close()

if __name__ == "__main__":
    start_date = sys.argv[1] if len(sys.argv) > 1 else '2025-01-01'
    end_date = sys.argv[2] if len(sys.argv) > 2 else '2025-06-30'
    
    results = evaluate_period(start_date, end_date)
    print(json.dumps(results, indent=4))