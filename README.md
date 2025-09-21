# Flight Tracker API

A RESTful API for flight search and booking management built with PHP and MySQL.

## Quick Start

### Prerequisites
- PHP 8+
- MySQL
- Composer

### Installation

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Configure environment**
   Create `.env` file with your database credentials:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=flights_db
   DB_USER=root
   DB_PASS=your_password
   JWT_SECRET=your_jwt_secret_key
   ```

3. **Setup database**
   ```bash
   php migrate.php
   ```

4. **Start development server**
   ```bash
   php -S localhost:8000 -t public
   ```

The API will be available at `http://localhost:8000`

## API Documentation

### Base URL
```
http://localhost:8000
```

### Authentication
JWT tokens are required for protected endpoints. Include in Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

---

## Endpoints

### Health Check

#### `GET /ping`
**Response:**
```json
{
  "status": "ok",
  "env_db": "flights_db"
}
```

---

### Authentication

#### `POST /api/auth/register`
**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (201):**
```json
{
  "message": "Registered",
  "user_id": 1
}
```

#### `POST /api/auth/login`
**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expires_in": 14400,
  "user_id": 1
}
```

---

### Flight Search

#### `GET /api/flights`
**Parameters:**
- `origin` (required) - 3-letter IATA code (e.g., "DEL", "BOM", "BLR")
- `destination` (required) - 3-letter IATA code
- `date` (required) - YYYY-MM-DD format
- `passengers` (optional) - Number of passengers (default: 1)
- `limit` (optional) - Max results (default: 50)
- `offset` (optional) - Skip results (default: 0)
- `sort` (optional) - "price" or "departure" (default: "price")

**Example:**
```
GET /api/flights?origin=DEL&destination=BLR&date=2025-09-22&passengers=2
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 2,
      "airline": "Vistara",
      "airline_code": "UK",
      "flight_number": "UK502",
      "origin": "DEL",
      "destination": "BLR",
      "departure": "2025-09-20T12:53:00Z",
      "arrival": "2025-09-20T15:15:00Z",
      "duration": "2h 22m",
      "price": 5130.00,
      "available_seats": 107
    }
  ],
  "meta": {
    "count": 1
  }
}
```

---

### Bookings (Authenticated)

#### `POST /api/bookings`
**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request:**
```json
{
  "flight_id": 2,
  "seats": 2,
  "passengers": [
    {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+91-9876543210"
    },
    {
      "name": "Jane Doe",
      "email": "jane@example.com",
      "phone": "+91-9876543211"
    }
  ]
}
```

**Response (201):**
```json
{
  "booking": {
    "id": 1,
    "user_id": 1,
    "flight_id": 2,
    "seats_booked": 2,
    "confirmation": "BOOK-abc123def456-1640419200",
    "status": "CONFIRMED",
    "created_at": "2025-09-21T10:00:00Z",
    "total_price": 10260.00,
    "price_per_seat": 5130.00,
    "passengers": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91-9876543210"
      },
      {
        "name": "Jane Doe",
        "email": "jane@example.com",
        "phone": "+91-9876543211"
      }
    ]
  }
}
```

#### `GET /api/bookings/{id}`
**Headers:**
```
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "booking": {
    "id": 1,
    "user_id": 1,
    "flight_id": 2,
    "seats_booked": 2,
    "confirmation": "BOOK-abc123def456-1640419200",
    "status": "CONFIRMED",
    "created_at": "2025-09-21T10:00:00Z",
    "passengers": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91-9876543210"
      }
    ]
  }
}
```

---

## Available Airports

The system includes flights between major Indian airports:

| Code | City | Airport |
|------|------|---------|
| DEL | Delhi | Indira Gandhi International |
| BOM | Mumbai | Chhatrapati Shivaji |
| BLR | Bangalore | Kempegowda International |
| PNQ | Pune | Pune Airport |
| HYD | Hyderabad | Rajiv Gandhi International |

---

## Testing Examples

### 1. Register User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

### 2. Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}'
```

### 3. Search Flights
```bash
curl "http://localhost:8000/api/flights?origin=DEL&destination=BLR&date=2025-09-22&passengers=1"
```

### 4. Create Booking
```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "flight_id": 2,
    "seats": 1,
    "passengers": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91-9876543210"
      }
    ]
  }'
```

---

## Error Responses

All errors return JSON with consistent format:

```json
{
  "error": true,
  "message": "Error message description"
}
```

**Status Codes:**
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `409` - Conflict
- `500` - Server Error

---

## Postman Collection

Import `Flight_Tracker_API.postman_collection.json` for complete API testing with:
- Pre-configured requests
- Automatic token management
- Example responses
- Environment variables

---

## Database Schema

**Flights Table:**
- id, airline, airline_code, flight_number
- origin, destination (3-letter IATA codes)
- departure, arrival (datetime)
- price (decimal), available_seats (int)
- operational_days (JSON array)

**Users Table:**
- id, email (unique), password_hash, created_at

**Bookings Table:**
- id, user_id, flight_id
- passengers (JSON), seats_booked
- confirmation (unique), status ('CONFIRMED'/'CANCELLED'), created_at