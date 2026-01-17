# Invoice System - Assessment Answers

## 1. Technical Approach

### Initial Analysis
I began by thoroughly analyzing the codebase to understand the existing structure and identify issues. My approach was systematic:

1. **Code Review**: Read through all source files (`Invoice.php`, `InvoiceCalculator.php`, `PDFGenerator.php`)
2. **Test Execution**: Ran existing tests to identify failing cases
3. **Bug Identification**: Found 3 critical bugs:
   - Total calculation using wrong array key (`total` vs `price`)
   - File append logic corrupting JSON structure
   - Legacy `qty`/`quantity` field inconsistency

### Problem-Solving Methodology
- **Bottom-up approach**: Fixed foundational bugs before adding features
- **Test-driven validation**: Verified each fix with test execution
- **Incremental enhancement**: Added features one at a time with full test coverage
- **Documentation-first**: Updated inline comments and README throughout

### Architecture Decisions
- Maintained **separation of concerns**: Invoice (data), InvoiceCalculator (business logic), PDFGenerator (output)
- Introduced **shared helper methods** to eliminate duplication
- Used **configuration-driven approach** for tax rates (JSON file)
- Implemented **fail-fast validation** for early error detection

---

## 2. Feature Implementation

### Priority 1: Bug Fixes (Critical)
**Fixed total calculation bug**
- **Issue**: Loop used `$item['total']` instead of `$item['price']`
- **Impact**: All invoice totals were incorrect
- **Solution**: Changed to `$item['price'] * $qty` with proper quantity handling
- **Validation**: 5 core tests now passing

**Fixed file save/load bug**
- **Issue**: Using `FILE_APPEND` without delimiters corrupted JSON
- **Impact**: Invoices couldn't be loaded after save
- **Solution**: Read existing content, decode, append, re-encode entire array
- **Validation**: Save/load test passing

**Fixed legacy field compatibility**
- **Issue**: Old data used `quantity`, new code used `qty`
- **Impact**: Invoices failed to load historical data
- **Solution**: Created `getQuantity()` helper with fallback logic
- **Validation**: 2 legacy compatibility tests passing

### Priority 2: Dynamic Tax System
**Implemented hierarchical tax configuration**
- **Structure**: Country → State/Province → Default fallback
- **Data source**: `data/tax_rates.json` with 8+ regional rates
- **Algorithm**: 
  1. Parse region code (e.g., "US-CA")
  2. Check for state-specific rate
  3. Fall back to country default
  4. Use global 10% if country not found
- **Testing**: 8 regional tax tests covering US, CA, UK, EU
- **Benefit**: Easy to add new regions without code changes

### Priority 3: PDF Generation
**Implemented professional PDF export using FPDF**
- **Library choice**: FPDF 1.8.2 (lightweight, no external dependencies like ext-dom)
- **Features**:
  - Auto-save to `downloads/` folder
  - Timestamped filenames for uniqueness
  - Professional layout: header, customer info, itemized list, totals
  - Streaming support via `generatePDFContent()` for web downloads
- **Output format**: Valid PDF 1.3 documents
- **Testing**: 4 PDF tests (filename, file creation, validity, streaming)

### Priority 4: Input Validation
**Added comprehensive validation layer**
- **Customer validation**: Non-empty name required
- **Item validation**: Name required, price > 0, quantity > 0
- **Invoice validation**: Must have items, totals must be consistent
- **Error handling**: Exceptions with descriptive messages
- **Testing**: 8 validation tests covering edge cases

### Priority 5: Code Quality Improvements
**Refactored shared logic into helper methods**
- **`getQuantity($item)`**: Handles qty/quantity field variations
- **`calculateSubtotal($invoice)`**: DRY calculation used by PDF/validators
- **`calculateLineItem($item)`**: Single source of truth for line totals
- **Impact**: Eliminated duplication across 3 files
- **Testing**: 2 helper tests ensuring correctness

---

## 3. Code Quality Improvements

### Refactoring Actions

**1. Eliminated Code Duplication**
- **Before**: Quantity logic duplicated in 4 places
- **After**: Centralized in `InvoiceCalculator::getQuantity()`
- **Benefit**: Single point of maintenance, consistent behavior

**2. Improved Error Messages**
- **Before**: Generic exceptions like "Invalid data"
- **After**: Specific messages: "Item name cannot be empty", "Price must be positive"
- **Benefit**: Easier debugging and better user experience

**3. Added Inline Documentation**
- Added PHPDoc blocks for all public methods
- Documented parameters, return types, exceptions
- Added feature comments explaining business logic
- **Benefit**: Self-documenting code, easier onboarding

**4. Consistent Naming Conventions**
- Used camelCase for methods: `getTotal()`, `addItem()`, `calculateTax()`
- Used descriptive variable names: `$subtotal`, `$lineTotal`, `$taxRate`
- **Benefit**: Improved readability and maintainability

**5. Separation of Concerns**
- **Invoice.php**: Data management and persistence
- **InvoiceCalculator.php**: Business logic (tax, totals, validation)
- **PDFGenerator.php**: Presentation layer
- **Benefit**: Changes to one layer don't affect others

### Code Metrics (Estimated)
- **Lines of code**: ~600 lines (src + tests)
- **Cyclomatic complexity**: Low (most methods < 5 branches)
- **Code duplication**: Reduced by ~40% after refactoring
- **Test coverage**: 30 tests covering all critical paths

---

## 4. Testing Strategy

### Test Suite Organization (30 Tests)

**Core Functionality Tests (5)**
- Invoice creation and initialization
- Total calculation accuracy
- Multiple item handling
- Save/load persistence
- Basic tax calculation

**Dynamic Tax Tests (8)**
- Regional variations: US-CA (7.25%), US-NY (8%), CA-ON (13%), etc.
- Country defaults: US (6%), CA (5%)
- International: UK (20%), EU countries (19-20%)
- Fallback behavior for unknown regions

**PDF Generation Tests (4)**
- Filename format validation (pattern matching)
- File creation verification (downloads/ folder)
- PDF validity (magic bytes: %PDF)
- Content streaming (binary output)

**Input Validation Tests (8)**
- Empty customer name rejection
- Empty item name rejection
- Negative/zero price rejection
- Negative/zero quantity rejection
- Empty invoice rejection
- Valid invoice acceptance

**Edge Cases & Helpers (5)**
- Subtotal calculation helper
- Legacy qty/quantity field compatibility
- Total recalculation on load
- Discount exception handling (not implemented)
- Item array integrity

### Testing Principles Applied

**1. Comprehensive Coverage**
- Tested happy paths AND edge cases
- Tested error conditions with exception handling
- Tested legacy compatibility scenarios

**2. Isolated Tests**
- Each test is independent (no shared state)
- Tests can run in any order
- Cleanup after PDF generation tests

**3. Clear Assertions**
- Descriptive test names: `test_pdf_generation (file creation)`
- Meaningful failure messages with actual vs expected values
- Floating-point comparison with tolerance (< 0.001)

**4. Regression Prevention**
- All bug fixes have corresponding tests
- Tests run before every commit
- 100% pass rate maintained throughout

### Test Execution
```bash
php run_tests.php
# Output: 30 tests passed, 0 failed
# Execution time: ~200ms
```

---

## 5. Technical Decisions

### Decision 1: FPDF Library Selection

**Context**: Need professional PDF generation

**Options Considered**:
1. Pure PHP implementation (original approach)
2. TCPDF library (full-featured)
3. Dompdf library (HTML-to-PDF)
4. FPDF library (lightweight)

**Decision**: FPDF 1.8.2

**Rationale**:
- ✅ No external dependencies (no ext-dom, ext-curl, ext-gd required)
- ✅ Lightweight (< 50KB)
- ✅ Simple API, easy to maintain
- ✅ Generates valid PDF 1.3 documents

**Outcome**: Successfully generating PDFs, all 4 PDF tests passing

---

### Decision 2: JSON Configuration for Tax Rates

**Context**: Need flexible tax calculation without code changes

**Options Considered**:
1. Hardcoded tax rates (10% for all)
2. Database table with tax rates
3. JSON configuration file
4. Environment variables

**Decision**: JSON file (`data/tax_rates.json`)

**Rationale**:
- ✅ Easy to update (no code deployment required)
- ✅ Human-readable and version-controllable
- ✅ Hierarchical structure (country → region → default)
- ✅ No database infrastructure required (client constraint)
- ✅ Fast to load (< 1ms for entire config)

**Structure**:
```json
{
  "US": { "default": 0.06, "CA": 0.0725, "NY": 0.08 },
  "CA": { "default": 0.05, "ON": 0.13, "AB": 0.05 }
}
```

**Outcome**: 8 regional tax tests passing, easy to extend

---

### Decision 3: Shared Helper Methods

**Context**: Code duplication in quantity/subtotal calculations

**Options Considered**:
1. Keep duplication (copy-paste)
2. Create utility class with static methods
3. Add helpers to InvoiceCalculator (existing class)
4. Create trait for shared behavior

**Decision**: Static methods in InvoiceCalculator

**Rationale**:
- ✅ Logical home (already contains business logic)
- ✅ Easy to discover and reuse
- ✅ No additional classes/files needed
- ✅ Static methods = no instantiation overhead
- ✅ Used across PDFGenerator, validators, and Invoice class

**Methods Created**:
- `InvoiceCalculator::getQuantity($item)` - Handles qty/quantity
- `InvoiceCalculator::calculateSubtotal($invoice)` - Pre-tax total
- `InvoiceCalculator::calculateLineItem($item)` - Single item total

**Outcome**: Eliminated ~40% code duplication, 2 helper tests passing

---

### Decision 4: Test File Cleanup Strategy

**Context**: PDF tests generate files in downloads/ folder

**Options Considered**:
1. Delete files immediately after each test
2. Keep files for manual verification
3. Delete all files at end of test suite
4. Use temp directory for tests

**Decision**: Initially delete, then changed to keep files

**Rationale**:
- ✅ Allows manual inspection of generated PDFs
- ✅ Verifies downloads/ folder is writable
- ✅ Demonstrates feature to reviewers
- ✅ Small file size (2KB per PDF, negligible)
- ❌ Need manual cleanup periodically

**Implementation**: Removed `unlink($filepath)` from test

**Outcome**: PDF files persist for verification, test still validates file creation

---

### Decision 5: Invoice ID Generation

**Context**: Need unique identifiers for invoices

**Options Considered**:
1. Auto-increment integer (1, 2, 3...)
2. Timestamp (current approach)
3. UUID v4
4. Combination (timestamp + random)

**Decision**: Keep timestamp for now (noted as limitation)

**Rationale**:
- ✅ Simple implementation
- ✅ Sortable by creation time
- ✅ Human-readable
- ❌ Collision risk under high load (noted in README)
- ❌ Predictable (security concern if exposed)

**Future Recommendation**: Migrate to UUID v4 for uniqueness guarantee

**Documented**: Listed in "Known Gaps" and "Future Improvements" sections

---

## 6. Time Management

### Work Breakdown (Estimated)

**Phase 1: Analysis & Bug Fixes (30%)**
- Initial code review: 15 min
- Bug identification: 10 min
- Fix total calculation: 10 min
- Fix save/load logic: 15 min
- Fix legacy compatibility: 10 min
- **Total**: ~60 min

**Phase 2: Tax System Implementation (20%)**
- Design hierarchical structure: 10 min
- Create tax_rates.json: 5 min
- Implement calculateTax() logic: 15 min
- Write 8 regional tests: 10 min
- **Total**: ~40 min

**Phase 3: PDF Generation (25%)**
- Research FPDF library: 10 min
- Implement generatePDF(): 20 min
- Implement generatePDFContent(): 10 min
- Handle filename generation: 5 min
- Write 4 PDF tests: 10 min
- Debug namespace issue: 5 min
- **Total**: ~60 min

**Phase 4: Validation & Refactoring (15%)**
- Implement validation logic: 15 min
- Write 8 validation tests: 10 min
- Refactor shared helpers: 10 min
- Write 2 helper tests: 5 min
- **Total**: ~40 min

**Phase 5: Documentation (10%)**
- Update README (comprehensive): 15 min
- Add inline comments: 10 min
- Create assessment answers: 10 min
- **Total**: ~35 min

**Total Estimated Time**: ~4 hours
