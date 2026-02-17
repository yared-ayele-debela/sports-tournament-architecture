# How to View OpenAPI Documentation

This guide shows you multiple ways to view and interact with your OpenAPI specifications.

## Quick Options (No Installation Required)

### 1. **Swagger Editor (Online)**
- **URL**: https://editor.swagger.io/
- **Steps**:
  1. Open https://editor.swagger.io/
  2. Click "File" → "Import file" or paste your YAML content
  3. Or use the URL: `https://editor.swagger.io/?url=<your-openapi-url>`
- **Best for**: Quick viewing and editing

### 2. **Redoc (Online)**
- **URL**: https://redocly.github.io/redoc/
- **Steps**:
  1. Open https://redocly.github.io/redoc/
  2. Paste your OpenAPI YAML content in the text area
  3. Click "Render" to view
- **Best for**: Beautiful, readable documentation

### 3. **Swagger UI (Online)**
- **URL**: https://petstore.swagger.io/
- **Steps**:
  1. Open https://petstore.swagger.io/
  2. Click the "Explore" button
  3. Enter your OpenAPI file URL or paste YAML content
- **Best for**: Interactive API testing

## File Locations

Your OpenAPI files are located at:
- `auth-service/openapi.yaml`
- `match-service/openapi.yaml`
- `results-service/openapi.yaml`
- `team-service/openapi.yaml`
- `tournament-service/openapi.yaml`
