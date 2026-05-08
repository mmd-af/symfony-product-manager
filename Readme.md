# Symfony Product Manager

A product management application built with **Symfony 7** to demonstrate backend engineering best practices.

## Architecture

- **Service Layer** — business logic isolated from controllers
- **Repository Pattern** — with interface contracts for testability
- **REST API** — JSON endpoints alongside web interface
- **Security** — session-based auth for web, stateless firewall for API

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Symfony 7 |
| ORM | Doctrine |
| Database | MySQL 8.0 |
| Web Server | Nginx |
| Container | Docker |
| Testing | PHPUnit 13 |
| Static Analysis | PHPStan (level 5) |

## Requirements

- Docker (includes Docker Compose)
- Make

## Quick Start

```bash
git clone https://github.com/mmd-af/symfony-product-manager.git
cd symfony-product-manager
make setup
```

This single command will:
- Start all Docker containers
- Install dependencies
- Run database migrations
- Load category fixtures
- Set up test database
- Run PHPStan static analysis
- Run PHPUnit test suite

Then visit **http://localhost**

## Available Commands

```bash
make setup     # Full setup from scratch
make test      # Run PHPUnit tests
make phpstan   # Run static analysis
make migrate   # Run database migrations
make shell     # Enter PHP container
make start     # Start containers
make stop      # Stop containers
make cache     # Clear Symfony cache
```

## API Endpoints

All endpoints return JSON. No authentication required.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/products` | List all products |
| GET | `/api/products/{id}` | Get single product |
| POST | `/api/products` | Create product |
| PUT | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product |

**Example request:**
```bash
curl -X POST http://localhost/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Laptop","price":"999.99","category_id":1}'
```

## Project Structure

```
symfony-product-manager/
├── docker/
│   ├── Dockerfile
│   └── nginx.conf
├── src/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── src/
│   │   ├── Contract/          # Repository interfaces
│   │   ├── Controller/
│   │   │   ├── Api/           # REST API controllers
│   │   │   ├── ProductController.php
│   │   │   ├── RegistrationController.php
│   │   │   └── SecurityController.php
│   │   ├── DataFixtures/
│   │   ├── Entity/
│   │   ├── Form/
│   │   ├── Repository/
│   │   └── Service/
│   └── tests/
│       ├── Controller/
│       │   └── Api/
│       ├── Service/           # Unit tests
│       ├── LoginControllerTest.php
│       └── RegistrationControllerTest.php
├── Makefile
└── docker-compose.yml
```
## Testing

```bash
make test
```

31 tests, 75 assertions covering:
- Unit tests for `ProductService` with mocked repository
- Integration tests for web controllers with authentication
- Integration tests for all REST API endpoints

## Author

Mohammad Afshar — [m-afshar.de](https://m-afshar.de) · [github.com/mmd-af](https://github.com/mmd-af)