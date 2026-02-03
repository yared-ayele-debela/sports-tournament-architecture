# Admin Dashboard - Sports Tournament Management

A comprehensive admin dashboard web application to manage a distributed sports tournament management system.

## Prerequisites

- Node.js (v18 or higher)
- npm or yarn
- All microservices running (auth-service, tournament-service, team-service, match-service, results-service)

## Setup Instructions

### 1. Install Dependencies

```bash
cd admin-dashboard
npm install
```

### 2. Configure Environment Variables

Create a `.env` file in the `admin-dashboard` directory (or copy from `.env.example`):

```env
VITE_AUTH_SERVICE_URL=http://localhost:8001/api
VITE_TOURNAMENT_SERVICE_URL=http://localhost:8002/api
VITE_TEAM_SERVICE_URL=http://localhost:8003/api
VITE_MATCH_SERVICE_URL=http://localhost:8004/api
VITE_RESULTS_SERVICE_URL=http://localhost:8005/api
```

### 3. Start Development Server

```bash
npm run dev
```

The application will be available at `http://localhost:3000`

## Testing the Application

### Step 1: Ensure All Services Are Running

Make sure all microservices are running:
- Auth Service: `http://localhost:8001`
- Tournament Service: `http://localhost:8002`
- Team Service: `http://localhost:8003`
- Match Service: `http://localhost:8004`
- Results Service: `http://localhost:8005`

### Step 2: Test Login

1. Open your browser and navigate to `http://localhost:3000`
2. You will be redirected to the login page
3. Use one of the test credentials:

**Test Users (from auth-service seeders):**
- **Admin User:**
  - Email: `admin1@test.com`
  - Password: `password`

- **Coach User:**
  - Email: `coach1@test.com`
  - Password: `password`

- **Referee User:**
  - Email: `referee1@test.com`
  - Password: `password`

### Step 3: Verify Authentication Flow

1. **Login Test:**
   - Enter email and password
   - Click "Sign In"
   - You should be redirected to the dashboard
   - Check browser console for any errors

2. **Token Storage Test:**
   - Open browser DevTools (F12)
   - Go to Application/Storage tab
   - Check Local Storage
   - You should see `access_token` and `user` stored

3. **Protected Route Test:**
   - Try accessing `http://localhost:3000/dashboard` directly
   - If not logged in, you should be redirected to login
   - After login, you should see the dashboard

4. **User Profile Test:**
   - After logging in, check the dashboard
   - You should see your user information:
     - Name
     - Email
     - Roles (if any)
     - Permissions (if any)

5. **Auto-logout Test:**
   - Manually delete the `access_token` from Local Storage
   - Try to navigate to a protected route
   - You should be redirected to login
   - Or make an API call that returns 401 - you should be auto-logged out

### Step 4: Test API Integration

Open browser DevTools Network tab and verify:

1. **Login Request:**
   - POST to `http://localhost:8001/api/auth/login`
   - Should return 200 with token and user data

2. **Get User Profile:**
   - GET to `http://localhost:8001/api/auth/me`
   - Should include `Authorization: Bearer {token}` header
   - Should return user with roles and permissions

3. **Logout Request:**
   - POST to `http://localhost:8001/api/auth/logout`
   - Should clear token from localStorage

## Troubleshooting

### Issue: Cannot connect to services

**Solution:** 
- Verify all microservices are running
- Check CORS settings in each service
- Verify the service URLs in `.env` are correct

### Issue: Login fails with 401

**Solution:**
- Verify the user exists in the database
- Check if the password is correct
- Ensure auth-service is running on port 8001

### Issue: Token not being sent in requests

**Solution:**
- Check browser Local Storage for `access_token`
- Verify the axios interceptor is working (check Network tab headers)
- Clear browser cache and try again

### Issue: CORS errors

**Solution:**
- Ensure all services have CORS enabled for `http://localhost:3000`
- Check service configuration files

## Development Commands

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Run linter
npm run lint
```

## Project Structure

```
admin-dashboard/
├── src/
│   ├── api/              # API service files
│   ├── components/        # Reusable components
│   ├── context/          # React context (AuthContext)
│   ├── lib/              # Utilities (API client, query client)
│   ├── pages/            # Page components
│   ├── App.jsx           # Main app component
│   └── main.jsx          # Entry point
├── .env                  # Environment variables
├── package.json
└── vite.config.js
```

## Next Steps

After testing authentication, you can proceed to implement:
- User Management
- Role & Permission Management
- Tournament Management
- Team & Player Management
- Match Management
- Results & Standings

## Support

For issues or questions, refer to the main project documentation or check the individual service README files.
