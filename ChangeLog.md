# CHANGELOG FOURNREADINVOICE FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## [Unreleased]

### Changed
- Clean legacy MYOBJECT/MYMODULE template references
- Replace hardcoded permission descriptions with translation keys
- Update numbering model prefix from MYOBJECT to FRF
- Improve lang file with proper translations for permissions and module description

### Added
- Enhanced build workflow with release trigger support
- Version bump workflow with PR creation
- Additional translations in fr_FR lang file

## 1.2

### Added
- Build workflow for releases (6343171)
- Unit tests for FileUploadValidator (a3b811e)
- Notes functionality for entries (12bb8ad)
- Linked objects support (4cffb2a)
- Core module behavior and invoice creation from OCR (a37dbd8)

### Changed
- Updated documentation and README (a3b811e)
- Removed unused code (a3b811e)

## 1.0

Initial version

### Features
- Automatic reading of supplier invoice files
- Integration with Dolibarr's Email Collector module
- Automatic processing via cron job (every 3 hours)
- File processing status tracking
- OCR-based text extraction from PDF files
- Automatic supplier invoice creation from purchase orders
