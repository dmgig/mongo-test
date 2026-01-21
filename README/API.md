
---

## Sources

### List All Sources
Retrieves a list of all stored sources (fetched web pages).

- **Endpoint**: `GET /sources`
- **Response**: `200 OK`
  ```json
  [
    {
      "_id": "uuid-string",
      "url": "https://...",
      "content": "<html>...",
      "http_code": 200,
      "accessed_at": { ... }
    },
    ...
  ]
  ```

### Get Source Details
Retrieves details for a specific source.

- **Endpoint**: `GET /sources/{id}`
- **Response**: `200 OK`
  ```json
  {
    "_id": "...",
    "url": "...",
    "content": "...",
    "http_code": 200,
    "accessed_at": { ... }
  }
  ```

### Create Source
Fetches content from a URL and stores it as a new source.

- **Endpoint**: `POST /sources`
- **Body**:
  ```json
  {
    "url": "https://example.com"
  }
  ```
- **Response**: `201 Created`
  ```json
  {
    "id": "new-source-uuid",
    "status": "created",
    "http_code": 200
  }
  ```

### Delete Source
Deletes a source.

- **Endpoint**: `DELETE /sources/{id}`
- **Response**: `200 OK`
  ```json
  {
    "status": "deleted"
  }
  ```
