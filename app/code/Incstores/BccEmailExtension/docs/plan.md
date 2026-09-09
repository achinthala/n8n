# Email BCC Implementation Plan for Magento 2

## Project Overview
Add BCC functionality to ALL emails sent from Magento 2 (core, custom, and third-party modules).

## Email-Sending Modules Identified (7 active modules):

### Custom Modules (4):
1. **Dcw\TaxCertificate** - Tax certificate notifications
   - File: `app/code/Dcw/TaxCertificate/Model/CertificateProcessor.php:189`
   - Usage: `$transport->sendMessage();`

2. **Dcw\CircularDesignerTool** - Design tool quote requests
   - File: `app/code/Dcw/CircularDesignerTool/Controller/Index/Quote.php:119`
   - Usage: `$transport->sendMessage();`

3. **Dcw\ShoppingCart** - Quote notifications
   - File: `app/code/Dcw/ShoppingCart/Controller/Cart/UpdatePost.php:337`
   - Usage: `$transport->getTransport()->sendMessage();`

4. **Dcw\Notifications** - Availability notifications (cron-based)

### Third-Party Modules (3):
5. **Amasty\RequestQuote** - Quote request emails
   - File: `vendor/amasty/module-request-quote/Model/Email/Sender.php:210`

6. **Amasty\Xnotif** - Stock/price alerts + error notifications
   - Files: Multiple email senders in the module

7. **Amasty\FAQ** - Product question emails
   - File: `vendor/amasty/module-faq-product-questions/Utils/Email.php:114`

## Implementation Architecture

### Interception Point:
`Magento\Framework\Mail\TransportInterface::sendMessage`

**Why this approach:**
- 100% email coverage (all emails MUST go through TransportInterface)
- No core file modifications
- Works with all identified modules
- Follows Magento plugin architecture

## Phase 1: Hardcoded BCC Implementation

### Objective:
Create a working BCC system with hardcoded email addresses for immediate testing.

### Files to Create:
```
app/code/Incstores/EmailBcc/
├── registration.php                               CREATED
├── etc/
│   ├── module.xml                                 CREATED
│   └── di.xml                                     CREATED
└── Plugin/TransportInterfacePlugin.php            NEEDS MODIFICATION
```

### Files to Remove/Modify:
- Remove: `Helper/Data.php` (not needed in Phase 1)
- Remove: `etc/adminhtml/system.xml` (not needed in Phase 1)
- Remove: `etc/acl.xml` (not needed in Phase 1)
-  Modify: `Plugin/TransportInterfacePlugin.php` (remove Helper dependency, hardcode emails)

### Hardcoded Configuration:
```php
// In Plugin/TransportInterfacePlugin.php
private const HARDCODED_BCC_EMAILS = [
    'admin@flooringinc.com',
    'manager@flooringinc.com'
    // Add more as needed
];
```

### Testing Strategy:
1. Test with Dcw\TaxCertificate (easiest to trigger)
2. Test with Dcw\CircularDesignerTool quote requests
3. Verify BCC appears in email headers
4. Check email delivery logs

## Phase 2: Admin Configuration Implementation (Future)

### Additional Files to Create:
```
app/code/Incstores/EmailBcc/
├── Helper/Data.php                               CREATED (ready for Phase 2)
├── etc/adminhtml/system.xml                       CREATED (ready for Phase 2)
└── etc/acl.xml                                   CREATED (ready for Phase 2)
```

### Admin Features:
- **Location**: Admin Panel > Stores > Configuration > Incstores > Email BCC Configuration
- **Enable/Disable toggle**: Global BCC functionality control
- **Email list field**: Semicolon-separated email addresses
- **Validation**: Email format validation
- **Scope support**: Default/Website/Store level configuration

### Migration Path:
1. Modify `Plugin/TransportInterfacePlugin.php` to use Helper instead of hardcoded values
2. Enable admin configuration files
3. Test configuration changes
4. Deploy to production

## Current Status:
- Phase 1 files created (with Phase 2 extras that need removal)
-  Plugin needs modification to remove Helper dependency
- Ready for Magento module enable and testing

## Next Immediate Steps:
1. Modify Plugin to remove Helper dependency and use hardcoded emails
2. Remove Phase 2 files temporarily
3. Run Magento commands: enable, setup:upgrade, di:compile, cache:flush
4. Test with existing email-sending modules

## Success Criteria Phase 1:
- All emails from identified modules include hardcoded BCC recipients
- No admin configuration required
- No broken functionality in existing email flows
- Email delivery logs show BCC recipients receiving emails
