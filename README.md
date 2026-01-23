# Mongo Webform Project

This project is a PHP application built with **Slim Framework** that demonstrates connecting to a MongoDB database using DDEV. It implements **Domain-Driven Design (DDD)** principles to model Parties (People/Organizations) and their Relationships.

## Tech Stack

- **PHP**: 8.3
- **Framework**: Slim 4
- **Database**: MongoDB
- **Environment**: DDEV (Docker)

## Prerequisites

- [DDEV](https://ddev.com/)
- Docker

## Setup

1. Start the DDEV environment:
   ```bash
   ddev start
   ```

2. Install PHP dependencies:
   ```bash
   ddev exec composer install
   ```

## Usage

### Web Interface

The application provides a simple web interface to manage Parties and Relationships.

1.  **Home** (`/`): Dashboard with links to all actions.
2.  **List Parties** (`/parties`):
    - View all registered parties with their Name and ID.
3.  **Create Party** (`/party/create`): 
    - Create a new **Individual** or **Organization**.
    - Returns a unique Party ID (UUID).
4.  **Create Relationship** (`/party/relationship/create`): 
    - Connect two Parties using their IDs.
    - Select relationship type (e.g., Employment, Membership).
    - Set status (Active/Inactive).
5.  **Delete Party** (`/party/delete`):
    - Delete a Party by ID.
    - **Warning**: Automatically deletes all associated relationships (Cascading Delete).
6.  **Breakdown Detail** (`/breakdown/{id}`):
    - View the detailed AI breakdown for a specific source.
    - Displays the raw YAML output for identified parties, locations, and timeline.

### Admin Panel

To inspect the data directly in MongoDB (Mongo Express), run:

```bash
ddev mongo-express
```

### API Access

The application exposes a JSON REST API at `/api/v1`. See [API Documentation](README/API.md) for details.

### CLI Tool

A Command Line Interface tool is available for managing the application.

- **Create Source**: Fetch and store a web page as a source.
  ```bash
  cli/unknown sources:create https://example.com
  ```
- **Source Breakdown**: Generates AI breakdown for a source, storing raw YAML results.
  ```bash
  cli/unknown source:breakdown <source-id> [--chunk-limit <limit>] [--retry]
  ```

## Architecture

The application follows a clean architecture separating the Web layer from the Domain layer.

### 1. Web Layer (Slim Framework)
- **Entry Point**: `www/index.php` handles all routing and HTTP requests.
- **Templates**: `templates/` contains plain PHP view files, keeping HTML separate from logic.

### 2. Domain Layer (DDD)
Located in `app/Domain/`, this layer contains the business logic, independent of the framework.

- **Party Domain**:
    - `Party`: The main Aggregate Root entity (Person or Organization).
    - `PartyRelationship`: Entity representing a link between two parties (e.g., "Works For").
    - **Value Objects**:
        - `PartyId`, `PartyRelationshipId`: Type-safe UUIDs.
        - `PartyType`, `PartyRelationshipType`: Enums for valid types.

- **Source Domain**:
    - `Source`: Entity representing external content (e.g., a web page) fetched and stored for analysis.
    - `SourceService`: Handles fetching content via HTTP (Guzzle) and persistence.

- **AI Domain**:
    - `AiModelInterface`: Abstraction for AI interactions.
    - `GeminiAdapter`: Implementation for Google Gemini.

### 3. Infrastructure Layer
- `MongoConnector`: Handles the connection to the MongoDB database using DDEV credentials.

## File Structure

- **app/**: Application source code.
  - **Domain/**: Business logic and entities.
  - **Infrastructure/**: Database connections.
- **www/**: Public web root.
  - `index.php`: Main application entry point.
- **cli/**: Command Line Interface.
  - `unknown`: CLI entry point.
  - `Commands/`: Console command classes.
- **templates/**: HTML views.
- **settings.php**: Configuration.
- **composer.json**: Dependencies.
