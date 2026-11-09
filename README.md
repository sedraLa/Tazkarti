# Tazkarti API

Tazkarti is a high-concurrency event ticketing and reservation REST API built with Laravel 12. The platform manages the entire ticketing lifecycle—from event discovery and inventory holds to concurrent booking confirmations, simulated refunds, event cancellations, bulk announcements, and asynchronous multi-channel notifications.

---

## Table of Contents

- [Features](#features)
- [Technologies](#technologies)
- [Architecture Overview](#architecture-overview)
- [Installation & Setup](#installation--setup)
- [Running the Application](#running-the-application)
- [Authentication & Roles](#authentication--roles)
- [API Documentation](#api-documentation)
- [Queue & Horizon Architecture](#queue--horizon-architecture)
- [Caching Strategy](#caching-strategy)
- [Simulated Providers](#simulated-providers)
- [Project Structure](#project-structure)

---

## Features

- **API-Only Architecture**: Clean JSON REST API with form request validation, policy authorization, and API resource transformation.
- **Temporary Inventory Holds**: 10-minute reservation holds with automatic delayed expiration jobs that release inventory if unconfirmed.
- **Concurrency & Deadlock Prevention**: Database row-level locking (`lockForUpdate`) on events and ticket types in sorted order (`sort()`).
- **Simulated Financial Transactions**: Idempotent payments and refunds tracked with `Refund` records and status transitions without external payment SDKs.
- **Decoupled Asynchronous Notifications**: Separate Email and SMS notification delivery jobs with unique delivery keys, atomic status leases, retry backoffs, and simulated failure testing.
- **Event Cancellation Pipeline**: Chunked background cancellation using `chunkById` over isolated bulk queues so high-volume cancellations never block live sales.
- **Bulk Announcements**: Chunked recipient delivery dispatched exclusively to the bulk queue with recipient-level duplicate protection.
- **Cache Invalidation**: Multi-level caching for available ticket quantities and paginated upcoming event searches with versioned cache busting.

---

## Technologies

- **PHP**: 8.2+
- **Framework**: Laravel 12
- **Database**: MySQL 8.0
- **Cache & Queue Driver**: Redis (via Predis client)
- **Queue Supervisor**: Laravel Horizon
- **Authentication**: Laravel Sanctum (Bearer Token)
- **Containerization**: Docker & Docker Compose (Redis service)

---

## Architecture Overview

```
                      +-------------------+
                      |   Client (HTTP)   |
                      +---------+---------+
                                |
                                v
                      +-------------------+
                      |  API Controllers  |
                      +---------+---------+
                                |
                   +------------+------------+
                   |                         |
                   v                         v
          +-----------------+       +-----------------+
          |  BookingService |       |   Redis Cache   |
          |  (DB Locks/Tx)  |       |  (Events / Qty) |
          +--------+--------+       +-----------------+
                   |
     +-------------+-------------+
     | (After Commit Events)     |
     v                           v
+------------------+    +-------------------+
| PurchaseConfirmed|    |  BookingCancelled |
+--------+---------+    +---------+---------+
         |                        |
         v                        v
+-------------------------------------------+
|          Laravel Horizon Queues           |
|                                           |
|  [supervisor-critical]                    |
|    - high                                 |
|    - reservations (Expire Reservations)   |
|                                           |
|  [supervisor-main]                        |
|    - default                              |
|    - notifications (Email / SMS Jobs)     |
|                                           |
|  [supervisor-bulk]                        |
|    - bulk (Event Cancels & Announcements) |
+-------------------------------------------+
```

---

## Installation & Setup

### Prerequisites

- PHP >= 8.2 (with `pdo_mysql`, `bcmath`, `ctype`, `mbstring`, `xml`, `curl` extensions)
- Composer
- MySQL Server (default port: 3306)
- Docker Desktop (for Redis container)

### 1. Clone & Install Dependencies

```bash
composer install
```

### 2. Environment Configuration

Copy the example environment configuration file:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Tazkarti
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

EMAIL_PROVIDER=log
EMAIL_SIMULATE_FAILURE=false

SMS_PROVIDER=log
SMS_SIMULATE_FAILURE=false
```

### 3. Start Redis via Docker

A `docker-compose.yml` file is provided to run Redis:

```bash
docker compose up -d redis
```

Verify that Redis is listening on `127.0.0.1:6379`.

### 4. Run Migrations & Seeders

```bash
php artisan migrate --seed
```

The database seeder populates venues, published events, ticket types, and default users.

---

## Running the Application

### 1. HTTP Server

```bash
php artisan serve
```

The API will be accessible at `http://127.0.0.1:8000`.

### 2. Queue Workers & Laravel Horizon

> **Important Note for Windows Users**: Laravel Horizon relies on POSIX process control signals (`pcntl`), which are native to Linux and Unix systems. On Windows:
> - **Recommended**: Run Horizon inside WSL2 (Ubuntu):
>   ```bash
>   php artisan horizon
>   ```
> - **Native Windows Fallback**: For local development without WSL2, use Laravel's standard queue worker:
>   ```bash
>   php artisan queue:work redis --queue=high,reservations,default,notifications,bulk
>   ```

To gracefully restart Horizon after application code changes or deployments:

```bash
php artisan horizon:terminate
```

---

## Authentication & Roles

The API uses **Laravel Sanctum** token-based authentication. Pass the token in the `Authorization` header for protected endpoints:

```http
Authorization: Bearer <your-sanctum-token>
```

### User Roles

- **Admin (`admin`)**: System administration, feature/unfeature events, cancel any event/booking, send announcements for any event.
- **Organizer (`organizer`)**: Create and manage own events, ticket types, send announcements, cancel own events and bookings.
- **Customer (`customer`)**: Browse events, reserve tickets, confirm purchases, cancel own bookings.

### Default Seeded Users

All default seeded accounts use the password: `password123`.

| Role | Email | Purpose |
| :--- | :--- | :--- |
| **Admin** | `admin@tazkarti.com` | Full administrative control |
| **Organizer** | `organizer1@tazkarti.com` | Manages Red Sea Productions |
| **Organizer** | `organizer2@tazkarti.com` | Manages Nile Cultural Foundation |
| **Customer** | `customer1@tazkarti.com` | Test attendee account |

To generate a test token in Tinker:

```bash
php artisan tinker --execute="echo App\Models\User::where('email', 'customer1@tazkarti.com')->first()->createToken('test')->plainTextToken;"
```

---

## API Documentation

All API responses return JSON. Request headers should include:

```http
Accept: application/json
Content-Type: application/json
```

### Standard Response Structure

Paginated lists:
```json
{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 10, "total": 10 }
}
```

Validation errors (`422 Unprocessable Entity`):
```json
{
  "message": "نص الإعلان مطلوب.",
  "errors": {
    "message": [ "نص الإعلان مطلوب." ]
  }
}
```

---

### Endpoints Table

| Method | Endpoint | Auth | Description |
| :--- | :--- | :--- | :--- |
| **GET** | `/api/v1/events` | Public | List all published events (filterable by city, event_type, date) |
| **GET** | `/api/v1/events/upcoming` | Public | List upcoming published events (cached in Redis) |
| **GET** | `/api/v1/events/featured` | Public | List featured published events |
| **GET** | `/api/v1/events/{id}` | Public | Show published event details |
| **GET** | `/api/v1/bookings` | Customer | List authenticated user's bookings |
| **POST** | `/api/v1/bookings` | Customer | Create a 10-minute held reservation |
| **GET** | `/api/v1/bookings/{id}` | Auth | View booking details (owner or admin) |
| **POST** | `/api/v1/bookings/{id}/confirm` | Customer | Confirm and pay for a held booking |
| **POST** | `/api/v1/bookings/{id}/cancel` | Auth | Cancel booking (customer, event organizer, admin) |
| **DELETE**| `/api/v1/bookings/{id}` | Auth | Alias for cancelling a booking |
| **PATCH**| `/api/v1/admin/events/{id}/feature` | Admin | Toggle event featured status |
| **GET** | `/api/v1/organizer/events` | Organizer/Admin | List events owned by the organizer |
| **POST** | `/api/v1/organizer/events` | Organizer/Admin | Create a new event |
| **GET** | `/api/v1/organizer/events/{id}` | Organizer/Admin | View organizer's event details |
| **PUT** | `/api/v1/organizer/events/{id}` | Organizer/Admin | Update organizer's event |
| **DELETE**| `/api/v1/organizer/events/{id}` | Organizer/Admin | Delete event (or cancel if bookings exist) |
| **POST** | `/api/v1/organizer/events/{id}/cancel` | Organizer/Admin | Cancel event and trigger background refund pipeline |
| **POST** | `/api/v1/organizer/events/{id}/announcements` | Organizer/Admin | Send bulk announcement to ticket holders |
| **GET** | `/api/v1/organizer/events/{id}/ticket-types` | Organizer/Admin | List ticket types for an event |
| **POST** | `/api/v1/organizer/events/{id}/ticket-types` | Organizer/Admin | Create ticket type for an event |
| **PUT** | `/api/v1/organizer/events/{id}/ticket-types/{tt}` | Organizer/Admin | Update ticket type (nested route) |
| **DELETE**| `/api/v1/organizer/events/{id}/ticket-types/{tt}` | Organizer/Admin | Delete ticket type (nested route) |
| **PUT** | `/api/v1/organizer/ticket-types/{tt}` | Organizer/Admin | Update ticket type (shallow route) |
| **DELETE**| `/api/v1/organizer/ticket-types/{tt}` | Organizer/Admin | Delete ticket type (shallow route) |

---

### Example Payloads

#### 1. Create a Reservation (`POST /api/v1/bookings`)

Optional Header: `Idempotency-Key: <unique-string>`

```json
{
  "event_id": 1,
  "items": [
    {
      "ticket_type_id": 1,
      "quantity": 2
    }
  ]
}
```

#### 2. Confirm Booking (`POST /api/v1/bookings/{id}/confirm`)

No request body required. Moves status from `held` to `paid`, generates ticket codes, decrements reserved inventory, increments sold inventory, and dispatches confirmation notifications.

#### 3. Cancel Booking (`POST /api/v1/bookings/{id}/cancel`)

```json
{
  "reason": "Customer requested refund due to travel changes."
}
```

#### 4. Create Event (`POST /api/v1/organizer/events`)

```json
{
  "venue_id": 1,
  "title": "Cairo AI & Cloud Summit 2026",
  "description": "Annual gathering of backend and cloud architects.",
  "event_type": "Technology",
  "start_at": "2026-12-01 10:00:00",
  "end_at": "2026-12-02 18:00:00",
  "status": "published"
}
```

#### 5. Send Bulk Announcement (`POST /api/v1/organizer/events/{id}/announcements`)

```json
{
  "title": "Venue Gate Update",
  "message": "Please enter through Gate 4 starting at 5:00 PM."
}
```

---

## Queue & Horizon Architecture

Tazkarti organizes background workloads across 5 queues managed by 3 isolated Horizon supervisors:

```
+--------------------------------------------------------------------------------+
| supervisor-critical : queues = ['high', 'reservations']                        |
|   - Dedicated to critical operations & reservation expiration                   |
+--------------------------------------------------------------------------------+
| supervisor-main     : queues = ['default', 'notifications']                    |
|   - Handles transactional emails and SMS confirmations/cancellations           |
+--------------------------------------------------------------------------------+
| supervisor-bulk     : queues = ['bulk']                                        |
|   - Dedicated to event cancellations & bulk announcements                      |
+--------------------------------------------------------------------------------+
```

### Why Worker Isolation Matters

Bulk operations (like refunding 5,000 attendees when an event is cancelled or sending an announcement) are routed exclusively to `supervisor-bulk`. This prevents bulk background jobs from consuming capacity needed by `supervisor-critical` and `supervisor-main`, ensuring that live ticket purchases and reservation expirations remain instantaneous.

### Jobs Reference Table

| Job | Queue | Tries | Backoff | Timeout | Purpose |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **`ExpireBookingReservation`** | `reservations` | 3 | 5s | 30s | Expires held booking if 10-min window elapsed; restores reserved inventory |
| **`SendPurchaseConfirmationEmailJob`** | `notifications` | 3 | 5s | 30s | Delivers purchase confirmation email with atomic claim and idempotency |
| **`SendPurchaseConfirmationSmsJob`** | `notifications` | 3 | 5s | 30s | Delivers purchase confirmation SMS with ticket codes |
| **`SendBookingCancelledEmailJob`** | `notifications` | 3 | 5s | 30s | Delivers booking cancellation email |
| **`SendBookingCancelledSmsJob`** | `notifications` | 3 | 5s | 30s | Delivers booking cancellation SMS |
| **`CancelEventBookingsJob`** | `bulk` | 3 | — | 120s | Batches event bookings in chunks of 100 via `chunkById` |
| **`ProcessEventBookingCancellationJob`** | `bulk` | 3 | — | 60s | Executes cancellation, refund, and ticket revocation for a single booking |
| **`ProcessEventAnnouncementJob`** | `bulk` | 3 | — | 120s | Batches confirmed ticket holders in chunks of 100 via `chunkById` |
| **`SendAnnouncementNotificationJob`** | `bulk` | 3 | — | 30s | Sends announcement to attendee with duplicate-delivery prevention |

### Notification Delivery Tracking & Lease Recovery

Every transactional notification is logged in `notification_deliveries` under a unique composite key `(booking_id, channel, notification_type)`:
1. **Atomic Claim**: Workers use an atomic database update on `status = pending` or expired processing leases (`updated_at <= now() - 60s`) before contacting providers.
2. **Lease Protection**: If a duplicate worker attempts to process an active lease, it dynamically calculates remaining lease time `max(10, (60 - $elapsed) + 5)` and releases the job.
3. **Channel Independence**: Email failures do not affect SMS delivery. Provider failures update the delivery record to `failed` and do not roll back the database booking or refund.

---

## Caching Strategy

Caching is powered by Redis with automatic cache invalidation wired outside controllers through model lifecycle hooks and service commit events:

### 1. Available Ticket Quantities

- **Key**: `ticket_types:{id}:available_quantity`
- **TTL**: 10 minutes
- **Invalidated When**:
  - A reservation is created.
  - A reservation expires.
  - A booking is confirmed (moving quantity from reserved to sold).
  - A booking is cancelled or refunded.
  - Ticket types are created, updated, or deleted.

### 2. Upcoming Events List

- **Key**: `events:upcoming:v{version}:city:{city}:type:{type}:per_page:{per_page}:page:{page}`
- **TTL**: 10 minutes
- **Atomic Versioned Invalidation**: The atomic version counter `events:upcoming:version` is incremented on every event publication, update, cancellation, or ticket type mutation. This instantly invalidates all cached filters and pagination pages without requiring expensive key-scanning operations.

---

## Simulated Providers

To allow full integration and failure testing without third-party API keys:

- **Log Email Provider (`LogEmailProvider`)**: Writes email recipients, event titles, ticket counts, and idempotency keys to the application log (`storage/logs/laravel.log`).
- **Log SMS Provider (`LogSmsProvider`)**: Writes SMS recipient phone numbers, short text messages, and idempotency keys to `storage/logs/laravel.log`.
- **Failure Simulation**:
  - `EMAIL_SIMULATE_FAILURE=true` forces the email provider to throw exceptions to test retry logic and channel isolation.
  - `SMS_SIMULATE_FAILURE=true` forces the SMS provider to throw exceptions.
- **Payments & Refunds**: Payments and refunds are simulated via atomic database transactions, generating `Refund` records with status `completed` and timestamps without external payment gateways.

---

## Project Structure

```
app/
├── Enums/               # Status enums (BookingStatus, EventStatus, TicketStatus, UserRole, etc.)
├── Events/              # Domain events (PurchaseConfirmed, BookingCancelled, EventCancelled)
├── Http/
│   ├── Controllers/Api/V1/ # REST API Controllers (BookingController, EventController, etc.)
│   ├── Requests/        # FormRequests with Arabic validation rules and policy checks
│   └── Resources/       # API Resource transformers (BookingResource, EventResource, etc.)
├── Jobs/                # Queued background jobs (notifications, expirations, bulk cancellations)
├── Listeners/           # Discovered listeners dispatching jobs after database commits
├── Models/              # Eloquent models (Event, Booking, Ticket, TicketType, Refund, etc.)
├── Policies/            # Authorization policies (BookingPolicy, EventPolicy, TicketTypePolicy)
└── Services/
    ├── BookingService.php  # Core transaction & inventory logic
    ├── Email/           # EmailProviderInterface and LogEmailProvider
    └── Sms/             # SmsProviderInterface and LogSmsProvider
config/
├── horizon.php          # Supervisor configurations (critical, main, bulk)
├── queue.php            # Queue connections and Redis retry_after settings
└── services.php         # Provider configuration and simulation switches
database/
├── migrations/          # Schema migrations with foreign constraints and unique keys
└── seeders/             # Initial users, venues, events, and bookings
```

---


