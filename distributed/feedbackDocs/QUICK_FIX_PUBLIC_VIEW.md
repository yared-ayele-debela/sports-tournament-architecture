# Quick Fix: Public View - Services Not Working

## The Problem

When running the public-view React app without Docker, the Laravel services (tournament, team, match, results) are not accessible because they're not running.

## Quick Solution

### Step 1: Start All Services

```bash
# From the distributed/ directory
./start-services.sh
```

This will start:
- Tournament Service on port 8002
- Team Service on port 8003
- Match Service on port 8004
- Results Service on port 8005

### Step 2: Verify Services Are Running

```bash
./check-services.sh
```

You should see all services marked as "Running".

### Step 3: Start React App

```bash
cd public-view
npm run dev
```

## If Services Still Don't Work

### Check 1: Are services actually running?

```bash
# Check if processes are running
ps aux | grep "php artisan serve"

# Check if ports are listening
lsof -i :8002 -i :8003 -i :8004 -i :8005
```

### Check 2: Can you access services directly?

```bash
# Test each service
curl http://localhost:8002/api/public/tournaments
curl http://localhost:8003/api/public/teams
curl http://localhost:8004/api/public/matches
curl http://localhost:8005/api/public/standings
```

If these fail, the services aren't running or aren't configured correctly.

### Check 3: Database and Environment Setup

Each service needs:
1. `.env` file configured
2. Database created and migrated
3. Dependencies installed

```bash
# For each service (tournament-service, team-service, match-service, results-service)
cd tournament-service
composer install
php artisan migrate
php artisan serve --port=8002
```

### Check 4: Browser Console

Open browser DevTools (F12) and check:
- **Console tab**: Look for error messages
- **Network tab**: Check if requests are failing

The improved error handling will now show clear messages like:
- "❌ Tournament Service is not running or not accessible"
- "Please ensure the service is running on the correct port"

## Manual Service Start (Alternative)

If the script doesn't work, start each service manually in separate terminals:

```bash
# Terminal 1
cd tournament-service
php artisan serve --port=8002

# Terminal 2
cd team-service
php artisan serve --port=8003

# Terminal 3
cd match-service
php artisan serve --port=8004

# Terminal 4
cd results-service
php artisan serve --port=8005
```

## Stop Services

```bash
./stop-services.sh
```

Or manually:
```bash
pkill -f 'php artisan serve'
```

## Common Issues

| Issue | Solution |
|-------|----------|
| Port already in use | Kill the process using that port or use a different port |
| Database connection error | Check `.env` file and ensure MySQL is running |
| CORS errors | Services have CORS configured, but check browser console |
| 404 errors | Check routes in `routes/api_public.php` |
| 500 errors | Check Laravel logs in `storage/logs/laravel.log` |

## Need More Help?

See [TROUBLESHOOTING_PUBLIC_VIEW.md](./TROUBLESHOOTING_PUBLIC_VIEW.md) for detailed troubleshooting steps.
