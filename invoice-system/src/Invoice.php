<?php

/**
 * Invoice Class
 *
 * Handles invoice creation and management
 * Started: 2 weeks ago
 * Last modified: Friday (was in a hurry)
 */
class Invoice {

    private $customer;
    private $items = [];
    private $discount = 0;
    private $id;
    private $createdAt;

    public function __construct($customerName) {
        // VALIDATION: Customer name must not be empty
        if (empty(trim($customerName))) {
            throw new \InvalidArgumentException("Customer name cannot be empty");
        }
        
        $this->customer = trim($customerName);
        $this->id = time(); // Not sure if this is the best approach...
        $this->createdAt = date('Y-m-d H:i:s');
    }

    /**
     * Add an item to the invoice
     * VALIDATION: Ensures item name is not empty, price and quantity are positive
     */
    public function addItem($name, $price, $quantity) {
        // VALIDATION: Item name must not be empty
        if (empty(trim($name))) {
            throw new \InvalidArgumentException("Item name cannot be empty");
        }
        
        // VALIDATION: Price must be positive
        if ($price <= 0) {
            throw new \InvalidArgumentException("Item price must be greater than zero, got: $price");
        }
        
        // VALIDATION: Quantity must be positive
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Item quantity must be greater than zero, got: $quantity");
        }
        
        $this->items[] = [
            'name' => trim($name),
            'price' => $price,
            'qty' => $quantity  // Using 'qty' here
        ];
    }

    /**
     * Calculate total
     * FIXED: Changed from 'quantity' to 'qty' to match addItem() array key
     */
    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            // FIX: Use 'qty' key to match addItem() storage (was incorrectly using 'quantity')
            $total += $item['price'] * $item['qty'];
        }
        return $total - $this->discount;
    }

    /**
     * Apply discount to invoice
     * TODO: Should discounts apply before or after tax?
     * TODO: Client hasn't decided on the business rules yet
     */
    public function applyDiscount($percent) {
        // Started implementing but not sure about requirements
        // throw new Exception("Not implemented - waiting on client clarification");

        // Trying basic implementation but commented out until we get clarity
        // $subtotal = $this->getTotal();
        // $this->discount = $subtotal * ($percent / 100);

        // For now just throw exception
        throw new Exception("Discount feature incomplete - need business rules from client");
    }

    /**
     * Get invoice ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get customer name
     */
    public function getCustomer() {
        return $this->customer;
    }

    /**
     * Get items array
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Get invoice creation date/time
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Convert invoice to array for JSON serialization
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'items' => $this->items,
            'discount' => $this->discount,
            'total' => $this->getTotal(),
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Save invoice to file
     * FIXED: Now appends to file instead of overwriting. Loads existing invoices, adds new one, writes back.
     */
    public function saveToFile($filename = 'data/invoices.json') {
        $data = $this->toArray();
        
        // FIX: Load existing invoices, append new one, write entire array
        $invoices = [];
        
        // If file exists and has data, load existing invoices
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            if (!empty($contents)) {
                $decoded = json_decode($contents, true);
                // Handle both single invoice (object) and array of invoices
                if (isset($decoded['id'])) {
                    $invoices = [$decoded];
                } else {
                    $invoices = $decoded ?? [];
                }
            }
        }
        
        // Add new invoice to array
        $invoices[] = $data;
        
        // Write entire array back to file
        file_put_contents($filename, json_encode($invoices, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return true;
    }

    /**
     * Load invoice from file by ID
     * Started this but didn't finish testing it
     */
    public static function loadFromFile($id, $filename = 'data/invoices.json') {
        if (!file_exists($filename)) {
            throw new Exception("Invoice file not found");
        }

        $contents = file_get_contents($filename);
        $invoices = json_decode($contents, true);

        // Handle both single invoice and array of invoices
        // (since saveToFile is broken and only saves one)
        if (isset($invoices['id'])) {
            $invoices = [$invoices];
        }

        foreach ($invoices as $invoiceData) {
            if ($invoiceData['id'] == $id) {
                $invoice = new Invoice($invoiceData['customer']);
                $invoice->id = $invoiceData['id'];
                $invoice->discount = $invoiceData['discount'];

                foreach ($invoiceData['items'] as $item) {
                    // Accept both legacy 'quantity' and current 'qty' keys when rebuilding
                    $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
                    $invoice->addItem($item['name'], $item['price'], $qty);
                }

                return $invoice;
            }
        }

        throw new Exception("Invoice not found: " . $id);
    }
}
