# ============================================================
# Dockerfile — Flask Python API (Yamazumi)
# Railway akan otomatis pakai file ini, mengabaikan Railpack
# ============================================================

FROM python:3.11-slim

# Install system dependencies untuk OpenCV & MediaPipe
RUN apt-get update && apt-get install -y \
    libgl1 \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy requirements dulu (cache layer)
COPY api/requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy hanya folder api/ (tidak ikut composer.json, dll)
COPY api/ .

# Buat folder yang dibutuhkan Flask
RUN mkdir -p uploads static

# Inisialisasi database SQLite saat build
RUN python -c "from index import app, db; \
    app.app_context().push(); \
    db.create_all(); \
    print('Database initialized.')"

EXPOSE 8000

CMD ["gunicorn", "index:app", "--bind", "0.0.0.0:8000", "--workers", "2", "--timeout", "300"]
