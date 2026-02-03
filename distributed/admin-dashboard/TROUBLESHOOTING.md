# Troubleshooting Guide

## Login Issues

### "Invalid credentials" Error

If you're getting "Invalid credentials" when trying to log in, it means the user doesn't exist in the database. You need to seed the database first.

#### Solution: Run Database Seeders

1. Navigate to the auth-service directory:
   ```bash
   cd auth-service
   ```

2. Run the database migrations (if not already done):
   ```bash
   php artisan migrate
   ```

3. Run the database seeders to create test users:
   ```bash
   php artisan db:seed
   ```

   Or run specific seeders:
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=PermissionSeeder
   php artisan db:seed --class=RolePermissionSeeder
   php artisan db:seed --class=UserSeeder
   ```

4. After seeding, you can use these test credentials:
   - **Admin**: `admin1@test.com` / `password`
   - **Coach**: `coach1@test.com` / `password`
   - **Referee**: `referee1@test.com` / `password`

### Verify User Exists

You can verify if a user exists by checking the database or using the registration endpoint:

```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Admin",
    "email": "admin1@test.com",
    "password": "password",
    "password_confirmation": "password"
  }'
```

### Other Common Issues

#### Network Error
- **Symptom**: "Network error: Unable to connect to the server"
- **Solution**: 
  - Verify auth-service is running on port 8001
  - Check if the service URL in `.env` is correct: `VITE_AUTH_SERVICE_URL=http://localhost:8001/api`

#### CORS Error
- **Symptom**: CORS errors in browser console
- **Solution**: 
  - Ensure CORS is enabled in auth-service for `http://localhost:3000`
  - Check `config/cors.php` in auth-service

#### Page Refreshes on Submit
- **Symptom**: Page refreshes when submitting login form
- **Solution**: 
  - Check browser console for errors
  - Verify the form has `e.preventDefault()` in handleSubmit
  - Check if axios interceptor is causing redirects
