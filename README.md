# Mongo Webform Project

This project is a simple PHP application that demonstrates connecting to a MongoDB database using DDEV. It includes a web form that captures user input and saves it to a MongoDB collection.

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

- **Form Submission**: Navigate to `/index.php` (e.g., `https://unknown.ddev.site/index.php`). This page presents a form to enter a Name and Type.
- **Results**: Submitting the form posts data to `results.php`, which inserts the record into the MongoDB `submissions` collection and displays the result.
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
  - `index.php`: The input form.
  - `results.php`: Processes form submissions and saves to MongoDB.
  - `mongo_test.php`: Simple script to test MongoDB connectivity.
- **settings.php**: Global settings and autoloader inclusion.
- **composer.json**: Project dependencies and autoloader configuration.
