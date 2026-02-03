#!/bin/bash

# Test script to verify login API endpoint

AUTH_URL="${VITE_AUTH_SERVICE_URL:-http://localhost:8001/api}"

echo "Testing Login API..."
echo "URL: $AUTH_URL/auth/login"
echo ""

# Test login
echo "Attempting login with admin1@test.com..."
RESPONSE=$(curl -s -X POST "$AUTH_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin1@test.com",
    "password": "password"
  }')

echo "Response:"
echo "$RESPONSE" | jq . 2>/dev/null || echo "$RESPONSE"
echo ""

# Check if successful
if echo "$RESPONSE" | grep -q '"success":true'; then
  echo "✅ Login successful!"
  TOKEN=$(echo "$RESPONSE" | jq -r '.data.token // .data.access_token // .token' 2>/dev/null)
  if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo "Token received: ${TOKEN:0:30}..."
  fi
else
  echo "❌ Login failed"
  echo "Check the error message above"
fi
