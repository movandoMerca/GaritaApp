# GaritaApp Docker Setup Guide

This guide provides comprehensive instructions for setting up the GaritaApp backend with Docker, exposing it as an API for frontend applications.

## Overview

The Docker setup includes:
- **Laravel 8.12** application container with PHP 8.0-FPM
- **MySQL 8.0** database with initialization scripts
- **Nginx** reverse proxy with API-optimized configuration
- **Redis** for caching and sessions
- **PhpMyAdmin** for database management

## Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- At least 2GB RAM available
- Ports 8080, 8081, 3306, 6379 available

## Quick Start

1. **Clone and navigate to the project:**
   ```bash
   cd /path/to/GaritaApp
   ```

2. **Build and start the containers:**
   ```bash
   docker-compose up -d --build
   ```

3. **Wait for initialization (first run may take 3-5 minutes):**
   ```bash
   docker-compose logs -f app
   ```

4. **Access the application:**
   - **API Endpoint:** http://localhost:8080
   - **PhpMyAdmin:** http://localhost:8081
   - **Database:** localhost:3306

## Management Script

For easier Docker operations, use the included [`garita.sh`](garita.sh) script:

```bash
# Make script executable (first time only)
chmod +x garita.sh

# Show all available commands
./garita.sh help

# Quick development setup
./garita.sh dev-setup

# Quick production setup
./garita.sh prod-setup

# Common operations
./garita.sh up           # Start services
./garita.sh down         # Stop services
./garita.sh logs         # View logs
./garita.sh shell        # Access app shell
./garita.sh mysql        # Access MySQL shell
./garita.sh cache        # Clear cache
./garita.sh health       # Check service health
```

The script provides colored output and error checking, making Docker operations more user-friendly than raw docker-compose commands.

## Service Details

### Application Container (app)
- **Port:** Internal 9000 (PHP-FPM)
- **Environment:** Production-ready Laravel
- **Features:** Auto-migration, caching, queue workers
- **Logs:** `docker-compose logs app`

### Database Container (mysql)
- **Port:** 3306 (exposed)
- **Database:** garitaapp
- **Username:** garita_user
- **Password:** garita_password
- **Root Password:** root_password

### Web Server (nginx)
- **HTTP Port:** 8080
- **HTTPS Port:** 8443 (self-signed certificate)
- **Features:** CORS enabled, rate limiting, static file serving
- **Configuration:** `docker/nginx/sites/garita.conf`

### Cache (redis)
- **Port:** 6379 (exposed)
- **Usage:** Sessions, application cache
- **Persistence:** Volume-backed

## API Endpoints

The application exposes the following main API routes:

### Authentication
- `POST /login` - User authentication
- `POST /logout` - User logout

### Residents Management
- `GET /resident/index` - List all residents
- `POST /resident/save` - Create new resident
- `GET /resident/edit/{id}` - Get resident details
- `POST /resident/saveEdit` - Update resident
- `DELETE /resident/delete/{id}` - Delete resident

### Visits Management
- `GET /visit/index` - Visit reports by date
- `POST /visit/save` - Register new visit
- `GET /visit/detail` - Active visits
- `POST /visit/egreso` - Mark visit exit
- `GET /visit/table/{from}/{to}` - Visit reports by date range

### Reports
- `GET /visit/table/{from}/{to}` - Visits by date range
- `GET /visit/tablebyresident/{from}/{to}/{id}` - Visits by resident

### File Management
- `GET /imagen/{filename}` - Retrieve visitor images
- `POST /visit/saveimg` - Upload visitor photos

## Environment Configuration

### Default Configuration (.env.docker)
```env
APP_NAME=GaritaApp
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=garitaapp
DB_USERNAME=garita_user
DB_PASSWORD=garita_password

REDIS_HOST=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### Custom Configuration
To customize the environment:

1. **Copy the environment file:**
   ```bash
   cp .env.docker .env
   ```

2. **Modify variables as needed:**
   ```bash
   nano .env
   ```

3. **Restart the application container:**
   ```bash
   docker-compose restart app
   ```

## Database Management

### Default Admin User
- **Username:** admin
- **Email:** admin@garitaapp.com
- **Password:** admin123

### Accessing the Database

**Via PhpMyAdmin:**
- URL: http://localhost:8081
- Server: mysql
- Username: garita_user
- Password: garita_password

**Via Command Line:**
```bash
docker-compose exec mysql mysql -u garita_user -p garitaapp
```

**Via External Client:**
- Host: localhost
- Port: 3306
- Database: garitaapp
- Username: garita_user
- Password: garita_password

## Development Workflow

### Making Code Changes
1. **Edit files locally** - Changes are automatically synced
2. **Clear cache if needed:**
   ```bash
   docker-compose exec app php artisan cache:clear
   docker-compose exec app php artisan config:clear
   ```

### Running Artisan Commands
```bash
# General format
docker-compose exec app php artisan [command]

# Examples
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan queue:work
```

### Viewing Logs
```bash
# Application logs
docker-compose logs -f app

# Nginx logs
docker-compose logs -f nginx

# Database logs
docker-compose logs -f mysql

# All services
docker-compose logs -f
```

## Frontend Integration

### CORS Configuration
The Nginx configuration includes CORS headers for frontend integration:

```nginx
add_header 'Access-Control-Allow-Origin' '*' always;
add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
```

### API Base URL
Use `http://localhost:8080` as your API base URL in frontend applications.

### Authentication
The API uses Laravel's built-in session-based authentication. For API tokens, consider implementing Laravel Sanctum.

## Production Deployment

### Security Considerations
1. **Change default passwords:**
   ```bash
   # Update .env file with secure passwords
   DB_PASSWORD=your_secure_password
   MYSQL_ROOT_PASSWORD=your_secure_root_password
   ```

2. **Generate new APP_KEY:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

3. **Use HTTPS in production:**
   - Configure SSL certificates in `docker/nginx/ssl/`
   - Update APP_URL to use HTTPS

### Performance Optimization
1. **Enable OPcache:**
   ```dockerfile
   RUN docker-php-ext-install opcache
   ```

2. **Optimize Composer:**
   ```bash
   docker-compose exec app composer install --optimize-autoloader --no-dev
   ```

3. **Cache configuration:**
   ```bash
   docker-compose exec app php artisan config:cache
   docker-compose exec app php artisan route:cache
   docker-compose exec app php artisan view:cache
   ```

## Troubleshooting

### Common Issues

**1. Port Already in Use:**
```bash
# Check what's using the port
sudo lsof -i :8080

# Change ports in docker-compose.yml
ports:
  - "8090:80"  # Use different port
```

**2. Permission Issues:**
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chmod -R 775 storage
```

**3. Database Connection Failed:**
```bash
# Check if MySQL is ready
docker-compose exec mysql mysqladmin ping

# Restart services
docker-compose restart mysql app
```

**4. Application Key Missing:**
```bash
# Generate new key
docker-compose exec app php artisan key:generate
```

### Health Checks

**Check Application Status:**
```bash
curl http://localhost:8080/login
```

**Check Database Connection:**
```bash
docker-compose exec app php artisan tinker
# In tinker: DB::connection()->getPdo();
```

**Check Redis Connection:**
```bash
docker-compose exec redis redis-cli ping
```

## Maintenance

### Backup Database
```bash
docker-compose exec mysql mysqldump -u garita_user -p garitaapp > backup.sql
```

### Restore Database
```bash
docker-compose exec -T mysql mysql -u garita_user -p garitaapp < backup.sql
```

### Update Application
```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker-compose down
docker-compose up -d --build

# Run migrations
docker-compose exec app php artisan migrate
```

### Clean Up
```bash
# Remove containers and volumes
docker-compose down -v

# Remove images
docker-compose down --rmi all

# Clean up Docker system
docker system prune -a
```

## Support

For issues and questions:
1. Check the logs: `docker-compose logs -f`
2. Verify configuration files in `docker/` directory
3. Ensure all required ports are available
4. Check Docker and Docker Compose versions

## File Structure

```
GaritaApp/
├── docker-compose.yml          # Main orchestration file
├── Dockerfile                  # Laravel application container
├── .env.docker                 # Environment configuration
├── garita.sh                   # Management script (executable)
├── docker/
│   ├── mysql/
│   │   ├── my.cnf             # MySQL configuration
│   │   └── init-db.sql        # Database initialization
│   ├── nginx/
│   │   ├── nginx.conf         # Main Nginx config
│   │   └── sites/garita.conf  # Site-specific config
│   ├── supervisor/
│   │   └── supervisord.conf   # Process management
│   └── scripts/
│       └── start.sh           # Application startup script
└── DOCKER_SETUP.md            # This documentation
```

This setup provides a production-ready, scalable backend API that can be easily integrated with any frontend application.