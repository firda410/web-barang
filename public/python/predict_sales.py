import sys
import json
import numpy as np
import pandas as pd
from sklearn.svm import SVR
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import mean_absolute_error, mean_squared_error

# Ambil data JSON dari Laravel (input)
data = json.loads(sys.stdin.read())

# Konversi ke DataFrame
df = pd.DataFrame(data)

# Pastikan kolom tanggal dalam format datetime
df['tanggal_penjualan'] = pd.to_datetime(df['tanggal_penjualan'])
df['tanggal_penjualan'] = df['tanggal_penjualan'].map(pd.Timestamp.toordinal)

# Fitur (X) dan Target (Y)
X = df[['tanggal_penjualan']]
y = df['jumlah_terjual']

# Normalisasi fitur
scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

# Split data (80% train, 20% test)
X_train, X_test, y_train, y_test = train_test_split(X_scaled, y, test_size=0.2, random_state=42)

# Model SVR
model = SVR(kernel='rbf', C=100, gamma=0.1, epsilon=0.1)
model.fit(X_train, y_train)

# Evaluasi Model
y_pred = model.predict(X_test)
mae = mean_absolute_error(y_test, y_pred)
rmse = mean_squared_error(y_test, y_pred, squared=False)

# Prediksi untuk 30 hari ke depan
future_dates = np.array([df['tanggal_penjualan'].max() + i for i in range(1, 31)]).reshape(-1, 1)
future_dates_scaled = scaler.transform(future_dates)
future_predictions = model.predict(future_dates_scaled)

# Hasil prediksi dalam JSON
result = {
    "predictions": list(zip(future_dates.flatten().tolist(), future_predictions.tolist())),
    "mae": mae,
    "rmse": rmse
}

print(json.dumps(result))
