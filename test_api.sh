#!/bin/bash

# Test script to verify the API endpoint
echo "Testing System Resources API..."

# Test if server is running
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 | grep -q "200"; then
    echo "✅ Server is running"
else
    echo "❌ Server is not responding"
    exit 1
fi

# Test the API endpoint (this will require authentication)
echo "Testing API endpoint..."
echo "Note: This endpoint requires authentication, so we expect a 401 or redirect"

# Just check if the endpoint exists
curl -s -I http://localhost:8000/api/system/resources | head -1

echo "✅ Test completed. Check the output above."
echo "If you see a redirect (302) or authentication required (401), the endpoint exists."
echo "Visit http://localhost:8000 to test the dashboard with authentication."
