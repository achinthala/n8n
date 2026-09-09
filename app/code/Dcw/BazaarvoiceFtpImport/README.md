# Bazaarvoice FTP Import Module

This Magento 2 module provides functionality to automatically import Bazaarvoice reviews feed from an SFTP server. It supports downloading zip files, extracting them, and processing the contents based on file names. All files are automatically saved to `pub/media/bazaarvoice/` directory for easy access and cloud compatibility.

## Features

- **SFTP Configuration**: Secure SFTP connection settings with credential copying from official Bazaarvoice module
- **Cloud Compatible**: Uses Magento's DirectoryList constants for robust path handling across all environments
- **Hardcoded Paths**: No configuration needed - automatically uses `pub/media/bazaarvoice/` directory
- **File Pattern Matching**: Support for file pattern matching (e.g., *.xml.gz, *.zip, bv_*.xml.gz)
- **Multiple File Types**: Supports both ZIP and GZIP compressed files
- **Automatic Decompression**: Automatically extracts zip files and decompresses gzip files
- **Cron Job Support**: Automated import and GMC conversion via cron jobs with configurable schedules
- **Manual Operations**: Admin interface for manual import and GMC conversion execution
- **Connection Testing**: Built-in SFTP connection testing with detailed feedback
- **Error Handling**: Comprehensive error handling and logging
- **File Management**: Automatic directory creation and file organization in `pub/media/bazaarvoice/`
- **GMC Review Conversion**: Convert Bazaarvoice reviews to Google Merchant Center format
- **CLI Commands**: Command-line interface for import and GMC conversion operations
- **Credential Management**: Automatic copying of SFTP credentials from official Bazaarvoice module
- **Disabled Fields**: Read-only credential fields that auto-populate from vendor module
- **Visual Feedback**: Color-coded success/error messages in admin interface

## Supported File Types

- **ZIP Files** (*.zip): Standard ZIP archives containing multiple files
- **GZIP Files** (*.gz, *.xml.gz): Compressed single files, commonly used for Bazaarvoice XML feeds
- **XML Files**: Decompressed XML files (e.g., bv_incstores_standard_client_feed.xml)

## Installation

1. Copy the module files to `app/code/Dcw/BazaarvoiceFtpImport/`
2. Enable the module:
   ```bash
   php bin/magento module:enable Dcw_BazaarvoiceFtpImport
   ```
3. Run setup upgrade:
   ```bash
   php bin/magento setup:upgrade
   ```
4. Compile and deploy:
   ```bash
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```
5. Clear cache:
   ```bash
   php bin/magento cache:clean
   ```

**Note:** 
- **SFTP Only**: This module uses SFTP exclusively for secure file transfer
- **No Additional Extensions**: Uses Magento's native SFTP class based on phpseclib
- **Cloud Ready**: Fully compatible with Magento Cloud and all deployment environments
- **Automatic Directory Creation**: Creates `pub/media/bazaarvoice/` and `pub/media/bazaarvoice/extracted/` directories automatically

## Configuration

Navigate to **Stores > Configuration > Catalog > Bazaarvoice FTP Import** to configure the module.

### SFTP Settings

- **Enable SFTP Import**: Enable/disable the module
- **Copy Credentials**: Button to copy SFTP credentials from official Bazaarvoice module
- **SFTP Host**: Server hostname or IP address (read-only, auto-populated from vendor module)
- **SFTP Username**: Server username (read-only, auto-populated from vendor module)
- **SFTP Password**: Server password (read-only, auto-populated from vendor module)
- **Remote Path**: Remote directory path on SFTP server (e.g., /feeds/)
- **File Pattern**: File pattern to match (e.g., *.xml.gz, *.zip, bv_*.xml.gz)

### Import Settings

- **Files Location**: All files are automatically saved to `pub/media/bazaarvoice/` directory
- **Extracted Files**: Decompressed XML files are saved to `pub/media/bazaarvoice/extracted/` directory
- **Enable Auto Import**: Enable automatic import via cron job
- **Cron Schedule**: Cron schedule expression (e.g., 0 2 * * * for daily at 2 AM)
- **Keep Original Files**: Keep original zip files after extraction (for debugging)

### GMC Review Conversion Settings

- **GMC Files Location**: GMC XML files are automatically saved to `pub/media/bazaarvoice/` directory
- **Enable GMC Conversion**: Enable/disable Google Merchant Center review conversion
- **Publisher Name**: Your company name for GMC feed
- **Aggregator Name**: Review aggregator name (e.g., Bazaarvoice)
- **Allowed Review Statuses**: Comma-separated list of review statuses to include
- **Default Rating**: Default rating value when not specified (1-5)
- **Rating Range**: Maximum rating value (e.g., 5 for 1-5 scale)
- **GMC Cron Schedule**: Cron schedule for GMC conversion (e.g., 0 3 * * * for daily at 3 AM)

### Actions

- **Test SFTP Connection**: Test the SFTP connection with current settings (shows file count and detailed feedback)
- **Manual Import**: Execute import manually with detailed progress feedback
- **Manual GMC Convert**: Convert existing XML files to GMC format with output directory information

## Usage

### Automatic Import

1. Configure SFTP settings in the admin panel
2. Enable "Auto Import" in Import Settings
3. Set the desired cron schedule
4. The module will automatically download and process files to `pub/media/bazaarvoice/` according to the schedule

### Manual Import

1. Configure SFTP settings in the admin panel
2. Click "Test SFTP Connection" to verify SFTP settings
3. Click "Manual Import" to run the import manually
4. Files will be saved to `pub/media/bazaarvoice/` and `pub/media/bazaarvoice/extracted/`

### Manual GMC Conversion

1. Configure GMC settings in the admin panel
2. Ensure GMC conversion is enabled
3. Click "Manual GMC Convert" to convert existing XML files to GMC format
4. GMC XML files will be saved to `pub/media/bazaarvoice/` directory

### CLI Commands

The module provides command-line interface for both import and GMC conversion operations:

#### Import Commands

```bash
# Basic import
php bin/magento bazaarvoice:ftp:import

# Import for specific store
php bin/magento bazaarvoice:ftp:import --store-id=1
```

#### GMC Conversion Commands

```bash
# Basic GMC conversion
php bin/magento bazaarvoice:gmc:convert

# GMC conversion for specific store
php bin/magento bazaarvoice:gmc:convert --store-id=1
```

#### List Available Commands

```bash
# List all bazaarvoice commands
php bin/magento list | grep bazaarvoice
```

### File Processing

The module processes files as follows:

1. **Create Directories**: Automatically creates `pub/media/bazaarvoice/` and `pub/media/bazaarvoice/extracted/` directories
2. **Connect to SFTP**: Establishes secure connection using configured credentials
3. **List Files**: Retrieves list of files matching the specified pattern
4. **Download Files**: Downloads each matching file to `pub/media/bazaarvoice/` directory
5. **Process Files**: 
   - If the file is a zip file, extracts its contents to `pub/media/bazaarvoice/extracted/`
   - If the file is a gzip file (e.g., .xml.gz), decompresses it to `pub/media/bazaarvoice/extracted/`
6. **Remove Compressed Files**: Removes the original compressed file (unless configured to keep it)
7. **Keep on SFTP**: Files remain on SFTP server (not deleted)
8. **GMC Conversion**: If enabled, converts XML files to Google Merchant Center format in `pub/media/bazaarvoice/`
9. **Log Results**: Logs all operations and results

### GMC Conversion Process

The GMC conversion process:

1. **Load XML**: Parses the Bazaarvoice XML file from `pub/media/bazaarvoice/extracted/`
2. **Gather Reviews**: Collects reviews by product SKU
3. **Magento Lookup**: Maps reviews to Magento products (including configurable products)
4. **Generate GMC XML**: Creates Google Merchant Center compliant XML
5. **Output File**: Saves as `gmc_bazaarvoice_reviews_feed.xml` in `pub/media/bazaarvoice/` directory

## File Structure

```
app/code/Dcw/BazaarvoiceFtpImport/
├── Block/
│   └── Adminhtml/
│       └── System/
│           └── Config/
│               ├── CopyCredentialsButton.php
│               ├── DisabledHost.php
│               ├── DisabledPassword.php
│               ├── DisabledUsername.php
│               ├── ManualGmcConvert.php
│               ├── ManualImport.php
│               └── TestConnection.php
├── Console/
│   └── Command/
│       ├── GmcConvertCommand.php
│       └── ImportCommand.php
├── Controller/
│   └── Adminhtml/
│       └── Import/
│           ├── CopyCredentials.php
│           ├── Execute.php
│           ├── GmcConvert.php
│           └── TestConnection.php
├── Cron/
│   ├── GmcConvertCron.php
│   └── ImportCron.php
├── Model/
│   ├── FileProcessor.php
│   ├── FtpService.php
│   ├── GmcReviewConverter.php
│   └── ImportService.php
├── etc/
│   ├── acl.xml
│   ├── adminhtml/
│   │   ├── routes.xml
│   │   └── system.xml
│   ├── config.xml
│   ├── console.xml
│   ├── crontab.xml
│   ├── di.xml
│   └── module.xml
├── registration.php
└── README.md
```

## Logging

The module logs all operations to the Magento system log. Check `var/log/system.log` for detailed information about:

- SFTP connection attempts
- Directory creation in `pub/media/bazaarvoice/`
- File downloads to `pub/media/bazaarvoice/`
- File extractions to `pub/media/bazaarvoice/extracted/`
- GMC conversion to `pub/media/bazaarvoice/`
- Errors and warnings
- Import statistics

## Troubleshooting

### Common Issues

1. **SFTP Connection Failed - General Issues**
   - Verify SFTP credentials and hostname
   - Ensure firewall allows SSH connections (port 22)
   - Verify Bazaarvoice SFTP server is accessible
   - Check Magento logs for detailed error messages

2. **Permission Denied**
   - Check `pub/media/bazaarvoice/` directory permissions
   - Ensure Magento has write access to `pub/media/` directory
   - Verify directory creation is successful in logs

3. **Files Not Found**
   - Verify remote path is correct
   - Check file pattern matches actual files
   - Ensure files exist on the SFTP server

4. **Cron Job Not Running**
   - Verify cron is configured and running
   - Check cron schedule expression
   - Enable auto import in configuration

5. **GMC Conversion Issues**
   - Ensure GMC conversion is enabled in configuration
   - Check that XML files exist in `pub/media/bazaarvoice/extracted/` directory
   - Verify `pub/media/bazaarvoice/` directory has write permissions
   - Check system logs for detailed error messages

### Debug Mode

Enable "Keep Original Files" in Import Settings to retain zip files in `pub/media/bazaarvoice/` for debugging purposes.

## Security Considerations

- SFTP passwords are encrypted in the database
- Uses secure SFTP protocol exclusively
- Regularly rotate SFTP credentials
- Monitor import logs for suspicious activity
- Files are stored in `pub/media/bazaarvoice/` with proper permissions

## Support

For issues or questions, please check the system logs and ensure all configuration settings are correct. The module includes comprehensive error handling and logging to help diagnose issues.
