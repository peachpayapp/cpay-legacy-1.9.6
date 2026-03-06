# Stripe to ConvesioPay Subscription Migration

This migration system allows seamless migration of WooCommerce subscriptions from Stripe to ConvesioPay without requiring customer interaction.

## Overview

The migration system scans existing Stripe subscriptions, retrieves the corresponding stored payment methods from ConvesioPay (which were previously migrated from Stripe), and updates the WooCommerce subscription metadata to use ConvesioPay for future payments.

**Important**: This migration system preserves all original Stripe data and configurations. No Stripe subscriptions are cancelled or modified, ensuring merchants can easily switch back to Stripe if needed.

## Key Features

- **Seamless Migration**: No customer interaction required
- **Bulk Processing**: Migrate multiple subscriptions at once
- **Individual Migration**: Process subscriptions one by one
- **Status Tracking**: Monitor migration progress and results
- **Error Handling**: Comprehensive error reporting and retry mechanisms
- **Revert Functionality**: Rollback migrations if issues arise
- **Data Preservation**: All original Stripe data and configurations remain intact
- **No API Dependencies**: No Stripe API integration required

## Prerequisites

### Required Plugins
- **WooCommerce Stripe Gateway**: Must be installed (for subscription detection)
- **WooCommerce Subscriptions**: Required for subscription management
- **ConvesioPay WooCommerce Integration**: This plugin

### ConvesioPay Configuration
The migration system uses your existing ConvesioPay configuration:
- API credentials from plugin settings
- Merchant account configuration
- Test/Live mode settings

**Note**: No Stripe API integration is required. The system only needs to detect Stripe subscriptions in WooCommerce.

## Database Schema

The migration system creates a custom table `{prefix}cpay_gateway_migration` with the following structure:

```sql
CREATE TABLE {prefix}cpay_gateway_migration (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    subscription_id bigint(20) unsigned NOT NULL,
    customer_email varchar(255) NOT NULL,
    convesiopay_customer_id varchar(255) NOT NULL,
    migration_status enum('pending','in_progress','completed','failed','reverted') NOT NULL DEFAULT 'pending',
    original_payment_method varchar(255) DEFAULT NULL,
    original_payment_method_title varchar(255) DEFAULT NULL,
    original_recurring_detail_reference text DEFAULT NULL,
    original_shopper_reference varchar(255) DEFAULT NULL,
    convesiopay_stored_payment_method_id varchar(255) DEFAULT NULL,
    error_message text DEFAULT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reverted_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY subscription_id (subscription_id),
    KEY migration_status (migration_status),
    KEY customer_email (customer_email)
);
```

## Migration Statuses

- **pending**: Ready for migration
- **in_progress**: Currently being processed
- **completed**: Successfully migrated to ConvesioPay
- **failed**: Migration failed (can be retried)
- **reverted**: Migration was reverted back to Stripe

## Migration Process

### 1. Scanning Stripe Subscriptions
- Scans all active WooCommerce subscriptions with Stripe payment method
- Creates migration records for each subscription
- Validates customer email and ConvesioPay customer ID mapping

### 2. Migration Execution
For each subscription:
1. **Retrieve Stored Payment Methods**: Calls ConvesioPay API to get stored payment methods for the customer
2. **Update Subscription Metadata**: 
   - Changes payment method to ConvesioPay
   - Sets `_cpay_recurringDetailReference` to the stored payment method ID
   - Sets `_cpay_shopper_reference` to the original ConvesioPay customer ID (MD5 of email)
3. **Preserve Original Data**: Stores original payment method details for potential reversion

### 3. Simplified Migration Approach
The system uses a **simple migration** approach:

1. **Migrate**: Change payment method to ConvesioPay (Stripe subscription remains intact)
2. **Test**: Verify that ConvesioPay renewal payments work correctly
3. **Revert**: If issues arise, revert back to Stripe payment method

**No Stripe Cancellation**: Since WooCommerce controls subscription renewals, there's no risk of double billing. The original Stripe subscription data remains intact.

## API Integration

### ConvesioPay API
- **Endpoint**: Uses configured ConvesioPay API endpoint
- **Authentication**: Uses existing ConvesioPay API credentials
- **Stored Payment Methods**: Retrieves customer's stored payment methods

## Admin Interface

### Migration Dashboard
- **Migration Statistics**: Displays counts by status
- **Action Buttons**: Scan, bulk migrate, bulk revert
- **Migration Table**: Lists all migrations with individual actions

### Available Actions
- **Scan Stripe Subscriptions**: Find subscriptions eligible for migration
- **Migrate**: Process individual or bulk migrations
- **Revert**: Rollback migrations to original Stripe payment method

## Error Handling

### Common Error Scenarios
1. **API Connection Issues**: Detailed error messages with troubleshooting guidance
2. **Missing Payment Methods**: Handles cases where stored payment methods aren't found
3. **Invalid Customer Mapping**: Validates customer email to ConvesioPay customer ID mapping

### Error Recovery
- **Retry Failed Migrations**: Individual retry buttons for failed migrations
- **Revert Functionality**: Rollback problematic migrations
- **Detailed Error Messages**: Comprehensive error reporting for troubleshooting

## Security Considerations

### Data Protection
- **Original Data Preservation**: Stores original payment method details securely
- **Audit Trail**: Complete migration history with timestamps
- **Nonce Verification**: All AJAX operations use WordPress nonces

### API Security
- **Credential Management**: Uses existing plugin API credentials
- **Error Sanitization**: Prevents sensitive data exposure in error messages
- **Access Control**: Admin-only access to migration interface

## Testing Recommendations

### Pre-Migration Testing
1. **Test Environment**: Use test mode for initial testing
2. **Small Batch**: Start with a few subscriptions
3. **Payment Verification**: Verify ConvesioPay renewal payments work
4. **Revert Testing**: Test revert functionality before proceeding

### Production Migration
1. **Backup**: Complete database backup before migration
2. **Staged Rollout**: Migrate in small batches
3. **Monitoring**: Monitor renewal payments after migration
4. **Data Preservation**: All original Stripe data remains intact

## Troubleshooting

### Migration Issues
- **Customer Mapping**: Verify customer email to ConvesioPay customer ID mapping
- **Payment Methods**: Check if stored payment methods exist in ConvesioPay
- **API Connectivity**: Verify ConvesioPay API connectivity and credentials
- **Database Permissions**: Ensure proper database permissions for table creation

### Revert Issues
- **Original Data**: Check if original payment method data was preserved
- **Subscription Status**: Ensure subscription is in a valid state for reversion
- **Database Integrity**: Verify migration table data integrity

## Post-Migration

### Verification Steps
1. **Renewal Payments**: Monitor next renewal payments
2. **Customer Experience**: Verify no disruption to customer access
3. **Billing Accuracy**: Confirm billing amounts and schedules are correct
4. **Webhook Processing**: Ensure ConvesioPay webhooks are working

### Data Management
1. **Keep All Data**: All original Stripe data and configurations remain intact
2. **Migration Records**: Migration history is preserved for audit and troubleshooting purposes
3. **Easy Reversion**: Can easily revert back to Stripe if needed

**Note**: This migration system is designed to be non-destructive. All original Stripe data, configurations, and subscription records remain intact, ensuring merchants can easily switch back to Stripe if needed.

## Support

For issues or questions:
1. Check the error messages in the admin interface
2. Review the migration logs and status
3. Test revert functionality if needed
4. Contact support with detailed error information and migration IDs 