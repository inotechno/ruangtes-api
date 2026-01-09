# Cara Update Postman Collection

Setiap kali ada endpoint baru yang dibuat, update file `RuangTes_API_Collection.json` dengan menambahkan request baru.

## Format Request Baru

```json
{
    "name": "Endpoint Name",
    "request": {
        "method": "GET|POST|PUT|PATCH|DELETE",
        "header": [
            {
                "key": "Accept",
                "value": "application/json"
            },
            {
                "key": "Authorization",
                "value": "Bearer {{auth_token}}"
            },
            {
                "key": "Content-Type",
                "value": "application/json"
            }
        ],
        "body": {
            "mode": "raw",
            "raw": "{\n    \"field\": \"value\"\n}"
        },
        "url": {
            "raw": "{{base_url}}/api/endpoint/path",
            "host": ["{{base_url}}"],
            "path": ["api", "endpoint", "path"]
        },
        "description": "Endpoint description"
    },
    "response": []
}
```

## Tips

1. Group endpoints berdasarkan fitur (Authentication, Dashboard, Tests, dll)
2. Gunakan variables: `{{base_url}}`, `{{auth_token}}`
3. Tambahkan description yang jelas
4. Include example request body untuk POST/PUT/PATCH
5. Update README.md dengan endpoint baru

