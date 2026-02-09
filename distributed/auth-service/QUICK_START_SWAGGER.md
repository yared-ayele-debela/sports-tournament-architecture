# Quick Start: View Swagger API Documentation

## ✅ Setup Complete!

The Swagger UI has been integrated into your Laravel application.

## 🚀 Access the Documentation

Start your Laravel server:
```bash
cd auth-service
php artisan serve --port=8001
```

Then open your browser and navigate to:
```
http://localhost:8001/api/docs
```

## 📋 What You'll See

- **Interactive API Documentation** - Browse all endpoints
- **Try It Out** - Test endpoints directly from the browser
- **Request/Response Examples** - See example payloads
- **Authentication** - Test with Bearer tokens
- **Schema Definitions** - View all data models

## 🔑 Testing Authentication

1. **Get a token** by using the `/auth/login` or `/auth/register` endpoint
2. **Click "Authorize"** button at the top of Swagger UI
3. **Enter your token**: `Bearer {your_token_here}`
4. **Click "Authorize"** and then "Close"
5. Now all protected endpoints will use your token automatically!

## 🎨 Alternative: Redoc (Beautiful UI)

If you prefer a different UI style, you can also use Redoc:

1. Add this route to `routes/web.php`:
```php
Route::get('/api/docs/redoc', function () {
    return view('redoc');
})->name('api.docs.redoc');
```

2. Create `resources/views/redoc.blade.php`:
```blade
<!DOCTYPE html>
<html>
<head>
    <title>Auth Service API Documentation</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">
    <style>body { margin: 0; padding: 0; }</style>
</head>
<body>
    <redoc spec-url='/api/docs/openapi.yaml'></redoc>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
```

3. Access at: `http://localhost:8001/api/docs/redoc`

## 📝 Files Created

- `app/Http/Controllers/ApiDocumentationController.php` - Controller to serve Swagger UI
- Routes added to `routes/web.php` - `/api/docs` and `/api/docs/openapi.yaml`
- `SWAGGER_SETUP.md` - Complete setup guide with all options

## 🔄 Updating Documentation

When you update `openapi.yaml`, the changes will automatically appear in Swagger UI after refreshing the page.

## 💡 Tips

- **Deep Linking**: Swagger UI supports deep linking - you can share direct links to specific endpoints
- **Export**: Use the "Download" button to export the OpenAPI spec
- **Try It Out**: Click "Try it out" on any endpoint to test it directly
- **Schema**: Click on response schemas to see detailed field descriptions

## Troubleshooting

**404 Error?**
- Make sure `openapi.yaml` exists in the `auth-service` root directory
- Check that the server is running on port 8001

**CORS Issues?**
- Swagger UI loads from CDN, so CORS shouldn't be an issue
- If testing from a different domain, you may need to configure CORS in Laravel

**Token Not Working?**
- Make sure you include "Bearer " prefix: `Bearer your_token_here`
- Check that the token hasn't expired
- Verify the token is valid by testing with curl first

## 📚 More Information

See `SWAGGER_SETUP.md` for:
- Alternative setup methods
- L5-Swagger package integration
- Static HTML generation
- Docker-based solutions
