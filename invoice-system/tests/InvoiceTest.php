<?php

/**
 * Invoice System Tests
 *
 * Test suite for invoice creation, calculation, storage, and PDF generation
 * 
 * Run with: php run_tests.php
 */

// Load composer dependencies for PDF generation
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/Invoice.php';
require_once __DIR__ . '/../src/InvoiceCalculator.php';
require_once __DIR__ . '/../src/PDFGenerator.php';

class InvoiceTest {

    private $testsPassed = 0;
    private $testsFailed = 0;
    private $failures = [];

    /**
     * Run all tests
     */
    public function runAll() {
        echo "Running Invoice Tests...\n";
        echo str_repeat("=", 50) . "\n\n";

        $this->test_create_invoice();
        $this->test_calculate_total();
        $this->test_add_multiple_items();
        $this->test_save_and_load();
        $this->test_tax_calculation();
        $this->test_dynamic_tax_rates();
        $this->test_pdf_generation();
        $this->test_pdf_content_generation();
        $this->test_input_validation();
        $this->test_calculate_subtotal_helper();
        $this->test_load_invoice_qty_mismatch();
        $this->test_apply_discount_throws_exception();

        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Tests Passed: " . $this->testsPassed . "\n";
        echo "Tests Failed: " . $this->testsFailed . "\n";

        if ($this->testsFailed > 0) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - " . $failure . "\n";
            }
        }

        return $this->testsFailed === 0;
    }

    /**
     * Test: Create basic invoice
     * Status: PASSING ✓
     */
    private function test_create_invoice() {
        $invoice = new Invoice("Test Customer");

        $this->assert(
            $invoice->getCustomer() === "Test Customer",
            "test_create_invoice",
            "Customer name should match"
        );
    }

    /**
     * Test: Calculate total for single item
     * Status: FIXED ✓
     */
    private function test_calculate_total() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Test Item", 10.00, 2);

        $expected = 20.00;
        $actual = $invoice->getTotal();

        $this->assert(
            $actual === $expected,
            "test_calculate_total",
            "Total should be $20.00, got $" . number_format($actual, 2)
        );
    }

    /**
     * Test: Add multiple items and calculate total
     * Status: FIXED ✓
     */
    private function test_add_multiple_items() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Item 1", 10.00, 2);
        $invoice->addItem("Item 2", 15.00, 3);
        $invoice->addItem("Item 3", 5.00, 1);

        $expected = 20.00 + 45.00 + 5.00; // = 70.00
        $actual = $invoice->getTotal();

        $this->assert(
            $actual === $expected,
            "test_add_multiple_items",
            "Total should be $70.00, got $" . number_format($actual, 2)
        );
    }

    /**
     * Test: Save invoice to file and load it back
     * Status: FIXED ✓
     */
    private function test_save_and_load() {
        $testFile = __DIR__ . '/../data/test_invoices.json';

        // Clean up first
        if (file_exists($testFile)) {
            unlink($testFile);
        }

        // Create and save first invoice
        $invoice1 = new Invoice("Customer 1");
        $invoice1->addItem("Item A", 100.00, 1);
        $invoice1->saveToFile($testFile);

        // Create and save second invoice
        $invoice2 = new Invoice("Customer 2");
        $invoice2->addItem("Item B", 200.00, 1);
        $invoice2->saveToFile($testFile);

        // Try to load first invoice
        try {
            $loaded = Invoice::loadFromFile($invoice1->getId(), $testFile);
            $this->assert(
                $loaded->getCustomer() === "Customer 1",
                "test_save_and_load",
                "Should be able to load first invoice"
            );
        } catch (Exception $e) {
            $this->assert(
                false,
                "test_save_and_load",
                "Failed to load invoice: " . $e->getMessage()
            );
        }

        // Clean up
        if (file_exists($testFile)) {
            unlink($testFile);
        }
    }

    /**
     * Test: Tax calculation with dynamic rates
     * Status: PASSING ✓
     */
    private function test_tax_calculation() {
        $subtotal = 100.00;
        $tax = InvoiceCalculator::calculateTax($subtotal, 'US-CA');

        // Now dynamically loaded from tax_rates.json: US-CA = 7.25%
        $expected = 7.25;

        // Use approximate equality for floating point comparison
        $this->assert(
            abs($tax - $expected) < 0.001,
            "test_tax_calculation",
            "Tax should be $7.25 (7.25%), got $" . number_format($tax, 2)
        );
    }

    /**
     * Test: Dynamic tax rate loading from tax_rates.json
     * Status: PASSING ✓
     */
    private function test_dynamic_tax_rates() {
        // Test 1: US-CA specific rate (7.25%)
        $tax1 = InvoiceCalculator::calculateTax(100.00, 'US-CA');
        $this->assert(
            abs($tax1 - 7.25) < 0.001,
            "test_dynamic_tax_rates (US-CA)",
            "US-CA should be 7.25%, got $" . number_format($tax1, 2)
        );

        // Test 2: US-NY specific rate (8%)
        $tax2 = InvoiceCalculator::calculateTax(100.00, 'US-NY');
        $this->assert(
            abs($tax2 - 8.00) < 0.001,
            "test_dynamic_tax_rates (US-NY)",
            "US-NY should be 8%, got $" . number_format($tax2, 2)
        );

        // Test 3: US default rate (6%) - state doesn't exist in config
        $tax3 = InvoiceCalculator::calculateTax(100.00, 'US-XX');
        $this->assert(
            abs($tax3 - 6.00) < 0.001,
            "test_dynamic_tax_rates (US default)",
            "US default should be 6%, got $" . number_format($tax3, 2)
        );

        // Test 4: Canada-ON specific rate (13%)
        $tax4 = InvoiceCalculator::calculateTax(100.00, 'CA-ON');
        $this->assert(
            abs($tax4 - 13.00) < 0.001,
            "test_dynamic_tax_rates (CA-ON)",
            "CA-ON should be 13%, got $" . number_format($tax4, 2)
        );

        // Test 5: Canada-AB specific rate (5%)
        $tax5 = InvoiceCalculator::calculateTax(100.00, 'CA-AB');
        $this->assert(
            abs($tax5 - 5.00) < 0.001,
            "test_dynamic_tax_rates (CA-AB)",
            "CA-AB should be 5%, got $" . number_format($tax5, 2)
        );

        // Test 6: UK default rate (20%)
        $tax6 = InvoiceCalculator::calculateTax(100.00, 'UK');
        $this->assert(
            abs($tax6 - 20.00) < 0.001,
            "test_dynamic_tax_rates (UK)",
            "UK should be 20%, got $" . number_format($tax6, 2)
        );

        // Test 7: EU-FR specific rate (20%)
        $tax7 = InvoiceCalculator::calculateTax(100.00, 'EU-FR');
        $this->assert(
            abs($tax7 - 20.00) < 0.001,
            "test_dynamic_tax_rates (EU-FR)",
            "EU-FR should be 20%, got $" . number_format($tax7, 2)
        );

        // Test 8: EU-DE specific rate (19%)
        $tax8 = InvoiceCalculator::calculateTax(100.00, 'EU-DE');
        $this->assert(
            abs($tax8 - 19.00) < 0.001,
            "test_dynamic_tax_rates (EU-DE)",
            "EU-DE should be 19%, got $" . number_format($tax8, 2)
        );
    }

    /**
     * Simple assertion helper
     */
    private function assert($condition, $testName, $message) {
        if ($condition) {
            $this->testsPassed++;
            echo "✓ " . $testName . "\n";
        } else {
            $this->testsFailed++;
            echo "✗ " . $testName . " - " . $message . "\n";
            $this->failures[] = $testName . ": " . $message;
        }
    }

    /**
     * Test: PDF generation feature
     * Status: PASSING ✓
     */
    private function test_pdf_generation() {
        $invoice = new Invoice("Test Customer");
        $invoice->addItem("Product A", 100.00, 1);
        $invoice->addItem("Product B", 50.00, 2);

        $generator = new PDFGenerator();
        
        try {
            $filename = $generator->generatePDF($invoice);
            
            // Verify filename format
            $isValidFilename = strpos($filename, 'invoice_') === 0 && 
                              strpos($filename, '.pdf') !== false;
            $this->assert(
                $isValidFilename,
                "test_pdf_generation (filename)",
                "PDF filename should match pattern invoice_*.pdf, got: $filename"
            );

            // Verify file was created
            $filepath = __DIR__ . '/../' . $filename;
            $fileExists = file_exists($filepath);
            $this->assert(
                $fileExists,
                "test_pdf_generation (file creation)",
                "PDF file should exist at: $filepath"
            );

            // Verify PDF has content (magic bytes for PDF)
            if ($fileExists) {
                $content = file_get_contents($filepath);
                $isPdf = strpos($content, '%PDF') === 0;
                $this->assert(
                    $isPdf,
                    "test_pdf_generation (PDF magic bytes)",
                    "File should be valid PDF with %PDF header"
                );

                // Clean up test file
                unlink($filepath);
            }
        } catch (\Exception $e) {
            $this->assert(
                false,
                "test_pdf_generation",
                "PDF generation failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Test: PDF content generation (streaming)
     * Status: PASSING ✓
     */
    private function test_pdf_content_generation() {
        $invoice = new Invoice("Stream Test");
        $invoice->addItem("Service", 250.00, 1);

        $generator = new PDFGenerator();
        
        try {
            $pdfContent = $generator->generatePDFContent($invoice);
            
            // Verify PDF content is binary data
            $isPdf = strpos($pdfContent, '%PDF') === 0;
            $this->assert(
                $isPdf,
                "test_pdf_content_generation (PDF magic)",
                "PDF content should start with %PDF"
            );

            // Verify reasonable size
            $size = strlen($pdfContent);
            $isSizeValid = $size > 1000; // PDF should be at least 1KB
            $this->assert(
                $isSizeValid,
                "test_pdf_content_generation (size)",
                "PDF content should be reasonable size, got: $size bytes"
            );
        } catch (\Exception $e) {
            $this->assert(
                false,
                "test_pdf_content_generation",
                "PDF content generation failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Test: Input validation
     * Status: PASSING ✓
     */
    private function test_input_validation() {
        // Test 1: Empty customer name should throw exception
        $exceptionThrown = false;
        try {
            $invoice = new Invoice("");
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (empty customer)",
            "Empty customer name should throw InvalidArgumentException"
        );

        // Test 2: Empty item name should throw exception
        $invoice = new Invoice("Valid Customer");
        $exceptionThrown = false;
        try {
            $invoice->addItem("", 10.00, 1);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (empty item name)",
            "Empty item name should throw InvalidArgumentException"
        );

        // Test 3: Negative price should throw exception
        $exceptionThrown = false;
        try {
            $invoice->addItem("Valid Item", -10.00, 1);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (negative price)",
            "Negative price should throw InvalidArgumentException"
        );

        // Test 4: Zero price should throw exception
        $exceptionThrown = false;
        try {
            $invoice->addItem("Valid Item", 0, 1);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (zero price)",
            "Zero price should throw InvalidArgumentException"
        );

        // Test 5: Negative quantity should throw exception
        $exceptionThrown = false;
        try {
            $invoice->addItem("Valid Item", 10.00, -5);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (negative quantity)",
            "Negative quantity should throw InvalidArgumentException"
        );

        // Test 6: Zero quantity should throw exception
        $exceptionThrown = false;
        try {
            $invoice->addItem("Valid Item", 10.00, 0);
        } catch (\InvalidArgumentException $e) {
            $exceptionThrown = true;
        }
        $this->assert(
            $exceptionThrown,
            "test_input_validation (zero quantity)",
            "Zero quantity should throw InvalidArgumentException"
        );

        // Test 7: validateInvoice() should detect missing items
        $invoice2 = new Invoice("Another Customer");
        $errors = InvoiceCalculator::validateInvoice($invoice2);
        $hasMissingItemsError = count($errors) > 0 && strpos(implode(" ", $errors), "at least one item") !== false;
        $this->assert(
            $hasMissingItemsError,
            "test_input_validation (validateInvoice - no items)",
            "validateInvoice should detect invoice with no items"
        );

        // Test 8: validateInvoice() should pass for valid invoice
        $validInvoice = new Invoice("Valid Customer");
        $validInvoice->addItem("Valid Product", 50.00, 2);
        $errors = InvoiceCalculator::validateInvoice($validInvoice);
        $isValid = count($errors) === 0;
        $this->assert(
            $isValid,
            "test_input_validation (validateInvoice - valid)",
            "validateInvoice should pass for valid invoice, got errors: " . implode("; ", $errors)
        );
    }

    /**
     * Test: Subtotal helper handles qty/quantity consistently
     * Status: NEW
     */
    private function test_calculate_subtotal_helper() {
        $invoice = new Invoice("Helper Test");
        $invoice->addItem("Named Qty", 10.00, 2);

        // Manually craft an item with 'quantity' to mimic legacy data
        $legacyItem = ['name' => 'Legacy Qty', 'price' => 5.00, 'quantity' => 3];
        $items = $invoice->getItems();
        $items[] = $legacyItem;

        // Override items for this test scenario
        $reflection = new \ReflectionClass($invoice);
        $prop = $reflection->getProperty('items');
        $prop->setAccessible(true);
        $prop->setValue($invoice, $items);

        $subtotal = InvoiceCalculator::calculateSubtotal($invoice);
        $this->assert(
            abs($subtotal - 35.00) < 0.001,
            "test_calculate_subtotal_helper",
            "Subtotal helper should handle qty and quantity keys"
        );
    }

    /**
     * Test: loadFromFile handles legacy 'quantity' key
     * Status: NEW (edge case previously failing due to qty/quantity mismatch)
     */
    private function test_load_invoice_qty_mismatch() {
        $testFile = __DIR__ . '/../data/test_invoices_qty.json';

        $legacyInvoice = [
            'id' => 999999,
            'customer' => 'Legacy Customer',
            'items' => [
                ['name' => 'Legacy Item', 'price' => 12.50, 'quantity' => 4]
            ],
            'discount' => 0,
            'total' => 50.00,
            'created_at' => '2024-01-01 00:00:00'
        ];

        file_put_contents($testFile, json_encode([$legacyInvoice], JSON_PRETTY_PRINT));

        try {
            $loaded = Invoice::loadFromFile(999999, $testFile);
            $this->assert(
                abs($loaded->getTotal() - 50.00) < 0.001,
                "test_load_invoice_qty_mismatch (total)",
                "Loaded invoice should preserve totals with legacy quantity key"
            );
            $this->assert(
                count($loaded->getItems()) === 1,
                "test_load_invoice_qty_mismatch (items)",
                "Loaded invoice should contain one item"
            );
        } finally {
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    /**
     * Test: applyDiscount throws exception (incomplete feature)
     * Status: NEW - documents incomplete feature behavior
     */
    private function test_apply_discount_throws_exception() {
        $invoice = new Invoice("Discount Test");
        $invoice->addItem("Product", 100.00, 1);

        $exceptionThrown = false;
        $exceptionMessage = '';
        try {
            $invoice->applyDiscount(10);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $exceptionMessage = $e->getMessage();
        }

        $this->assert(
            $exceptionThrown,
            "test_apply_discount_throws_exception",
            "applyDiscount should throw Exception as documented"
        );
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new InvoiceTest();
    $success = $test->runAll();
    exit($success ? 0 : 1);
}
