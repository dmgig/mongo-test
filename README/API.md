# API Documentation

The project includes a RESTful API to manage Parties and Relationships programmatically.

**Base URL**: `/api/v1`

## Parties

### List All Parties
Retrieves a list of all registered parties.

- **Endpoint**: `GET /parties`
- **Response**: `200 OK`
  ```json
  [
    {
      "_id": "uuid-string",
      "name": "Party Name",
      "type": "individual",
      "created_at": { ... }
    },
    ...
  ]
  ```

### Get Party Details
Retrieves details for a specific party, including their relationships.

- **Endpoint**: `GET /parties/{id}`
- **Response**: `200 OK`
  ```json
  {
    "party": { ... },
    "relationships": [
      {
        "_id": "...",
        "from_party_id": "...",
        "to_party_id": "...",
        "type": "employment",
        "status": "active"
      }
    ]
  }
  ```

### Create Party
Creates a new individual or organization.

- **Endpoint**: `POST /parties`
- **Body**:
  ```json
  {
    "name": "John Doe",
    "type": "individual"  // or "organization"
  }
  ```
- **Response**: `201 Created`
  ```json
  {
    "id": "new-uuid-string",
    "status": "created"
  }
  ```

### Update Party
Updates an existing party's information.

- **Endpoint**: `PATCH /parties/{id}`
- **Body**:
  ```json
  {
    "name": "New Name"
  }
  ```
- **Response**: `200 OK`
  ```json
  {
    "status": "updated"
  }
  ```

### Delete Party
Deletes a party. **Warning**: This cascades and deletes all relationships associated with the party.

- **Endpoint**: `DELETE /parties/{id}`
- **Response**: `200 OK`
  ```json
  {
    "status": "deleted"
  }
  ```

---

## Party Relationships

### Create Relationship
Links two existing parties together.

- **Endpoint**: `POST /party-relationships`
- **Body**:
  ```json
  {
    "from_party_id": "uuid-1",
    "to_party_id": "uuid-2",
    "type": "employment", // "employment", "membership", "association"
    "status": "active"    // "active", "inactive" (optional, default: active)
  }
  ```
- **Response**: `201 Created`
  ```json
  {
    "id": "new-rel-uuid",
    "status": "created"
  }
  ```

### Update Relationship
Updates the status of a relationship.

- **Endpoint**: `PATCH /party-relationships/{id}`
- **Body**:
  ```json
  {
    "status": "inactive"
  }
  ```
- **Response**: `200 OK`
  ```json
  {
    "status": "updated"
  }
  ```
