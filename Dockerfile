# ⚠️ DEPRECATED: This Dockerfile is for reference only.
#
# The actual Dockerfile for Railway deployment is located at:
#   👉 api/Dockerfile
#
# The Python Flask API for video analysis/Yamazumi is deployed from:
#   - dockerfile: api/Dockerfile
#   - requirements: api/requirements.txt
#   - main app: api/index.py
#
# For Railway deployment configuration, see: railway.toml
#
# If you're looking for the Rails/Laravel application:
# - It is NOT deployed to Railway (use separate service if needed)
# - Main app files in root directory with composer.json
#
# To deploy to Railway:
# 1. Go to your Railway project dashboard
# 2. New Service → GitHub
# 3. Select this repository
# 4. Configure root directory: Leave as default (root)
# 5. Railway will automatically detect api/Dockerfile from railway.toml

