# GaritaApp - Visitor Management System

A comprehensive visitor management system for residential complexes and gated communities, built with Laravel 8.12 and containerized with Docker.

## Quick Start

### Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+
- 2GB+ RAM available

### Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd GaritaApp
   ```

2. **Make the management script executable:**
   ```bash
   chmod +x garita.sh
   ```

3. **Start the development environment:**
   ```bash
   ./garita.sh dev-setup
   ```

4. **Access the application:**
   - **API:** http://localhost:8080
   - **Admin Panel:** http://localhost:8081 (PhpMyAdmin)
   - **Default Login:** admin@garitaapp.com / admin123

## Management Commands

```bash
./garita.sh help         # Show all available commands
./garita.sh up           # Start all services
./garita.sh down         # Stop all services
./garita.sh logs         # View logs
./garita.sh shell        # Access application shell
./garita.sh mysql        # Access MySQL shell
./garita.sh health       # Check service health
```

## API Endpoints

### Authentication
- `POST /login` - User authentication
- `POST /logout` - User logout

### Residents
- `GET /resident/index` - List residents
- `POST /resident/save` - Create resident
- `GET /resident/edit/{id}` - Get resident details

### Visits
- `POST /visit/save` - Register new visit
- `GET /visit/detail` - Active visits
- `POST /visit/egreso` - Mark visit exit

### Reports
- `GET /visit/table/{from}/{to}` - Visits by date range
- `GET /visit/tablebyresident/{from}/{to}/{id}` - Visits by resident

## Features

- **Visitor Registration** with photo capture
- **Resident Management** with CSV import
- **Real-time Check-in/Check-out** tracking
- **Comprehensive Reporting** with date ranges
- **Photo Documentation** (visitor, license, vehicle)
- **Role-based Access Control**
- **CORS-enabled API** for frontend integration

## Architecture

- **Backend:** Laravel 8.12 with PHP 8.0-FPM
- **Database:** MySQL 8.0 with Redis caching
- **Web Server:** Nginx with rate limiting
- **Frontend:** Metronic theme with Bootstrap 4.6
- **Containerization:** Docker Compose

## Documentation

- **[Complete Setup Guide](DOCKER_SETUP.md)** - Detailed Docker setup instructions
- **[Application Context](context.md)** - Architecture and technical overview

## Database Access

- **Host:** localhost:3306
- **Database:** garitaapp
- **Username:** garita_user
- **Password:** garita_password

## Support

For issues:
1. Check logs: `./garita.sh logs`
2. Verify health: `./garita.sh health`
3. Review documentation in `DOCKER_SETUP.md`

## License

This project is proprietary software for MARDYSA.
