# Film API with Kinopoisk Integration

## Overview
This API provides access to film data from the database and integrates with the Kinopoisk API to provide additional movie information. The API allows clients to request specific data types for each film.

## Features
- Database film information (rental_duration, rental_rate, replacement_cost)
- Integration with Kinopoisk API for additional movie details
- Support for multiple data types: details, reviews, persons, similar, images, rating
- Error handling for third-party API requests

## Endpoints

### GET /films/{id}/details
Returns film information from database and additional data from Kinopoisk API based on the `data` parameter.

#### Parameters
- `id` (path): Film ID in the database
- `data` (query): Comma-separated list of data types to fetch. Possible values:
  - `details` - Film details from Kinopoisk
  - `reviews` - Film reviews from Kinopoisk
  - `persons` - Film cast and crew from Kinopoisk
  - `similar` - Similar films from Kinopoisk
  - `images` - Film images (cover type) from Kinopoisk
  - `rating` - Film ratings from Kinopoisk

#### Example Requests
- `/films/1/details?data=details` - Get basic film details
- `/films/1/details?data=reviews,persons` - Get reviews and persons
- `/films/1/details?data=details,rating,images` - Get multiple data types

## Database Structure
The application uses migrations to ensure database tables exist. SQL migration files are located in the `resources/sql/` directory:
- `film.sql` - Film information
- `customer.sql` - Customer information
- `rental.sql` - Rental information
- `store.sql` - Store information

## Implementation Details

### Migration System
The migration system checks for existing tables and runs corresponding SQL scripts if tables are missing. This ensures the database is properly set up on application startup.

### Kinopoisk Integration
The application connects to the Kinopoisk API using the provided API key to fetch additional movie information. It searches for movies by title and retrieves requested data types.

### Error Handling
Proper error handling is implemented for both database operations and third-party API requests. When Kinopoisk API is unavailable or returns errors, the application provides appropriate error messages.

## Setup Instructions
1. Ensure your database connection settings are correct in `config.php`
2. Install dependencies: `composer install`
3. The migration system will automatically create tables if they don't exist
4. Add the correct API key to the configuration

## Security Considerations
- API keys are stored as constants in the configuration file
- Input validation is performed on all parameters
- SQL queries use prepared statements to prevent injection attacks