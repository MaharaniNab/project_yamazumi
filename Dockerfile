# ⚠️ DEPRECATED: This file is only for reference
#
# For Railway deployment, use: api/Dockerfile
# 
# Railway will auto-detect and use api/Dockerfile based on:
# - .railwayignore (ignores PHP/Node files)
# - railway.json (explicitly specifies api/Dockerfile)
# - railway.toml (backup configuration)
#
# This file intentionally left minimal to prevent PHP detection
# by Railway's Railpack auto-detection system.

FROM alpine:latest
RUN echo "This is a placeholder. Railway should use api/Dockerfile instead."
CMD ["echo", "Error: Railway used wrong Dockerfile. Check configuration."]


