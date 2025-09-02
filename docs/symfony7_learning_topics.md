# Symfony 7 Learning Topics

## Foundation
1. **PHP 8.2/8.3 Features**
   - Named arguments
   - Constructor property promotion
   - Attributes (replacing annotations)
   - Enums
   
2. **Symfony Components**
   - HttpFoundation
   - HttpKernel
   - Routing
   - DependencyInjection
   - EventDispatcher

3. **Symfony Flex**
   - Project structure
   - Recipes system
   - Environment configuration (.env files)

## Web Development
1. **Controllers**
   - Attributes for routing (#[Route])
   - AbstractController features
   - Request and Response handling
   - JSON responses

2. **Twig Templating**
   - Template inheritance
   - Filters and functions
   - Asset management
   - Forms rendering

3. **Forms**
   - Form types
   - Data transformers
   - Validation
   - CSRF protection
   - File uploads

4. **Security**
   - Authentication
   - Authorization (#[IsGranted])
   - Voters
   - Password hashing
   - CSRF protection
   - Security attributes

## Database & ORM
1. **Doctrine ORM**
   - Entity mapping
   - Relationships (OneToMany, ManyToOne, etc.)
   - Repository pattern
   - Query Builder
   - DQL (Doctrine Query Language)

2. **Migrations**
   - Creating migrations
   - Running migrations
   - Rolling back

3. **Fixtures**
   - Creating test data
   - Factory pattern with Zenstruck Foundry

## Testing
1. **PHPUnit Integration**
   - Unit tests
   - Functional tests
   - Test client
   - Mocking services

2. **Panther**
   - Browser testing
   - JavaScript interaction testing

3. **Test Coverage**
   - Code coverage reports
   - Test-driven development

## Advanced Topics
1. **API Development**
   - API Platform
   - REST API best practices
   - API versioning
   - API documentation
   - **API Debugging and Error Handling**
     - HTTP status codes and their meanings
     - Symfony exception handling in API contexts
     - Custom exception subscribers
     - Debugging 500 errors (server logs, profiler)
     - Proper error response formatting (RFC 7807 Problem Details)

2. **Messenger Component**
   - Message buses
   - Asynchronous processing
   - Queuing
   - Message handlers

3. **Workflow Component**
   - State machines
   - Workflow transitions
   - Event dispatching

4. **Cache**
   - HTTP cache
   - App cache
   - Redis/Memcached integration

5. **Performance Optimization**
   - Profiling (Symfony Profiler)
   - Blackfire integration
   - Production configuration

## Symfony 7 Specific Features
1. **Improved Developer Experience**
   - Better error messages
   - Enhanced debugging
   - New maker commands

2. **Performance Improvements**
   - Faster response times
   - Reduced memory usage
   - Enhanced caching

3. **Modern PHP Support**
   - Fully leveraging PHP 8.2/8.3 features
   - Typed properties
   - Return type declarations

4. **Components Updates**
   - Validator component enhancements
   - New HTTP client features
   - Enhanced Mailer component

5. **Removed Deprecations**
   - Updated code patterns
   - Removed legacy features
   - Migration path from Symfony 6.x

## Best Practices
1. **Code Organization**
   - Service architecture
   - Dependency Injection patterns
   - Command pattern

2. **Logging and Monitoring**
   - Monolog configuration
   - Log levels and channels
   - Integrating with monitoring tools

3. **Deployment**
   - CI/CD pipelines
   - Symfony deployment best practices
   - Docker containerization

4. **Security Best Practices**
   - OWASP top 10 mitigations
   - Security headers
   - Content Security Policy

## Troubleshooting Common Issues
1. **HTTP 500 Errors**
   - Checking Symfony logs (`var/log/dev.log` or `var/log/prod.log`)
   - Using the Symfony profiler (`/_profiler`)
   - Common causes of 500 errors:
     - Database connection issues
     - Missing services or parameters
     - Permission problems with cache/log directories
     - PHP fatal errors or exceptions

2. **API Endpoint Debugging**
   - Validating route configuration
   - Checking controller access permissions
   - Using tools like Postman or Symfony's HTTP client
   - Logging request/response cycles with middleware

3. **Performance Issues**
   - Identifying bottlenecks with the Profiler
   - Database query optimization
   - Caching strategies
   - Using Blackfire for in-depth performance analysis
