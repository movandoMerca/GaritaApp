# GaritaApp - Context Documentation

## Project Overview

**GaritaApp** is a comprehensive visitor management system designed for residential complexes and gated communities. The application manages visitor registration, resident information, and provides detailed reporting capabilities with photo capture functionality.

## Architecture & Stack

### Backend Framework
- **Laravel 8.12** - PHP web application framework
- **PHP 7.3|8.0** - Server-side programming language
- **MySQL** - Primary database system
- **Composer** - PHP dependency management

### Frontend Technologies
- **Metronic Theme** - Premium admin dashboard template
- **Bootstrap 4.6.0** - CSS framework for responsive design
- **jQuery 3.6.0** - JavaScript library
- **Vue.js 2.6.12** - Progressive JavaScript framework
- **Laravel Mix 6.0.25** - Asset compilation and build tool
- **SASS** - CSS preprocessor

### Key Frontend Libraries & Components
- **DataTables** - Advanced table functionality with sorting, filtering, and pagination
- **CKEditor 5** - Rich text editor for content management
- **FullCalendar** - Calendar and scheduling functionality
- **ApexCharts** - Modern charting library for data visualization
- **Select2** - Enhanced select boxes with search functionality
- **SweetAlert2** - Beautiful, responsive popup boxes
- **Dropzone** - Drag and drop file uploads
- **Webcam integration** - Camera capture for visitor photos
- **Leaflet & Google Maps** - Mapping and geolocation services

## Database Schema

### Core Tables

#### `users`
- User authentication and authorization
- Role-based access control (admin, superuser, regular user)
- Camera configuration settings per user

#### `residentes` (Residents)
- Resident information management
- Unique resident codes
- Contact information and addresses
- Status tracking (active/inactive)

#### `visitas` (Visits)
- Complete visitor registration system
- License information capture
- Photo storage paths (license, visitor, vehicle plate)
- Entry/exit timestamp tracking
- Emergency contact information

#### `config`
- System configuration settings
- Feature toggles (photo capture, webcam, phone access)
- Branding and logo management

#### `logs`
- Comprehensive audit trail
- User action tracking
- System activity monitoring

#### `validation`
- License validation system
- Token-based authentication for external integrations

## Application Features

### Visitor Management
- **Visitor Registration**: Complete visitor information capture including license scanning
- **Photo Capture**: Multiple photo types (visitor, license, vehicle plate)
- **Real-time Processing**: Instant visitor check-in/check-out
- **Emergency Contacts**: Emergency contact information storage

### Resident Management
- **Resident Database**: Complete resident information management
- **CSV Import**: Bulk resident data import functionality
- **Resident Codes**: Unique identification system
- **Status Management**: Active/inactive resident tracking

### Reporting System
- **Date Range Reports**: Visitor reports by date range
- **Resident-specific Reports**: Visitor history per resident
- **Export Functionality**: PDF and data export capabilities
- **Visual Analytics**: Charts and graphs for visitor patterns

### Security Features
- **Role-based Access Control**: Admin, superuser, and regular user roles
- **License Validation**: Integration with license verification systems
- **Photo Documentation**: Complete visual record of all visits
- **Audit Logging**: Comprehensive activity tracking

### Camera Integration
- **Webcam Support**: Real-time photo capture
- **Multiple Camera Types**: Support for license, visitor, and plate cameras
- **Image Processing**: Base64 encoding and storage
- **Camera Configuration**: Per-user camera setup

## File Structure

### Application Structure
```
app/
├── Classes/Theme/          # Metronic theme integration
├── Console/Commands/       # Artisan commands
├── Http/Controllers/       # Application controllers
├── Http/Middleware/        # Custom middleware
├── Models/                 # Eloquent models
└── Providers/             # Service providers
```

### Frontend Resources
```
resources/
├── views/                 # Blade templates
│   ├── auth/             # Authentication views
│   ├── layout/           # Layout components
│   ├── residents/        # Resident management views
│   ├── visits/           # Visit management views
│   ├── reports/          # Reporting views
│   └── users/            # User management views
├── sass/                 # SCSS stylesheets
├── js/                   # JavaScript files
└── metronic/             # Metronic theme assets
```

## Configuration

### Environment Settings
- **Timezone**: America/Guatemala
- **Locale**: Spanish (es)
- **Application Name**: MARDYSA (configurable)
- **Debug Mode**: Production-ready (disabled by default)

### Key Middleware
- **Authentication**: Standard Laravel auth
- **Admin Access**: Role-based admin middleware
- **License Validation**: Custom license checking
- **Banned User Check**: User status validation

## Routing Structure

### Main Route Groups
- **`/resident`** - Resident management (CRUD operations, CSV import)
- **`/users`** - User management (admin functions, camera config)
- **`/visit`** - Visit management (registration, reporting, photo capture)
- **`/config`** - System configuration (superuser only)

### Authentication
- **Login Required**: Most routes require authentication
- **Role-based Access**: Different access levels for different user types
- **Registration Disabled**: New user registration is disabled by default

## Integration Capabilities

### External Systems
- **License Scanning**: Integration with license reading systems
- **Camera Systems**: Support for multiple camera types
- **Validation Services**: External license validation
- **Export Systems**: Data export for external reporting

### API Endpoints
- **AJAX Support**: Real-time data loading
- **Image Handling**: Base64 image processing
- **JSON Responses**: RESTful API responses

## Deployment Considerations

### Server Requirements
- **PHP 7.3+** with required extensions
- **MySQL 5.7+** or compatible database
- **Web Server**: Apache/Nginx with mod_rewrite
- **Storage**: File storage for visitor photos
- **Camera Access**: Webcam permissions for photo capture

### Security Features
- **CSRF Protection**: Built-in Laravel CSRF protection
- **Input Validation**: Comprehensive form validation
- **File Upload Security**: Secure image handling
- **Session Management**: Secure session handling

## Customization Points

### Theming
- **Metronic Integration**: Professional admin theme
- **Responsive Design**: Mobile-friendly interface
- **Custom Branding**: Logo and brand customization
- **Multi-language Support**: Spanish localization

### Feature Toggles
- **Photo Capture**: Enable/disable photo requirements
- **Webcam Integration**: Toggle camera functionality
- **Phone Access**: Enable/disable phone features
- **Exit Tracking**: Toggle visitor exit recording

## Development Workflow

### Build Process
- **Laravel Mix**: Asset compilation and optimization
- **SASS Compilation**: CSS preprocessing
- **JavaScript Bundling**: Module bundling and minification
- **Hot Reloading**: Development server with live reload

### Testing
- **PHPUnit**: Unit and feature testing framework
- **Database Testing**: Migration and seeding for tests
- **Browser Testing**: Frontend functionality testing

This application represents a complete visitor management solution with modern web technologies, comprehensive security features, and extensive customization capabilities for residential and commercial gate management systems.