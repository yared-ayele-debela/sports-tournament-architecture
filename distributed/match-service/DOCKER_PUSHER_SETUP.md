# Docker Setup: Pusher WebSocket Configuration

This guide explains how to configure Pusher for WebSocket broadcasting when running the project in Docker.

## Configuration

The Pusher credentials have been added to `docker-compose.yml`. No additional configuration is needed if you're using the provided docker-compose.yml file.

### Backend (match-service)

The following environment variables are set in `docker-compose.yml`:

```yaml
environment:
  - BROADCAST_DRIVER=pusher
  - PUSHER_APP_ID=2116045
  - PUSHER_APP_KEY=8d9785b428b83e896400
  - PUSHER_APP_SECRET=35ab51339da676b7cd7a
  - PUSHER_APP_CLUSTER=eu
```

### Frontend (public-view)

The following environment variables are set in `docker-compose.yml`:

```yaml
environment:
  - VITE_PUSHER_APP_KEY=8d9785b428b83e896400
  - VITE_PUSHER_APP_CLUSTER=eu
  - VITE_PUSHER_ENCRYPTED=true
```

## Installation Steps

### 1. Install Pusher PHP SDK

Add to `match-service/composer.json` (if not already present):

```bash
cd match-service
composer require pusher/pusher-php-server
```

Or add it manually to `composer.json`:

```json
{
  "require": {
    "pusher/pusher-php-server": "^7.2"
  }
}
```

### 2. Rebuild Docker Containers

After updating docker-compose.yml, rebuild the containers:

```bash
# Stop existing containers
docker-compose down

# Rebuild with new environment variables
docker-compose build match-service public-view

# Start all services
docker-compose up -d
```

### 3. Clear Laravel Config Cache

Inside the match-service container:

```bash
# Enter the container
docker-compose exec match-service bash

# Clear config cache
php artisan config:clear
php artisan config:cache

# Exit container
exit
```

Or run it directly:

```bash
docker-compose exec match-service php artisan config:clear
docker-compose exec match-service php artisan config:cache
```

### 4. Rebuild Frontend (if needed)

If the frontend needs to rebuild with new environment variables:

```bash
# Enter the container
docker-compose exec public-view sh

# Rebuild (if using Vite)
npm run build

# Exit
exit
```

## Verification

### 1. Check Environment Variables

**Backend:**
```bash
docker-compose exec match-service php artisan tinker
>>> config('broadcasting.default')
=> "pusher"
>>> config('broadcasting.connections.pusher.key')
=> "8d9785b428b83e896400"
```

**Frontend:**
Check browser console when accessing `http://localhost:3001`. You should see:
```
Laravel Echo initialized successfully with Pusher
```

### 2. Test WebSocket Connection

1. Access the public view: `http://localhost:3001`
2. Navigate to Live Matches
3. Create a match and set status to `in_progress`
4. Add a match event (goal, card, etc.)
5. Check if updates appear in real-time

### 3. Check Pusher Dashboard

1. Go to https://dashboard.pusher.com
2. Select your app (ID: 2116045)
3. Check the "Debug Console" tab
4. You should see WebSocket connections and events

## Troubleshooting

### WebSocket Not Connecting

1. **Check browser console** for connection errors
2. **Verify environment variables** are set correctly:
   ```bash
   docker-compose exec match-service env | grep PUSHER
   docker-compose exec public-view env | grep VITE_PUSHER
   ```

3. **Check Pusher dashboard** for connection attempts
4. **Verify network connectivity** - Pusher requires internet access

### Events Not Broadcasting

1. **Check Laravel logs:**
   ```bash
   docker-compose logs match-service | grep -i broadcast
   ```

2. **Verify BROADCAST_DRIVER:**
   ```bash
   docker-compose exec match-service php artisan tinker
   >>> config('broadcasting.default')
   ```

3. **Check if events are being dispatched** in controllers
4. **Verify Pusher credentials** are correct

### Frontend Not Receiving Updates

1. **Check browser console** for Echo initialization
2. **Verify VITE_PUSHER_APP_KEY** is accessible:
   - Open browser DevTools
   - Check Network tab for WebSocket connections
   - Should connect to `ws-*.pusher.com`

3. **Check if channels are subscribed:**
   - Browser console should show channel subscriptions
   - Check Pusher dashboard for active channels

### Container Rebuild Issues

If environment variables don't seem to be taking effect:

```bash
# Stop all containers
docker-compose down

# Remove volumes (optional - be careful!)
# docker-compose down -v

# Rebuild without cache
docker-compose build --no-cache match-service public-view

# Start services
docker-compose up -d

# Check logs
docker-compose logs -f match-service public-view
```

## Production Considerations

For production deployment:

1. **Use environment-specific credentials:**
   - Create separate Pusher app for production
   - Use Docker secrets or environment files
   - Never commit credentials to git

2. **Update docker-compose.yml:**
   ```yaml
   environment:
     - PUSHER_APP_ID=${PUSHER_APP_ID}
     - PUSHER_APP_KEY=${PUSHER_APP_KEY}
     - PUSHER_APP_SECRET=${PUSHER_APP_SECRET}
   ```

3. **Use .env file:**
   Create `.env` file in project root:
   ```env
   PUSHER_APP_ID=your_production_app_id
   PUSHER_APP_KEY=your_production_key
   PUSHER_APP_SECRET=your_production_secret
   PUSHER_APP_CLUSTER=eu
   ```

4. **Update docker-compose.yml to use .env:**
   ```yaml
   env_file:
     - .env
   ```

## Quick Start

After updating docker-compose.yml:

```bash
# 1. Install Pusher PHP SDK (if not already installed)
docker-compose exec match-service composer require pusher/pusher-php-server

# 2. Clear config cache
docker-compose exec match-service php artisan config:clear

# 3. Restart services
docker-compose restart match-service public-view

# 4. Check logs
docker-compose logs -f match-service public-view
```

## Notes

- Pusher connections are made directly from the browser to Pusher servers (not through Docker)
- The backend only sends events to Pusher API
- No need to expose WebSocket ports in Docker (Pusher handles this)
- Frontend connects to Pusher using the public key (safe to expose)
