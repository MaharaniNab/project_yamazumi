# 🚆 Railway Deployment Guide - Yamazumi API

## Project Structure
```
project_yamazumi/
├── api/                 # Python Flask API (deployed to Railway)
│   ├── index.py        # Main Flask application
│   ├── Dockerfile      # Docker configuration
│   ├── requirements.txt # Python dependencies
│   └── uploads/        # Video uploads directory
├── railway.toml        # Railway deployment configuration
└── ... (Laravel app)   # Not deployed to Railway
```

## Prerequisites
- Railway account: https://railway.app
- GitPush access to your repository

## Deployment Steps

### 1. Clear Railway Cache (if needed)
If you encounter build errors, clear the Railway build cache:
- Go to Railway Dashboard → Settings → Build → Clear Cache

### 2. Environment Variables to Set in Railway
Go to **Variables** tab in Railway dashboard and add these:

```
LARAVEL_URL=https://your-frontend-domain.com
FLASK_ENV=production
PYTHONUNBUFFERED=1
```

### 3. Configuration Details

#### Port Configuration
- Railway automatically assigns a `PORT` environment variable
- API listens on `0.0.0.0:8000` (when running locally with Flask)
- In production (Railway), Gunicorn uses the `PORT` env variable

#### Database
- **⚠️ Important**: SQLite database stored in container filesystem
- **Issue**: Database persists within a deployment but resets on redeploy
- **Solution for production**: 
  - Option 1: Use Railway PostgreSQL add-on instead of SQLite
  - Option 2: Implement periodic backups
  - Option 3: Add persistent storage volume in Railway

#### CORS Configuration
- Default: Allows all origins (`*`)
- Set `LARAVEL_URL` environment variable to restrict access
- Current: `LARAVEL_URL=os.environ.get('LARAVEL_URL', '*')`

### 4. Troubleshooting

#### Build Error: "requirements.txt not found"
**Cause**: Docker build context issue  
**Solution**: 
- ✅ Fixed in railway.toml → set `dockerfilePath = "api/Dockerfile"`
- ✅ Fixed in api/Dockerfile → use proper COPY path

#### Import Errors
**Common packages that need system libraries**:
- `opencv-python-headless` - requires libgl1, libglib2.0-0, libsm6, libxext6, libxrender-dev
- `mediapipe` - requires libgomp1
- ✅ All installed in Dockerfile

#### Database Not Persisting
**Cause**: SQLite file in ephemeral container filesystem  
**Solution**: Switch to PostgreSQL
- Add Railway PostgreSQL plugin
- Update `SQLALCHEMY_DATABASE_URI` in index.py to use PostgreSQL

### 5. Testing Deployment

#### Health Check
```bash
curl https://your-railway-domain.com/health
```
Response: `{"status": "ok", "service": "Yamazumi Flask API"}`

#### Upload Endpoint
```bash
curl -X POST https://your-railway-domain.com/api/upload \
  -F "file_list=@video.mp4" \
  -F "output_harian=120" \
  -F "mp_aktual=5"
```

#### Get Results
```bash
curl https://your-railway-domain.com/api/results/{job_id}
```

## Current Configuration Files

### railway.toml
```toml
[build]
builder = "dockerfile"
dockerfilePath = "api/Dockerfile"

[deploy]
startCommand = "gunicorn index:app --bind 0.0.0.0:$PORT --workers 2 --timeout 300"
healthcheckPath = "/health"
healthcheckTimeout = 30
restartPolicyType = "on_failure"
```

### api/Dockerfile
- Python 3.11-slim base image
- Installs all system dependencies for CV libraries
- Uses Gunicorn as WSGI server
- Health check endpoint configured
- Logs output to stdout (important for Railway monitoring)

### Key Dependencies (api/requirements.txt)
- Flask with CORS support
- OpenCV (headless)
- MediaPipe for pose detection
- Pandas + NumPy for data processing
- Matplotlib for chart generation
- Gunicorn for production serving

## Performance Tuning

### Gunicorn Configuration
Current: `--workers 2 --timeout 300`
- Adjust `workers` based on Railway plan (3 for starter, more for higher tiers)
- Timeout 300s for video processing (increase if processing videos > 5 minutes)

### Video Processing Limits
- Max upload: 500MB per request
- Recommended: Videos < 5 minutes for better responsiveness
- Consider implementing job queuing for large videos

## Monitoring

### Logs in Railway
```
Dashboard → Logs → View deployment logs
```
Check for:
- ✅ Gunicorn startup messages
- ❌ Database connection errors
- ❌ Missing dependencies
- ⚠️ CORS or request errors

### Metrics
- Railway Dashboard → Resource → Monitor CPU, Memory, Network

## Future Improvements

1. **Database Persistence**
   - [ ] Switch from SQLite to PostgreSQL
   - [ ] Enable Railway PostgreSQL plugin
   - [ ] Update connection string

2. **Scaling**
   - [ ] Implement background job queue (Celery + Redis)
   - [ ] Add caching layer (Redis)
   - [ ] Optimize CV processing

3. **Security**
   - [ ] Add API authentication/rate limiting
   - [ ] Implement proper CORS configuration
   - [ ] Add request validation

4. **Monitoring**
   - [ ] Setup error tracking (Sentry)
   - [ ] Add performance monitoring
   - [ ] Implement audit logging

---

**Last Updated**: April 20, 2026  
**Status**: ✅ Ready for deployment
