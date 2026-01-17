<?php

/**
 * InvoiceCalculator - Helper class for invoice calculations
 *
 * Static utility methods for business logic
 * Client keeps changing their mind on requirements...
 */
class InvoiceCalculator {

    /**
     * Calculate tax for an invoice
     *
     * FEATURE IMPLEMENTED: Dynamically loads tax rates from data/tax_rates.json
     * Supports region-specific rates with fallback to country default
     *
     * @param float $subtotal The subtotal before tax
     * @param string $region Region code (e.g., "US-CA", "CA-ON")
     * @return float Tax amount
     */
    public static function calculateTax($subtotal, $region = 'US-CA') {
        // Parse region code: "COUNTRY-STATE" format
        $parts = explode('-', $region);
        $country = $parts[0] ?? 'US';
        $state = $parts[1] ?? null;

        // Load tax rates from configuration file
        $taxRate = self::getTaxRate($country, $state);

        return $subtotal * $taxRate;
    }

    /**
     * Get normalized quantity from an item array.
     */
    public static function getQuantity(array $item) {
        if (isset($item['quantity'])) {
            return $item['quantity'];
        }
        if (isset($item['qty'])) {
            return $item['qty'];
        }
        return 0;
    }

    /**
     * Get tax rate for a country/state combination
     *
     * FEATURE: Loads from tax_rates.json with intelligent fallback:
     * 1. Try country-state specific rate (e.g., US-CA)
     * 2. Fall back to country default
     * 3. Fall back to global default (6%)
     *
     * @param string $country Country code (e.g., "US", "CA", "UK")
     * @param string|null $state Optional state/province code
     * @return float Tax rate as decimal (0.05 = 5%)
     */
    private static function getTaxRate($country, $state = null) {
        $configFile = __DIR__ . '/../data/tax_rates.json';

        // Validate file exists
        if (!file_exists($configFile)) {
            // Graceful fallback if config file missing
            error_log("Warning: tax_rates.json not found, using default 6% rate");
            return 0.06;
        }

        // Load and parse tax rates
        $contents = file_get_contents($configFile);
        $taxRates = json_decode($contents, true);

        // Validate JSON parsing
        if ($taxRates === null) {
            error_log("Warning: Failed to parse tax_rates.json, using default 6% rate");
            return 0.06;
        }

        // Validate country exists
        if (!isset($taxRates[$country])) {
            error_log("Warning: Country '$country' not found in tax_rates.json, using default 6% rate");
            return 0.06;
        }

        $countryRates = $taxRates[$country];

        // If state specified and exists, use state-specific rate
        if ($state && isset($countryRates[$state])) {
            return (float) $countryRates[$state];
        }

        // Fall back to country default rate
        if (isset($countryRates['default'])) {
            return (float) $countryRates['default'];
        }

        // Ultimate fallback: 6% global default
        error_log("Warning: No default rate for country '$country', using 6% global default");
        return 0.06;
    }

    /**
     * Apply business rules to an invoice
     *
     * Rules from client (received via email last Thursday):
     * 1. Orders over $1000 get automatic 5% discount
     * 2. BUT discount should NOT apply to items marked as "sale" items
     * 3. How do we even track which items are on sale??
     * 4. Does the $1000 include tax or not?? (Waiting for response)
     *
     * Client keeps changing their mind on this feature
     * Started implementation 3 times, gave up
     *
     * @param Invoice $invoice
     * @return Invoice Modified invoice
     */
    public static function applyBusinessRules($invoice) {
        // Need to figure out requirements first

        // Pseudo-code for what they MIGHT want:
        // if (invoice total > 1000 && !has_sale_items) {
        //     apply 5% discount
        // }

        // Problems:
        // 1. How to identify sale items? Add a flag to item array?
        // 2. Does discount apply before or after tax?
        // 3. Can discounts stack with other discounts?
        // 4. What if they return items - does discount get recalculated?

        // For now, just return the invoice unchanged
        // Need meeting with client to clarify

        return $invoice;
    }

    /**
     * Calculate line item total
     * This one actually works correctly!
     *
     * @param array $item Item with price and quantity/qty
     * @return float Line item total
     */
    public static function calculateLineItem($item) {
        $price = $item['price'];
        return $price * self::getQuantity($item);
    }

    /**
     * Calculate subtotal for an invoice.
     */
    public static function calculateSubtotal($invoice) {
        $total = 0;
        foreach ($invoice->getItems() as $item) {
            $total += self::calculateLineItem($item);
        }
        return $total;
    }

    /**
     * Format currency for display
     * Quick helper I added
     *
     * @param float $amount
     * @return string Formatted currency
     */
    public static function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }

    /**
     * Validate invoice data
     * FEATURE IMPLEMENTED: Complete validation of invoice integrity
     *
     * Checks:
     * - Invoice has at least one item
     * - All prices are positive
     * - All quantities are positive
     * - Customer name is not empty
     *
     * @param Invoice $invoice Invoice to validate
     * @return array Array of error messages (empty if valid)
     */
    public static function validateInvoice($invoice) {
        $errors = [];

        // Check customer name
        if (empty(trim($invoice->getCustomer()))) {
            $errors[] = "Customer name cannot be empty";
        }

        // Check invoice has items
        $items = $invoice->getItems();
        if (empty($items)) {
            $errors[] = "Invoice must have at least one item";
            return $errors; // No point checking items if there are none
        }

        // Validate each item
        foreach ($items as $index => $item) {
            $itemNum = $index + 1;
            
            // Check item name
            if (empty(trim($item['name'])) || $item['name'] !== trim($item['name'])) {
                $errors[] = "Item $itemNum: name cannot be empty";
            }
            
            // Check price
            if (!isset($item['price']) || $item['price'] <= 0) {
                $errors[] = "Item $itemNum: price must be positive, got " . ($item['price'] ?? 'missing');
            }
            
            // Check quantity (handle both 'qty' and 'quantity' keys)
            $qty = self::getQuantity($item);
            if (!isset($qty) || $qty <= 0) {
                $errors[] = "Item $itemNum: quantity must be positive, got " . ($qty ?? 'missing');
            }
        }

        return $errors;
    }
}
