# Auth Service

Authentication and Authorization Service for the Sports Tournament Architecture. This service handles user authentication, role-based access control (RBAC), and permission management.

## Features

- **User Authentication**: Registration, login, logout, and token management using Laravel Passport
- **Role-Based Access Control (RBAC)**: Manage roles and assign them to users
- **Permission Management**: Create and manage permissions, assign them to roles
- **User Management**: Complete CRUD operations for user management
- **RESTful API**: Standardized JSON responses with consistent error handling
- **Event-Driven**: Publishes events to message queue for other services
- **Health Monitoring**: Health check endpoints for service monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Authentication**: Laravel Passport (OAuth2)
- **Database**: MySQL/PostgreSQL (configurable)
- **Queue**: Redis/RabbitMQ (for event publishing)

## API Endpoints

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication Endpoints

#### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register a new user |
| POST | `/auth/login` | User login (returns access token) |
| GET | `/health` | Health check endpoint |
| GET | `/info` | Service information |

#### Protected Endpoints (Requires Bearer Token)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/logout` | Logout current user |
| POST | `/auth/refresh` | Refresh access token |
| GET | `/auth/me` | Get authenticated user profile with roles and permissions |

### User Management CRUD Endpoints

All endpoints require authentication (Bearer token).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/users` | List all users (with pagination and search) |
| GET | `/admin/users/{id}` | Get user details with roles and permissions |
| POST | `/admin/users` | Create a new user |
| PUT/PATCH | `/admin/users/{id}` | Update user |
| DELETE | `/admin/users/{id}` | Delete user |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by name or email

**Request Body (Create/Update):**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "password123",
    "role_ids": [1, 2]
}
```

### Role Management CRUD Endpoints

All endpoints require authentication (Bearer token).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/roles` | List all roles (with pagination and search) |
| GET | `/admin/roles/{id}` | Get role details with permissions and users |
| POST | `/admin/roles` | Create a new role |
| PUT/PATCH | `/admin/roles/{id}` | Update role |
| DELETE | `/admin/roles/{id}` | Delete role |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by name or description

**Request Body (Create/Update):**
```json
{
    "name": "admin",
    "description": "Administrator role with full access",
    "permission_ids": [1, 2, 3]
}
```

### Permission Management CRUD Endpoints

All endpoints require authentication (Bearer token).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/permissions` | List all permissions (with pagination and search) |
| GET | `/admin/permissions/{id}` | Get permission details with roles |
| POST | `/admin/permissions` | Create a new permission |
| PUT/PATCH | `/admin/permissions/{id}` | Update permission |
| DELETE | `/admin/permissions/{id}` | Delete permission |

**Query Parameters (List endpoint):**
- `per_page` (optional): Number of items per page (default: 15)
- `search` (optional): Search by name or description

**Request Body (Create/Update):**
```json
{
    "name": "users.create",
    "description": "Permission to create users"
}
```

### Internal Service Endpoints

These endpoints are used by other microservices for user validation and role management.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users/{id}` | Get user details (internal) |
| GET | `/users/{id}/validate` | Validate user existence |
| POST | `/users/{id}/roles` | Assign role to user |
| GET | `/users/{id}/permissions` | Get user permissions |
| POST | `/users/validate` | Validate user by email |

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Resource created successfully",
    "data": {
        // Resource data
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."]
    },
    "error_code": "VALIDATION_ERROR",
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Paginated Response
```json
{
    "success": true,
    "message": "Users retrieved successfully",
    "data": [
        // Array of items
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15,
        "has_more": true,
        "has_previous": false
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

## Setup Instructions

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL
- Redis (for queues and cache)

### Installation

1. **Clone and navigate to the service directory**
```bash
cd auth-service
```

2. **Install dependencies**
```bash
composer install
```

3. **Copy environment file**
```bash
cp .env.example .env
```

4. **Generate application key**
```bash
php artisan key:generate
```

5. **Configure database in `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auth_service
DB_USERNAME=root
DB_PASSWORD=
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Install Passport**
```bash
php artisan passport:install
```

8. **Start the development server**
```bash
php artisan serve
```

The service will be available at `http://localhost:8000`

## Environment Variables

Key environment variables to configure:

```env
APP_NAME="Auth Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auth_service
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Database Schema

### Users Table
- `id`: Primary key
- `name`: User's full name
- `email`: Unique email address
- `password`: Hashed password
- `email_verified_at`: Email verification timestamp
- `created_at`, `updated_at`: Timestamps

### Roles Table
- `id`: Primary key
- `name`: Unique role name
- `description`: Role description
- `created_at`, `updated_at`: Timestamps

### Permissions Table
- `id`: Primary key
- `name`: Unique permission name
- `description`: Permission description
- `created_at`, `updated_at`: Timestamps

### Pivot Tables
- `role_user`: Many-to-many relationship between users and roles
- `permission_role`: Many-to-many relationship between roles and permissions

## Testing with Postman

A complete Postman collection is available for testing all endpoints:

**File**: `Auth-Service-CRUD.postman_collection.json`

### Import Instructions:
1. Open Postman
2. Click **Import**
3. Select `Auth-Service-CRUD.postman_collection.json`
4. Update the `base_url` variable if needed (default: `http://localhost:8000`)
5. Use the **Login** or **Register** endpoint to get an access token
6. The token will be automatically saved and used for protected endpoints

### Testing Workflow:
1. **Authenticate**: Use Login/Register to get a token
2. **Create Permissions**: Create permissions first
3. **Create Roles**: Create roles and assign permissions
4. **Create Users**: Create users and assign roles
5. **Test CRUD**: Test all CRUD operations

## Usage Examples

### Register a User
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Create a Permission (with token)
```bash
curl -X POST http://localhost:8000/api/v1/admin/permissions \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "users.create",
    "description": "Permission to create users"
  }'
```

### Create a Role (with token)
```bash
curl -X POST http://localhost:8000/api/v1/admin/roles \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "admin",
    "description": "Administrator role",
    "permission_ids": [1, 2, 3]
  }'
```

### List Users (with token)
```bash
curl -X GET "http://localhost:8000/api/v1/admin/users?per_page=10&search=john" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Security Features

- **Password Hashing**: All passwords are hashed using bcrypt
- **Token-Based Authentication**: Laravel Passport OAuth2 tokens
- **Input Validation**: All inputs are validated before processing
- **SQL Injection Protection**: Using Eloquent ORM with parameter binding
- **XSS Protection**: Laravel's built-in protection
- **CSRF Protection**: Enabled for web routes

## Event Publishing

The service publishes events to a message queue for other services:

- `user.registered`: When a new user registers
- `user.logged.in`: When a user logs in
- `user.role.assigned`: When a role is assigned to a user

## Error Responses

All error responses follow a standardized format:

```json
{
    "success": false,
    "message": "Error message",
    "error_code": "ERROR_CODE",
    "errors": { /* optional validation errors */ },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

### Common Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| `BAD_REQUEST` | 400 | Invalid request |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Access denied |
| `NOT_FOUND` | 404 | Resource not found |
| `RESOURCE_NOT_FOUND` | 404 | Resource not found (alternative) |
| `METHOD_NOT_ALLOWED` | 405 | HTTP method not allowed |
| `VALIDATION_ERROR` | 422 | Validation failed |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_SERVER_ERROR` | 500 | Server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

### Service-Specific Error Codes

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| `AUTH_TOKEN_INVALID` | 401 | Authentication token is invalid |
| `AUTH_TOKEN_EXPIRED` | 401 | Authentication token has expired |
| `AUTH_CREDENTIALS_INVALID` | 401 | Invalid login credentials |
| `AUTH_USER_NOT_FOUND` | 404 | User not found |
| `AUTH_EMAIL_ALREADY_EXISTS` | 422 | Email address already registered |

For a complete list of error codes, see [ERROR_CODES.md](../ERROR_CODES.md).

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
Follow PSR-12 coding standards.

### Queue Workers
If using queues, start the worker:
```bash
php artisan queue:work
```

## Contributing

1. Follow the existing code structure
2. Write tests for new features
3. Update documentation
4. Follow PSR-12 coding standards

## License

This service is part of the Sports Tournament Architecture project.
