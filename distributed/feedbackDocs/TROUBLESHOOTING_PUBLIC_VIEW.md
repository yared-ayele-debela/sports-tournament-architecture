# Troubleshooting: Public View React App - Laravel Services Not Working

## Common Issues When Running Services Without Docker

### Issue: All Laravel services are not working when running the public-view React app

## Quick Diagnosis

1. **Check if services are running:**
   ```bash
   ./check-services.sh
   ```

2. **Check if ports are in use:**
   ```bash
   lsof -i :8002 -i :8003 -i :8004 -i :8005
   ```

3. **Test service endpoints manually:**
   ```bash
   curl http://localhost:8002/api/public/tournaments
   curl http://localhost:8003/api/public/teams
   curl http://localhost:8004/api/public/matches
   curl http://localhost:8005/api/public/standings
   ```

## Solutions

### 1. Services Are Not Running

**Problem:** The Laravel services are not started.

**Solution:** Start all services:

```bash
# Option 1: Use the start script
./start-services.sh

# Option 2: Start manually
cd tournament-service && php artisan serve --port=8002 &
cd team-service && php artisan serve --port=8003 &
cd match-service && php artisan serve --port=8004 &
cd results-service && php artisan serve --port=8005 &
```

### 2. Services Are Running But Not Responding

**Problem:** Services are running but returning errors or not accessible.

**Check:**
- Database connection: Ensure MySQL is running and databases exist
- Redis connection: Ensure Redis is running (if using cache/queues)
- Environment variables: Check `.env` files in each service

**Solution:**
```bash
# Check database connection in each service
cd tournament-service
php artisan tinker
# Then try: DB::connection()->getPdo();

# Check Redis
redis-cli ping
```

### 3. CORS Issues

**Problem:** Browser shows CORS errors in console.

**Check:** The services have CORS middleware configured, but verify:
- `PublicCorsMiddleware` is applied to public routes
- CORS headers are being sent

**Solution:** Check the middleware is registered in `bootstrap/app.php`:
```php
'public.cors' => \App\Http\Middleware\PublicCorsMiddleware::class,
```

And applied in `routes/api_public.php`:
```php
Route::middleware(['force.json', 'public.cors', 'public.rate.limit'])->group(function () {
    // routes
});
```

### 4. Database Not Set Up

**Problem:** Services can't connect to databases.

**Solution:**
```bash
# For each service, ensure database exists and migrations are run
cd tournament-service
php artisan migrate

cd ../team-service
php artisan migrate

cd ../match-service
php artisan migrate

cd ../results-service
php artisan migrate
```

### 5. Environment Configuration Issues

**Problem:** Services are misconfigured.

**Check each service's `.env` file:**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tournament_db  # or team_db, match_db, results_db
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis (if using)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Service URLs (for inter-service communication)
AUTH_SERVICE_URL=http://localhost:8001
TOURNAMENT_SERVICE_URL=http://localhost:8002
TEAM_SERVICE_URL=http://localhost:8003
MATCH_SERVICE_URL=http://localhost:8004
RESULTS_SERVICE_URL=http://localhost:8005
```

### 6. Port Conflicts

**Problem:** Ports 8002-8005 are already in use.

**Solution:**
```bash
# Find what's using the ports
lsof -i :8002
lsof -i :8003
lsof -i :8004
lsof -i :8005

# Kill the processes or use different ports
# Update vite.config.js and axios.js if changing ports
```

### 7. React App Configuration

**Problem:** React app is pointing to wrong URLs.

**Check:** `public-view/src/api/axios.js` should have:
```javascript
baseURL: import.meta.env.VITE_TOURNAMENT_SERVICE_URL || 'http://localhost:8002/api/public',
```

**Solution:** Create `.env` file in `public-view/`:
```env
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api/public
VITE_TEAM_SERVICE_URL=http://localhost:8003/api/public
VITE_MATCH_SERVICE_URL=http://localhost:8004/api/public
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api/public
```

Then restart the React dev server:
```bash
cd public-view
npm run dev
```

## Step-by-Step Setup (Without Docker)

1. **Start MySQL and Redis:**
   ```bash
   # MySQL (if not running as service)
   sudo systemctl start mysql
   
   # Redis
   redis-server
   # Or: sudo systemctl start redis
   ```

2. **Set up each Laravel service:**
   ```bash
   # For each service (tournament-service, team-service, match-service, results-service)
   cd tournament-service
   composer install
   cp .env.example .env  # if .env doesn't exist
   php artisan key:generate
   php artisan migrate
   ```

3. **Start all services:**
   ```bash
   ./start-services.sh
   ```

4. **Verify services are running:**
   ```bash
   ./check-services.sh
   ```

5. **Start React app:**
   ```bash
   cd public-view
   npm install
   npm run dev
   ```

## Browser Console Errors

### "Network Error" or "Failed to fetch"
- Services are not running
- Wrong URL in axios configuration
- Firewall blocking connections

### "CORS policy" errors
- CORS middleware not applied
- Check browser console for specific CORS error
- Verify `Access-Control-Allow-Origin` header in response

### "404 Not Found"
- Route doesn't exist
- Check `routes/api_public.php` in each service
- Verify route prefix: `/api/public/...`

### "500 Internal Server Error"
- Check Laravel logs: `storage/logs/laravel.log`
- Database connection issue
- Missing environment variables

## Debugging Tips

1. **Check Laravel logs:**
   ```bash
   tail -f tournament-service/storage/logs/laravel.log
   ```

2. **Test endpoints with curl:**
   ```bash
   curl -v http://localhost:8002/api/public/tournaments
   ```

3. **Check service health endpoints:**
   ```bash
   curl http://localhost:8002/api/public/health
   ```

4. **Enable verbose logging in React:**
   - Check browser DevTools Network tab
   - Check Console for errors
   - Add console.log in axios interceptors

5. **Verify service routes:**
   ```bash
   cd tournament-service
   php artisan route:list | grep public
   ```

## Still Having Issues?

1. Check individual service README files
2. Verify all prerequisites are installed (PHP, Composer, MySQL, Redis)
3. Check service-specific logs
4. Ensure all migrations have been run
5. Verify database credentials in `.env` files
