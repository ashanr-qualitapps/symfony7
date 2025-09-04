# Symfony 7 Application

A modern web application built with Symfony 7.0, featuring user authentication, authorization, and comprehensive API endpoints. This application includes a complete user management system with role-based access control.

## Features

- User Authentication and Authorization
- Web Login Interface with Remember Me functionality
- Role-based Access Control (RBAC)
- RESTful API endpoints
- Database integration with Doctrine ORM
- Health monitoring endpoints
- Admin and User management interfaces
- **Product Management System with full CRUD operations**
- **Live Resource Monitor with real-time charts**
- **Modern responsive dashboard with navigation**

## Requirements

- PHP 8.2 or higher
- Composer
- Database (MySQL/PostgreSQL recommended)
- Web server (Apache, Nginx, or PHP built-in server)

## Installation

1. **Clone the repository** (if applicable):
   ```bash
   git clone <repository-url>
   cd symfony7
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Set up environment variables**:
   ```bash
   # Copy the example environment file
   cp .env .env.local
   
   # Edit .env.local with your database configuration
   # Example for MySQL:
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/symfony7_db?serverVersion=8.0"
   ```

4. **Set up the database**:
   ```bash
   # Create the database
   php bin/console doctrine:database:create
   
   # Run database migrations
   php bin/console doctrine:migrations:migrate
   
   # Load default users (if fixtures are available)
   php bin/console doctrine:fixtures:load
   ```

## Running the Application

### Option 1: PHP Built-in Server (Recommended)

```bash
# Start the PHP built-in server
php -S localhost:8000 -t public/
```

The application will be available at `http://localhost:8000`.

### Web Login Interface

The application now includes a web-based login interface:

- **Login Page**: http://localhost:8000/login
- **Default Admin**: admin@example.com / password
- **Default User**: user@example.com / password

Features of the login system:
- Bootstrap-styled responsive login form
- CSRF protection
- Remember me functionality
- Error messaging
- Role-based access control
- Navigation bar with login status

**Note**: The Symfony CLI is not installed on this system. If you want to install it, you can download it from [https://symfony.com/download](https://symfony.com/download).

## Test API Endpoints

The application includes comprehensive API endpoints for health monitoring, user authentication, and user management.

### Authentication Endpoints

1. **User Login**: `POST /api/auth/login`
   ```bash
   curl -X POST http://localhost:8000/api/auth/login \
   -H "Content-Type: application/json" \
   -d '{"email": "admin@example.com", "password": "password"}'
   ```

2. **User Registration**: `POST /api/auth/register`
   ```bash
   curl -X POST http://localhost:8000/api/auth/register \
   -H "Content-Type: application/json" \
   -d '{"username": "newuser", "email": "newuser@example.com", "password": "password"}'
   ```

3. **User Profile**: `GET /api/auth/me`
   ```bash
   curl -X GET http://localhost:8000/api/auth/me \
   -H "Authorization: Bearer YOUR_TOKEN"
   ```

### User Management Endpoints

1. **List Users (API)**: `GET /api/users`
   ```bash
   curl http://localhost:8000/api/users
   ```

2. **List Users (Web)**: `GET /users`
   - Visit http://localhost:8000/users in your browser

### Product Management Endpoints

1. **List Products**: `GET /products`
   - Visit http://localhost:8000/products in your browser

2. **Create Product**: `POST /products/new` (Admin only)
   - Visit http://localhost:8000/products/new in your browser

3. **View Product**: `GET /products/{id}`
   - Visit http://localhost:8000/products/{id} in your browser

4. **Edit Product**: `POST /products/{id}/edit` (Admin only)
   - Visit http://localhost:8000/products/{id}/edit in your browser

5. **Delete Product**: `POST /products/{id}/delete` (Admin only)

### Default Users

The application comes with two default users for testing:

- **Admin User**: 
  - Email: `admin@example.com`
  - Password: `password`
  - Roles: `ROLE_ADMIN`, `ROLE_USER`

- **Regular User**: 
  - Email: `user@example.com`  
  - Password: `password`
  - Roles: `ROLE_USER`

### Health Check Endpoints

1. **Basic Health Check**: `GET /api/health`
   ```bash
   # Returns comprehensive system information
   curl http://localhost:8000/api/health
   ```

2. **Simple Ping**: `GET /api/ping`
   ```bash
   # Returns a simple pong response
   curl http://localhost:8000/api/ping
   ```

3. **Detailed Status**: `GET /api/status`
   ```bash
   # Returns detailed health checks for various system components
   curl http://localhost:8000/api/status
   ```

### Example Response (Health Check)

```json
{
  "status": "OK",
  "timestamp": "2025-08-28T09:54:00+00:00",
  "version": "1.0.0",
  "environment": "dev",
  "php_version": "8.2.12",
  "symfony_version": "7.0.10",
  "memory_usage": "12.5 MB",
  "uptime": "N/A"
}
```

You can test these endpoints in your browser by visiting:
- http://localhost:8000/api/health
- http://localhost:8000/api/ping
- http://localhost:8000/api/status

### Option 2: Symfony CLI (Optional)

If you have the Symfony CLI installed:

```bash
# Start the development server
symfony serve

# Or start on a specific port
symfony serve --port=8080

# Start in daemon mode (background)
symfony serve -d
```

### Option 3: Web Server

Configure your web server (Apache/Nginx) to point to the `public/` directory as the document root.

#### Apache Configuration Example:
```apache
<VirtualHost *:80>
    ServerName symfony7.local
    DocumentRoot /path/to/symfony7/public
    DirectoryIndex index.php
    
    <Directory /path/to/symfony7/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration Example:
```nginx
server {
    listen 80;
    server_name symfony7.local;
    root /path/to/symfony7/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

## Development Commands

### Database Commands
```bash
# Create database
php bin/console doctrine:database:create

# Drop database  
php bin/console doctrine:database:drop --force

# Generate migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Check migration status
php bin/console doctrine:migrations:status

# Update database schema (development only)
php bin/console doctrine:schema:update --force
```

### User Management Commands
```bash
# Create a new user (if command exists)
php bin/console app:create-user

# List all users
php bin/console app:list-users
```

### Cache Management
```bash
# Clear cache
php bin/console cache:clear

# Clear cache for production environment
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup
```bash
# List all routes
php bin/console debug:router

# Show container services
php bin/console debug:container

# Show configuration
php bin/console debug:config

# Show environment variables
php bin/console debug:dotenv
```

### Assets Management
```bash
# Install assets
php bin/console assets:install

# Install assets with symlinks (for development)
php bin/console assets:install --symlink
```

## Project Structure

```
├── bin/                 # Executable files (console commands)
├── config/             # Configuration files
│   ├── packages/       # Package-specific configuration
│   └── routes/         # Routing configuration
├── migrations/         # Database migrations
├── public/             # Web-accessible files (document root)
│   └── index.php       # Front controller
├── src/                # Application source code
│   ├── Controller/     # Controllers (Auth, User, Health, Admin)
│   ├── Entity/         # Doctrine entities (User)
│   ├── Repository/     # Database repositories (UserRepository)
│   └── Kernel.php      # Application kernel
├── templates/          # Twig templates
├── var/                # Generated files (cache, logs)
│   ├── cache/          # Application cache
│   └── log/            # Application logs
├── vendor/             # Composer dependencies
├── AUTHENTICATION.md   # Authentication API documentation
├── curl_commands.md    # cURL examples for testing
└── composer.json       # Composer configuration
```

## Environment Configuration

Create a `.env.local` file to override default environment variables:

```bash
# .env.local
APP_ENV=dev
APP_SECRET=your-secret-key-here

# Database configuration
DATABASE_URL="mysql://username:password@127.0.0.1:3306/symfony7_db?serverVersion=8.0"
# Or for PostgreSQL:
# DATABASE_URL="postgresql://username:password@127.0.0.1:5432/symfony7_db?serverVersion=15&charset=utf8"
```

## API Documentation

For detailed API documentation, see:
- `AUTHENTICATION.md` - Complete authentication and authorization API reference
- `curl_commands.md` - Ready-to-use cURL commands for testing all endpoints

## Testing the Application

### Quick Test Sequence

1. **Start the server**:
   ```bash
   php -S localhost:8000 -t public/
   ```

2. **Test health endpoints**:
   ```bash
   curl http://localhost:8000/api/health
   curl http://localhost:8000/api/ping
   ```

3. **Test authentication**:
   ```bash
   # Login as admin
   curl -X POST http://localhost:8000/api/auth/login \
   -H "Content-Type: application/json" \
   -d '{"email": "admin@example.com", "password": "password"}'
   ```

4. **View users**:
   - API: `curl http://localhost:8000/api/users`
   - Web: Visit http://localhost:8000/users

## Current Dependencies

This application comes pre-configured with the following packages:

### Core Framework
- `symfony/framework-bundle` - Core Symfony framework
- `symfony/console` - Command-line interface
- `symfony/runtime` - Application runtime

### Database & ORM
- `doctrine/orm` - Object-Relational Mapping
- `doctrine/doctrine-bundle` - Doctrine integration
- `doctrine/doctrine-migrations-bundle` - Database migrations
- `doctrine/dbal` - Database abstraction layer

### Security & Authentication
- `symfony/security-bundle` - Authentication and authorization
- `symfony/validator` - Data validation

### Templating
- `symfony/twig-bundle` - Twig templating engine

### Configuration
- `symfony/dotenv` - Environment variable handling
- `symfony/yaml` - YAML configuration support

## Adding Features

### Installing Additional Packages

The application already includes:
- Doctrine ORM for database operations
- Symfony Security for authentication
- Twig templating engine
- Validation component

Additional packages you might want:

```bash
# Add API Platform for advanced API features
composer require api-platform/api-pack

# Add forms for web forms
composer require symfony/form

# Add development tools
composer require --dev symfony/debug-bundle symfony/var-dumper

# Add fixtures for sample data
composer require --dev doctrine/doctrine-fixtures-bundle
```

### Creating Controllers

```bash
# Generate a new controller
php bin/console make:controller ControllerName
```

## Testing

To add testing capabilities:

```bash
# Install PHPUnit
composer require --dev symfony/test-pack

# Run tests
php bin/phpunit
```

## Production Deployment

1. **Install dependencies** (without dev dependencies):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Set environment to production**:
   ```bash
   APP_ENV=prod
   ```

3. **Clear and warm up cache**:
   ```bash
   php bin/console cache:clear --env=prod
   php bin/console cache:warmup --env=prod
   ```

4. **Install assets**:
   ```bash
   php bin/console assets:install --env=prod
   ```

## Troubleshooting

### Common Issues

1. **Permission errors**: Make sure `var/` directory is writable
   ```bash
   chmod -R 775 var/
   ```

2. **Cache issues**: Clear the cache
   ```bash
   php bin/console cache:clear
   ```

3. **Autoloader issues**: Update the autoloader
   ```bash
   composer dump-autoload
   ```

## Useful Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Symfony CLI Documentation](https://symfony.com/download)

## License

This project is proprietary software.

## Workflow Component

This project uses the Symfony Workflow component to manage state transitions for entities (e.g., Product).

### Configuration

The workflow is defined in `config/packages/workflow.yaml`:

```yaml
framework:
    workflows:
        sample_process:
            type: 'state_machine'
            supports:
                - App\Entity\Product
            places:
                - draft
                - review
                - published
            transitions:
                to_review:
                    from: draft
                    to: review
                publish:
                    from: review
                    to: published
```

### Usage Example

You can use the workflow service in your code:

```php
use Symfony\Component\Workflow\WorkflowInterface;

// Inject WorkflowInterface $workflow (for 'sample_process')
$canPublish = $workflow->can($product, 'publish');
if ($canPublish) {
    $workflow->apply($product, 'publish');
}
```
