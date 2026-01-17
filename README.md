# Invoice System - Coding Assessment

**Last Updated:** January 17, 2026

## Quick Start

### Run Tests
```bash
php run_tests.php
```

**Current Status:** 29 passing, 0 failing (php run_tests.php)

### Create Invoice (Example)
```php
require_once 'src/Invoice.php';

$invoice = new Invoice("Customer Name");
$invoice->addItem("Product", 10.00, 2);
echo "Total: $" . $invoice->getTotal() . "\n";
```

## Project Structure

```
├── src/
│   ├── Invoice.php           - Main invoice class
│   ├── InvoiceCalculator.php - Tax and business logic helpers
│   └── PDFGenerator.php      - PDF export (pure PHP, no deps)
├── data/
│   ├── invoices.json         - Stored invoices
│   └── tax_rates.json        - Tax rate configuration
└── tests/
    └── InvoiceTest.php       - Test suite
```

## Implemented Features

- Fixed total calculation and file append logic
- Dynamic tax loading from `data/tax_rates.json` with fallbacks
- Pure-PHP PDF generation (valid PDF 1.4) plus HTML export
- Input validation for customer and items; invoice integrity checks
- Shared helpers for quantities/subtotals across PDF/HTML/validators

## Known Gaps

- Discount rules still unspecified; applyDiscount/applyBusinessRules placeholders remain
- Invoice ID still timestamp-based (collision risk under high load)


## Technical Decisions

- **Storage:** JSON files (client has no database)
- **PHP Version:** 7.4+ required
- **Dependencies:** Composer packages now allowed for PDF generation (policy updated)

## Current Validation / Behavior

✓ Invoice creation, add items, totals, save/load

✓ Dynamic tax calculations by region with defaults

✓ PDF/HTML export without external dependencies

✓ Input validation and legacy qty/quantity compatibility

## Testing Notes

- Tests in `tests/InvoiceTest.php` (29 passing)
- Edge cases include legacy `quantity` key and subtotal helper
- Performance not tested with large datasets

## Configuration

### Tax Rates (data/tax_rates.json)
Currently not being used. Should replace hardcoded 10% rate in InvoiceCalculator.

Format:
```json
{
  "US": { "CA": 0.0725, "NY": 0.08, ... },
  "CA": { "ON": 0.13, "BC": 0.12, ... }
}
```

## Summary

- Core bugs fixed (totals, file append, JSON data)
- Implemented tax config, PDF export, validation, helper refactors
- Remaining: discount rules + stronger ID generation
