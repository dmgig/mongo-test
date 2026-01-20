# Mongo Webform Project

This project is a PHP application built with **Slim Framework** that demonstrates connecting to a MongoDB database using DDEV. It uses Domain-Driven Design (DDD) principles.

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

- **Home**: `/` - Links to available actions.
- **Create Party**: `/party/create` - Create a new Person or Organization.
- **Create Relationship**: `/party/relationship/create` - Link two parties (e.g., Employment).
- **Connection Test**: Access `/mongo_test.php` to verify the database connection.

### Admin Panel

To access the MongoDB Admin Panel (Mongo Express), run:

```bash
ddev mongo-express
```

## File Structure

- **app/**: Application code.
  - **Domain/**: Contains business logic and entities.
    - `Party/`: The Party domain.
      - `Party.php`: The main entity.
      - `PartyRelationship.php`: Manages links between parties.
  - **Infrastructure/**: Infrastructure concerns.
    - `Mongo/MongoConnector.php`: Handles MongoDB connection logic.
- **www/**: Public-facing PHP scripts.
  - `index.php`: The main application entry point (Slim Framework).
  - `mongo_test.php`: Simple script to test MongoDB connectivity.
- **templates/**: HTML templates for the forms.
- **settings.php**: Global settings and autoloader inclusion.
- **composer.json**: Project dependencies and autoloader configuration.
