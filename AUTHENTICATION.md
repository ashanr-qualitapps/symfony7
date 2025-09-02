# User Authentication and Authorization API

This document describes the authentication and authorization endpoints available in the Symfony 7 application.

## Default Users

The application comes with two default users for testing:

1. **Admin User**
   - Email: `admin@example.com`
   - Username: `admin`
   - Password: `password`
   - Roles: `ROLE_ADMIN`, `ROLE_USER`

2. **Regular User**
   - Email: `user@example.com`
   - Username: `user`
   - Password: `password`
   - Roles: `ROLE_USER`

## Authentication Endpoints

### 1. User Login
**POST** `/api/auth/login`

Login with email and password to receive user information and a mock token.

**Request Body:**
```json
{
    "email": "admin@example.com",
    "password": "password"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "email": "admin@example.com",
            "username": "admin",
            "firstName": "Admin",
            "lastName": "User",
            "fullName": "Admin User",
            "roles": ["ROLE_ADMIN", "ROLE_USER"],
            "isActive": true,
            "createdAt": "2025-08-28T10:00:00+00:00",
            "updatedAt": "2025-08-28T10:00:00+00:00"
        },
        "token": "eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGV4YW1wbGUuY29tIiwiZXhwIjoxNjkzMjIyODAwLCJpYXQiOjE2OTMyMTkyMDB9",
        "expires_in": 3600
    }
}
```

### 2. User Registration
**POST** `/api/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "email": "newuser@example.com",
    "username": "newuser",
    "firstName": "John",
    "lastName": "Doe",
    "password": "securepassword123"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 3,
            "email": "newuser@example.com",
            "username": "newuser",
            "firstName": "John",
            "lastName": "Doe",
            "fullName": "John Doe",
            "roles": ["ROLE_USER"],
            "isActive": true,
            "createdAt": "2025-08-28T10:15:00+00:00",
            "updatedAt": "2025-08-28T10:15:00+00:00"
        }
    }
}
```

### 3. Get Current User Profile
**GET** `/api/auth/me`

Get the current authenticated user's profile information.

**Headers:**
```
Authorization: Basic [base64(email:password)]
```

**Response (Success):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "admin@example.com",
            "username": "admin",
            "firstName": "Admin",
            "lastName": "User",
            "fullName": "Admin User",
            "roles": ["ROLE_ADMIN", "ROLE_USER"],
            "isActive": true,
            "createdAt": "2025-08-28T10:00:00+00:00",
            "updatedAt": "2025-08-28T10:00:00+00:00"
        }
    }
}
```

### 4. Update User Profile
**PUT/PATCH** `/api/auth/me`

Update the current authenticated user's profile.

**Headers:**
```
Authorization: Basic [base64(email:password)]
```

**Request Body:**
```json
{
    "firstName": "Updated",
    "lastName": "Name",
    "username": "updatedusername"
}
```

### 5. Change Password
**POST** `/api/auth/change-password`

Change the current authenticated user's password.

**Headers:**
```
Authorization: Basic [base64(email:password)]
```

**Request Body:**
```json
{
    "currentPassword": "oldpassword",
    "newPassword": "newpassword123"
}
```

### 6. Logout
**POST** `/api/auth/logout`

Logout the current user (in a real JWT implementation, this would invalidate the token).

**Headers:**
```
Authorization: Basic [base64(email:password)]
```

## Admin Authorization Endpoints

All admin endpoints require `ROLE_ADMIN` access.

### 1. List All Users
**GET** `/api/admin/users`

Get a list of all users with optional filtering.

**Query Parameters:**
- `role` - Filter by role (e.g., `ROLE_ADMIN`)
- `active` - Filter by active status (`true`/`false`)
- `search` - Search by email, username, or full name

**Headers:**
```
Authorization: Basic [base64(admin@example.com:password)]
```

### 2. Get User by ID
**GET** `/api/admin/users/{id}`

Get a specific user by their ID.

### 3. Create New User
**POST** `/api/admin/users`

Create a new user account (admin only).

**Request Body:**
```json
{
    "email": "newadmin@example.com",
    "username": "newadmin",
    "firstName": "New",
    "lastName": "Admin",
    "password": "securepassword123",
    "roles": ["ROLE_ADMIN"],
    "isActive": true
}
```

### 4. Update User
**PUT/PATCH** `/api/admin/users/{id}`

Update any user's information.

### 5. Delete User
**DELETE** `/api/admin/users/{id}`

Delete a user account (cannot delete own account).

### 6. Activate User
**POST** `/api/admin/users/{id}/activate`

Activate a user account.

### 7. Deactivate User
**POST** `/api/admin/users/{id}/deactivate`

Deactivate a user account (cannot deactivate own account).

### 8. Update User Roles
**PUT** `/api/admin/users/{id}/roles`

Update a user's roles.

**Request Body:**
```json
{
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
}
```

### 9. Get Statistics
**GET** `/api/admin/stats`

Get user statistics and counts.

**Response:**
```json
{
    "success": true,
    "data": {
        "stats": {
            "total_users": 10,
            "active_users": 8,
            "inactive_users": 2,
            "admin_users": 2,
            "regular_users": 8
        }
    }
}
```

## Example cURL Commands

### Login as Admin
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

### Get Current User Profile
```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Basic $(echo -n 'admin@example.com:password' | base64)"
```

### List All Users (Admin)
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Basic $(echo -n 'admin@example.com:password' | base64)"
```

### Register New User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "username": "testuser",
    "firstName": "Test",
    "lastName": "User",
    "password": "testpassword123"
  }'
```

## Security Features

1. **Password Hashing**: Uses Symfony's built-in password hasher with bcrypt
2. **Role-based Access Control**: Different endpoints require different roles
3. **Input Validation**: All inputs are validated using Symfony Validator
4. **Error Handling**: Comprehensive error responses with appropriate HTTP status codes
5. **User Management**: Full CRUD operations for user management
6. **Account Protection**: Users cannot delete/deactivate their own accounts

## Notes

- This implementation uses HTTP Basic authentication for simplicity
- In production, consider implementing JWT tokens for better security
- The UserRepository is currently in-memory; consider using Doctrine ORM for persistence
- All passwords are hashed using Symfony's secure password hashing system
- Default users are created automatically for testing purposes
