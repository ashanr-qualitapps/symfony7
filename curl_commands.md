# Symfony 7 Health Check API - cURL Commands

## 1. Health Check Endpoint
```bash
curl --location 'http://localhost:8000/api/health' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json'
```

## 2. Ping Endpoint  
```bash
curl --location 'http://localhost:8000/api/ping' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json'
```

## 3. Detailed Status Endpoint
```bash
curl --location 'http://localhost:8000/api/status' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json'
```

## PowerShell Alternative (Windows)

If cURL is not available, you can use PowerShell:

### Health Check
```powershell
Invoke-RestMethod -Uri 'http://localhost:8000/api/health' -Method Get -Headers @{
    'Accept' = 'application/json'
    'Content-Type' = 'application/json'
}
```

### Ping
```powershell
Invoke-RestMethod -Uri 'http://localhost:8000/api/ping' -Method Get -Headers @{
    'Accept' = 'application/json'
    'Content-Type' = 'application/json'
}
```

### Status
```powershell
Invoke-RestMethod -Uri 'http://localhost:8000/api/status' -Method Get -Headers @{
    'Accept' = 'application/json'
    'Content-Type' = 'application/json'
}
```

## Expected Responses

### Health Check Response
```json
{
  "status": "OK",
  "timestamp": "2025-08-28T09:57:00+00:00",
  "version": "1.0.0",
  "environment": "dev",
  "php_version": "8.2.12",
  "symfony_version": "7.0.10",
  "memory_usage": "12.5 MB",
  "uptime": "N/A"
}
```

### Ping Response
```json
{
  "message": "pong",
  "timestamp": "2025-08-28T09:57:00+00:00"
}
```

### Status Response
```json
{
  "overall_status": "healthy",
  "checks": {
    "database": {
      "status": "not_configured",
      "message": "Database not configured"
    },
    "cache": {
      "status": "healthy",
      "message": "Cache directory is writable",
      "cache_dir": "/path/to/cache"
    },
    "filesystem": {
      "status": "healthy",
      "details": {
        "var_directory_exists": true,
        "var_directory_writable": true,
        "log_directory_writable": true,
        "cache_directory_writable": true
      }
    }
  },
  "timestamp": "2025-08-28T09:57:00+00:00"
}
```

## Import into Postman

1. Open Postman
2. Click "Import" button
3. Select "Upload Files"
4. Choose the `postman_collection.json` file
5. Click "Import"

