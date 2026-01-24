# FournReadInvoice - Dolibarr Module

Module for automatic supplier invoice reading. Processes received files (via email collector or other means) and integrates them into Dolibarr.

## Features

- Automatic reading of supplier invoice files
- Integration with Dolibarr's Email Collector module
- Automatic processing via cron job (every 3 hours)
- File processing status tracking

---

## Installation

### Prerequisites

- Dolibarr >= 19.0
- PHP >= 7.1
- Email Collector module enabled (optional, for email reception)
- For OCR functionality (PDF text extraction):
  - Tesseract OCR with French language pack
  - ImageMagick
  - Ghostscript

#### System dependencies installation

**Debian/Ubuntu:**
```bash
apt update
apt install tesseract-ocr tesseract-ocr-fra imagemagick ghostscript -y
```

**Alpine Linux:**
```bash
apk add --update tesseract-ocr tesseract-ocr-dev imagemagick ghostscript
```

**ImageMagick Policy (required for PDF processing):**
Edit `/etc/ImageMagick-6/policy.xml` and ensure PDF access is enabled:
```xml
<policy domain="coder" rights="read|write" pattern="PDF" />
```

### Module Installation

1. Copy the `fournreadinvoice` folder into `htdocs/custom/`
2. Enable the module in **Setup > Modules > Other**

---

## Usage

### How It Works

1. Invoice files are collected (manually or via email collector)
2. The cron job processes files every 3 hours
3. Invoices are created in Dolibarr
4. File status is updated (Processed or Error)

### File Statuses

| Status | Code | Description |
|--------|------|-------------|
| Draft | 0 | File pending processing |
| Validated | 1 | File processed successfully |
| Error | 8 | Error during processing |

---

## Architecture

### File Structure

```
fournreadinvoice/
├── class/
│   ├── fournreadfile.class.php         # File object to process
│   └── actions_fournreadinvoice.class.php # Hooks
├── core/modules/
│   ├── modFournReadInvoice.class.php   # Module descriptor
│   └── fournreadinvoice/               # Numbering models
│       ├── mod_fournreadfile_standard.php
│       └── mod_fournreadfile_advanced.php
├── admin/
│   ├── setup.php                       # Configuration
│   └── about.php                       # About
├── lib/
│   └── fournreadinvoice.lib.php        # Common functions
├── sql/                                # Database tables
└── langs/                              # Translations
```

### Main Class

#### `Fournreadfile`
Represents an invoice file to process:

| Field | Type | Description |
|-------|------|-------------|
| `rowid` | int | Technical ID |
| `ref` | varchar | Unique reference |
| `label` | varchar | Label |
| `filename` | varchar | File name |
| `status` | int | Status (0, 1, 8) |
| `note_public` | text | Notes |

### Cron Job

The module automatically configures a cron job:
- **Frequency**: Every 3 hours
- **Method**: `Fournreadfile::doScheduledJob()`
- **Action**: Processes pending files

### Email Collector Hook

The module can integrate with the `emailcollectorcard` hook to automatically receive invoices by email.

---

## Development

### SQL Table

```
llx_fournreadinvoice_fournreadfile  # Files to process
```

### Dolibarr Tables Used

| Table | Usage |
|-------|-------|
| `llx_facture_fourn` | Created supplier invoices |
| `llx_societe` | Suppliers |

---

## License

GPLv3 - See COPYING file
