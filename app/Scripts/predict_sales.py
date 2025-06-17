import pandas as pd
from sklearn.svm import SVR
from sklearn.preprocessing import StandardScaler
import mysql.connector
import json
import sys
from datetime import datetime
from dateutil.relativedelta import relativedelta
import numpy as np

def get_db_connection():
    # Ganti dengan kredensial database Anda
    return mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="app_barang_regresi",
    )

def prepare_data(months_of_history=12):
    try:
        conn = get_db_connection()
        query = f"""
            SELECT 
                DATE_FORMAT(tanggal_penjualan, '%Y-%m-01') as bulan,
                SUM(jumlah_terjual) as total_penjualan
            FROM penjualan
            GROUP BY bulan
            ORDER BY bulan DESC
            LIMIT {months_of_history}
        """
        df = pd.read_sql(query, conn)
        df['bulan'] = pd.to_datetime(df['bulan'])
        df = df.sort_values('bulan').reset_index(drop=True)
        return df
    except mysql.connector.Error as err:
        return json.dumps({"error": f"Database connection failed: {err}"})
    finally:
        if 'conn' in locals() and conn.is_connected():
            conn.close()

def create_features(df, window_size=6):
    df_copy = df.copy()
    for i in range(1, window_size + 1):
        df_copy[f'penjualan_sebelum_{i}_bulan'] = df_copy['total_penjualan'].shift(i)
    df_copy = df_copy.dropna().reset_index(drop=True)
    
    X = df_copy.drop(['bulan', 'total_penjualan'], axis=1)
    y = df_copy[['total_penjualan']] # Jaga y sebagai DataFrame
    
    return X, y

def predict_future(df, months_to_predict=6, window_size=6):
    if len(df) < window_size + 1: # Butuh data setidaknya untuk satu window + satu target
        return {"error": f"Not enough data. Need at least {window_size + 1} months of data."}

    X_train, y_train = create_features(df, window_size)

    if X_train.empty:
        return {"error": "Feature creation failed. Not enough data for windowing."}
    
    # ====================================================================
    # PERUBAHAN UTAMA: Tambahkan Data Scaling
    # ====================================================================
    scaler_X = StandardScaler()
    scaler_y = StandardScaler()

    # Scaling data training
    X_train_scaled = scaler_X.fit_transform(X_train)
    y_train_scaled = scaler_y.fit_transform(y_train)

    # Latih model dengan data yang sudah di-scaling
    # model = SVR(kernel='rbf', C=100, gamma=0.1, epsilon=.1)
    model = SVR(kernel='rbf', C=1000, gamma='auto')
    model.fit(X_train_scaled, y_train_scaled.ravel())
    
    # Ambil data terakhir untuk memulai prediksi (dalam skala asli)
    last_known_data = list(df['total_penjualan'].values[-window_size:])
    last_month = df['bulan'].max()
    
    predictions = []

    for _ in range(months_to_predict):
        # Siapkan fitur untuk prediksi (dalam urutan [t-1, t-2, ...])
        features_original_scale = np.array(last_known_data[::-1]).reshape(1, -1)
        
        # Scaling fitur prediksi menggunakan scaler_X yang sudah di-fit
        features_scaled = scaler_X.transform(features_original_scale)
        
        # Lakukan prediksi (hasilnya dalam skala kecil)
        next_prediction_scaled = model.predict(features_scaled)
        
        # Kembalikan hasil prediksi ke skala semula
        next_prediction_original = scaler_y.inverse_transform(next_prediction_scaled.reshape(1, -1))[0][0]
        
        # Tambahkan ke daftar hasil
        last_month += relativedelta(months=1)
        predictions.append({
            "bulan": last_month.strftime('%Y-%m-%d'),
            "prediksi_terjual": round(next_prediction_original)
        })
        
        # Update data terakhir dengan hasil prediksi baru (dalam skala asli)
        last_known_data.pop(0)
        last_known_data.append(next_prediction_original)
        
    return predictions

if __name__ == "__main__":
    history_months = int(sys.argv[1]) if len(sys.argv) > 1 else 12
    predict_months = int(sys.argv[2]) if len(sys.argv) > 2 else 6
    window = 6

    sales_data = prepare_data(months_of_history=history_months)
    
    if isinstance(sales_data, str): 
        print(sales_data)
    else:
        results = predict_future(sales_data, months_to_predict=predict_months, window_size=window)
        print(json.dumps(results, indent=4))