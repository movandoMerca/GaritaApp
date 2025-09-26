#!/bin/bash

# GaritaApp Docker Management Script
# Usage: ./garita.sh [command]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Function to show help
show_help() {
    echo -e "${BLUE}GaritaApp Docker Management Commands:${NC}"
    echo ""
    echo "  ./garita.sh build        - Build all containers"
    echo "  ./garita.sh up           - Start all services"
    echo "  ./garita.sh down         - Stop all services"
    echo "  ./garita.sh restart      - Restart all services"
    echo "  ./garita.sh logs         - Show logs for all services"
    echo "  ./garita.sh logs-app     - Show application logs only"
    echo "  ./garita.sh logs-nginx   - Show nginx logs only"
    echo "  ./garita.sh logs-mysql   - Show mysql logs only"
    echo "  ./garita.sh clean        - Clean up containers and volumes"
    echo "  ./garita.sh backup       - Backup database"
    echo "  ./garita.sh restore FILE - Restore database from backup file"
    echo "  ./garita.sh shell        - Access application shell"
    echo "  ./garita.sh mysql        - Access MySQL shell"
    echo "  ./garita.sh cache        - Clear application cache"
    echo "  ./garita.sh key          - Generate application key"
    echo "  ./garita.sh migrate      - Run database migrations"
    echo "  ./garita.sh install      - Install/update dependencies"
    echo "  ./garita.sh dev-setup    - Complete development setup"
    echo "  ./garita.sh prod-setup   - Complete production setup"
    echo "  ./garita.sh health       - Check service health"
    echo "  ./garita.sh help         - Show this help message"
    echo ""
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker first."
        exit 1
    fi
}

# Function to check if docker-compose is available
check_docker_compose() {
    if ! command -v docker-compose > /dev/null 2>&1; then
        print_error "docker-compose is not installed or not in PATH."
        exit 1
    fi
}

# Build containers
build() {
    print_info "Building containers..."
    docker-compose build --no-cache
    print_success "Containers built successfully!"
}

# Start services
up() {
    print_info "Starting services..."
    docker-compose up -d
    print_success "Services started!"
    echo ""
    print_info "API will be available at: http://localhost:8080"
    print_info "PhpMyAdmin will be available at: http://localhost:8081"
    print_warning "Use './garita.sh logs' to monitor startup progress"
}

# Stop services
down() {
    print_info "Stopping services..."
    docker-compose down
    print_success "Services stopped!"
}

# Restart services
restart() {
    print_info "Restarting services..."
    docker-compose restart
    print_success "Services restarted!"
}

# Show logs
logs() {
    print_info "Showing logs for all services (Ctrl+C to exit)..."
    docker-compose logs -f
}

# Show logs for specific services
logs_app() {
    print_info "Showing application logs (Ctrl+C to exit)..."
    docker-compose logs -f app
}

logs_nginx() {
    print_info "Showing nginx logs (Ctrl+C to exit)..."
    docker-compose logs -f nginx
}

logs_mysql() {
    print_info "Showing mysql logs (Ctrl+C to exit)..."
    docker-compose logs -f mysql
}

# Clean up everything
clean() {
    print_warning "This will remove all containers, volumes, and images. Are you sure? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_info "Cleaning up..."
        docker-compose down -v --rmi all
        docker system prune -f
        print_success "Cleanup completed!"
    else
        print_info "Cleanup cancelled."
    fi
}

# Backup database
backup() {
    print_info "Creating database backup..."
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    docker-compose exec mysql mysqldump -u garita_user -pgarita_password garitaapp > "$BACKUP_FILE"
    print_success "Backup created: $BACKUP_FILE"
}

# Restore database
restore() {
    if [ -z "$1" ]; then
        print_error "Usage: ./garita.sh restore BACKUP_FILE"
        exit 1
    fi
    
    if [ ! -f "$1" ]; then
        print_error "Backup file '$1' not found!"
        exit 1
    fi
    
    print_info "Restoring database from $1..."
    docker-compose exec -T mysql mysql -u garita_user -pgarita_password garitaapp < "$1"
    print_success "Database restored successfully!"
}

# Access application shell
shell() {
    print_info "Accessing application shell..."
    docker-compose exec app bash
}

# Access MySQL shell
mysql_shell() {
    print_info "Accessing MySQL shell..."
    docker-compose exec mysql mysql -u garita_user -pgarita_password garitaapp
}

# Clear application cache
cache() {
    print_info "Clearing application cache..."
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    print_success "Cache cleared!"
}

# Generate application key
key() {
    print_info "Generating application key..."
    docker-compose exec app php artisan key:generate
    print_success "Application key generated!"
}

# Run migrations
migrate() {
    print_info "Running database migrations..."
    docker-compose exec app php artisan migrate
    print_success "Migrations completed!"
}

# Install/update dependencies
install() {
    print_info "Installing/updating dependencies..."
    docker-compose exec app composer install --optimize-autoloader
    docker-compose exec app npm install
    docker-compose exec app npm run production
    print_success "Dependencies installed!"
}

# Development setup
dev_setup() {
    print_info "Setting up development environment..."
    build
    up
    print_info "Waiting for services to be ready..."
    sleep 30
    migrate
    cache
    echo ""
    print_success "Development environment is ready!"
    print_info "API: http://localhost:8080"
    print_info "PhpMyAdmin: http://localhost:8081"
    print_info "Default admin: admin@garitaapp.com / admin123"
}

# Production setup
prod_setup() {
    print_info "Setting up production environment..."
    build
    up
    print_info "Waiting for services to be ready..."
    sleep 30
    key
    migrate
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    echo ""
    print_success "Production environment is ready!"
    print_info "API: http://localhost:8080"
    print_warning "Remember to update passwords and SSL certificates for production!"
}

# Health check
health() {
    print_info "Checking service health..."
    
    echo -n "App container: "
    if docker-compose exec app php artisan --version > /dev/null 2>&1; then
        print_success "OK"
    else
        print_error "Not responding"
    fi
    
    echo -n "Database: "
    if docker-compose exec mysql mysqladmin ping -u garita_user -pgarita_password > /dev/null 2>&1; then
        print_success "OK"
    else
        print_error "Not responding"
    fi
    
    echo -n "Redis: "
    if docker-compose exec redis redis-cli ping > /dev/null 2>&1; then
        print_success "OK"
    else
        print_error "Not responding"
    fi
    
    echo -n "Nginx: "
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 2>/dev/null || echo "000")
    if [[ "$HTTP_CODE" =~ ^(200|302)$ ]]; then
        print_success "OK (HTTP $HTTP_CODE)"
    else
        print_error "Not responding (HTTP $HTTP_CODE)"
    fi
}

# Main script logic
main() {
    # Check prerequisites
    check_docker
    check_docker_compose
    
    # Handle commands
    case "${1:-help}" in
        "build")
            build
            ;;
        "up")
            up
            ;;
        "down")
            down
            ;;
        "restart")
            restart
            ;;
        "logs")
            logs
            ;;
        "logs-app")
            logs_app
            ;;
        "logs-nginx")
            logs_nginx
            ;;
        "logs-mysql")
            logs_mysql
            ;;
        "clean")
            clean
            ;;
        "backup")
            backup
            ;;
        "restore")
            restore "$2"
            ;;
        "shell")
            shell
            ;;
        "mysql")
            mysql_shell
            ;;
        "cache")
            cache
            ;;
        "key")
            key
            ;;
        "migrate")
            migrate
            ;;
        "install")
            install
            ;;
        "dev-setup")
            dev_setup
            ;;
        "prod-setup")
            prod_setup
            ;;
        "health")
            health
            ;;
        "help"|*)
            show_help
            ;;
    esac
}

# Run main function with all arguments
main "$@"