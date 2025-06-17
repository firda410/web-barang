import pandas as pd
from sklearn.svm import SVR
from sklearn.preprocessing import StandardScaler
import mysql.connector
import json
import sys
from datetime import datetime
import numpy as np

# Fungsi koneksi database
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

def predict_for_month(target_month_str, window_size=6):
    try:
        conn = get_db_connection()
        # Ambil semua data historis SEBELUM bulan target
        query = f"""
            SELECT 
                DATE_FORMAT(tanggal_penjualan, '%Y-%m-01') as bulan,
                SUM(jumlah_terjual) as total_penjualan
            FROM penjualan
            WHERE tanggal_penjualan < '{target_month_str}'
            GROUP BY bulan
            ORDER BY bulan
        """
        training_data = pd.read_sql(query, conn)
        training_data['bulan'] = pd.to_datetime(training_data['bulan'])

        if len(training_data) < window_size + 1:
            return {"error": "Not enough historical data to make a prediction."}

        X_train, y_train = create_features(training_data, window_size)
        
        scaler_X = StandardScaler()
        scaler_y = StandardScaler()
        X_train_scaled = scaler_X.fit_transform(X_train)
        y_train_scaled = scaler_y.fit_transform(y_train)

        model = SVR(kernel='rbf', C=1000, gamma='auto')
        model.fit(X_train_scaled, y_train_scaled.ravel())
        
        features_raw = training_data.tail(window_size)['total_penjualan'].values[::-1]
        features_scaled = scaler_X.transform(features_raw.reshape(1, -1))
        
        prediction_scaled = model.predict(features_scaled)
        prediction_original = scaler_y.inverse_transform(prediction_scaled.reshape(1, -1))[0][0]
        
        return {"prediksi_terjual": round(prediction_original)}

    except Exception as e:
        return {"error": str(e)}
    finally:
        if 'conn' in locals() and conn.is_connected():
            conn.close()

if __name__ == "__main__":
    target_month = sys.argv[1]
    results = predict_for_month(target_month)
    print(json.dumps(results, indent=4))