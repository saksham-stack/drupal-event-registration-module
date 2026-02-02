# Event Registration Module

A comprehensive Drupal 10 module that provides event registration functionality with email notifications, admin management, and CSV export capabilities.

## Features

- **Public Event Registration**: Users can register for events through a public form
- **Event Management**: Administrators can create and manage events
- **Email Notifications**: Automatic confirmation emails to users and notifications to admins
- **Capacity Management**: Limit registrations based on maximum attendee counts
- **Duplicate Prevention**: Prevents duplicate registrations for the same event
- **CSV Export**: Export registration data to CSV format
- **Admin Interface**: Comprehensive admin interface for managing events and registrations
- **Validation Rules**: Comprehensive validation for all form fields

## Requirements

- Drupal 10.x
- PHP 8.1 or higher
- MySQL database

## Installation

1. Place the `event_registration` module in your Drupal installation's `modules` directory
2. Enable the module through the Drupal admin interface (`admin/modules`)
3. Or enable via Drush: `drush en event_registration`

## Configuration

### Event Creation

1. Navigate to `admin/config/event-registration/add-event`
2. Fill in the event details:
   - Event Name
   - Category
   - Registration Start/End Dates
   - Event Date
   - Location
   - Maximum Attendees (optional)
3. Save the event

### Admin Settings

1. Navigate to `admin/config/event-registration/settings`
2. Configure admin email settings and other options

## Usage

### Public Registration

Access the registration form at: **`http://localhost/drupal/event-registration`**

1. Users can select from available events
2. Fill in their personal information:
   - Full Name
   - Email
   - College
   - Department
3. Submit the form to register

### Admin Management

1. View all registrations at `admin/event-registration/registrations`
2. Export registrations to CSV at `admin/event-registration/export/csv`
3. Add new events at `admin/config/event-registration/add-event`

## Database Schema

The module creates two tables:

### `event_registration_event`
- `id`: Primary key
- `event_name`: Event name
- `description`: Event description
- `event_date`: Event date (timestamp)
- `location`: Event location
- `max_attendees`: Maximum number of attendees
- `category`: Event category
- `registration_start`: Registration start date
- `registration_end`: Registration end date
- `status`: Active/inactive status
- `uid`: Creator user ID
- `created`: Creation timestamp
- `changed`: Last changed timestamp

### `event_registration_entry`
- `id`: Primary key
- `event_id`: Foreign key to event
- `full_name`: Registrant's full name
- `email`: Registrant's email
- `college`: Registrant's college
- `department`: Registrant's department
- `created`: Registration timestamp

## Email Templates

The module sends two types of emails:

1. **Registration Confirmation**: Sent to the registrant
2. **Admin Notification**: Sent to the configured admin email

**Note**: The email functionality has been implemented but requires proper SMTP configuration for reliable delivery in production environments. In local development environments, emails may not be delivered due to PHP mail configuration limitations.

## CSV Export

Administrators can export all registrations to CSV format from the admin interface. The export includes:
- Registration ID
- Event Name
- Full Name
- Email
- College
- Department
- Registration Date

## Validation Rules Implemented

- **Event Selection**: Validates that an event is selected and registration is open
- **Full Name**: Required field with minimum 2 characters validation
- **Email**: Required field with format validation and duplicate check
- **College**: Required field validation
- **Department**: Required field validation
- **Duplicate Prevention**: Checks for existing registrations with the same email for the same event
- **Capacity Check**: Verifies event hasn't reached maximum attendee limit

## Technical Implementation Highlights

### Robust Error Handling
- Comprehensive try-catch blocks for database operations
- Graceful degradation when optional database columns are missing
- Detailed logging for debugging purposes
- User-friendly error messages

### Database Optimization
- Efficient queries with proper joins
- Index-aware query building
- Prepared statements to prevent SQL injection
- Connection pooling considerations

### Security Measures
- Input validation and sanitization
- CSRF protection via Drupal's form API
- Permission-based access controls
- Secure database queries

### Performance Considerations
- Efficient database queries
- Caching strategies where appropriate
- Memory-efficient CSV generation for large datasets
- Optimized form processing

### Code Architecture
- Proper separation of concerns (forms, controllers, services)
- Dependency injection for better testability
- Follows Drupal coding standards
- Modular design for easy maintenance

## Known Issues

### Email Functionality
The email notification system is implemented but may not work in local development environments due to:
- Default PHP mail configuration limitations
- Local SMTP server requirements
- Firewall restrictions in development environments

**Solution**: For production deployment, configure proper SMTP settings or use a mail service provider.

### Database Column Consistency
The module handles cases where optional database columns (like `max_attendees`) may not exist, providing graceful degradation.

## Permissions

- `access event registration`: Allows users to access the registration form
- `administer event registration`: Allows users to manage events and registrations

## Troubleshooting

### Common Issues
1. **Menu Links Not Appearing**: Clear Drupal cache after installing/updating the module
2. **Database Connection Errors**: Verify database configuration and permissions
3. **Email Delivery Issues**: Configure SMTP settings for reliable email delivery
4. **Form Validation Errors**: Check that all required fields are properly filled

### Debugging
- Check Drupal's watchdog logs at `admin/reports/dblog`
- Enable error reporting in development environments
- Verify module dependencies are installed and enabled

## Development Notes

This module demonstrates advanced Drupal development concepts including:
- Custom form implementation with validation
- Database abstraction layer usage
- Service-oriented architecture
- Event-driven programming
- Configuration management
- Security best practices
- Performance optimization techniques

## Support

For support, please contact the module maintainer at: **amlfunction@gmail.com**

**Note**: The email functionality has known issues in the current implementation and requires proper SMTP configuration for reliable operation.

## License

This project is licensed under the GPL v2 or later license.

---

**Project Status**: Production Ready
**Last Updated**: February 2026
**Module Version**: 1.0


