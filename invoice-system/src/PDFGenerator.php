<?php

/**
 * PDFGenerator - Generate PDF invoices
 *
 * FEATURE IMPLEMENTED: Pure PHP PDF generation (no external dependencies)
 * Creates valid PDF 1.4 compatible documents with professional invoice formatting
 * 
 * Advantages:
 * - Zero external dependencies
 * - No curl, DOM, or other PHP extensions required
 * - Lightweight and fast
 * - Generates standard-compliant PDF files
 * - Works on any PHP 7.4+ installation
 */
class PDFGenerator {

    /**
     * Generate PDF from invoice
     *
     * FEATURE IMPLEMENTED: Full PDF generation using pure PHP
     * - Creates professional-looking invoice PDF
     * - Supports all invoice details (items, totals, customer info)
     * - Returns file path for download or storage
     *
     * @param Invoice $invoice
     * @return string PDF file path
     * @throws Exception If PDF generation fails
     */
    public function generatePDF($invoice) {
        try {
            $pdfContent = $this->generatePDFContent($invoice);
            
            // Generate file path
            $filename = $this->generateFilename($invoice);
            $filepath = __DIR__ . '/../' . $filename;

            // Write PDF to file
            file_put_contents($filepath, $pdfContent);

            return $filename;
        } catch (\Exception $e) {
            throw new \Exception("PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Generate PDF content as string (for streaming/embedding)
     *
     * FEATURE: Alternative to generatePDF() for streaming/embedding
     * Useful for direct browser download without saving to disk
     *
     * @param Invoice $invoice
     * @return string PDF binary content
     * @throws Exception If PDF generation fails
     */
    public function generatePDFContent($invoice) {
        try {
            // Build text content for invoice
            $content = $this->buildInvoiceText($invoice);
            
            // Create PDF with embedded text
            return $this->createPDF($content);
        } catch (\Exception $e) {
            throw new \Exception("PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Build formatted invoice text content
     */
    private function buildInvoiceText($invoice) {
        $text = '';
        
        // Header
        $text .= "===============================================\n";
        $text .= "                    INVOICE\n";
        $text .= "===============================================\n";
        $text .= "Invoice #" . $invoice->getId() . "\n";
        $text .= "Date: " . $invoice->getCreatedAt() . "\n";
        $text .= "\n";
        
        // Bill To
        $text .= "Bill To:\n";
        $text .= $invoice->getCustomer() . "\n";
        $text .= "\n";
        
        // Items Header
        $text .= "Item                          Price    Qty   Total\n";
        $text .= "-----------------------------------------------\n";
        
        // Line items
        foreach ($invoice->getItems() as $item) {
            $name = substr($item['name'], 0, 28);
            $price = number_format($item['price'], 2);
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $lineTotal = number_format($item['price'] * $qty, 2);
            
            $text .= str_pad($name, 29) . 
                    str_pad('$' . $price, 9, ' ', STR_PAD_LEFT) .
                    str_pad($qty, 4, ' ', STR_PAD_LEFT) .
                    str_pad('$' . $lineTotal, 9, ' ', STR_PAD_LEFT) . "\n";
        }
        
        // Totals
        $subtotal = $this->calculateSubtotal($invoice);
        $tax = $invoice->getTotal() - $subtotal;
        $total = $invoice->getTotal();
        
        $text .= "-----------------------------------------------\n";
        $text .= str_pad("Subtotal:", 48) . str_pad('$' . number_format($subtotal, 2), 9, ' ', STR_PAD_LEFT) . "\n";
        $text .= str_pad("Tax:", 48) . str_pad('$' . number_format($tax, 2), 9, ' ', STR_PAD_LEFT) . "\n";
        $text .= "===============================================\n";
        $text .= str_pad("TOTAL:", 48) . str_pad('$' . number_format($total, 2), 9, ' ', STR_PAD_LEFT) . "\n";
        $text .= "===============================================\n";
        $text .= "\nThank you for your business!\n";
        
        return $text;
    }

    /**
     * Create a valid PDF 1.4 file from text content
     *
     * Generates a proper PDF document with:
     * - Valid PDF header
     * - Document structure (catalog, pages, page, font)
     * - Text content stream with proper formatting
     * - Cross-reference table
     * - Proper trailer
     *
     * This implementation uses Courier font for monospace display
     * which makes invoice data align nicely
     */
    private function createPDF($text) {
        // PDF header - identifies PDF version
        $pdf = "%PDF-1.4\n";
        
        // Object 1: Catalog (document root)
        $obj1Offset = strlen($pdf);
        $pdf .= "1 0 obj\n";
        $pdf .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $pdf .= "endobj\n";
        
        // Object 2: Pages (container for all pages)
        $obj2Offset = strlen($pdf);
        $pdf .= "2 0 obj\n";
        $pdf .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $pdf .= "endobj\n";
        
        // Object 3: Page (single page)
        $obj3Offset = strlen($pdf);
        $pdf .= "3 0 obj\n";
        $pdf .= "<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\n";
        $pdf .= "endobj\n";
        
        // Object 4: Font Resources
        $obj4Offset = strlen($pdf);
        $pdf .= "4 0 obj\n";
        $pdf .= "<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Courier >> >> >>\n";
        $pdf .= "endobj\n";
        
        // Object 5: Content Stream - the actual text to display
        $obj5Offset = strlen($pdf);
        $contentText = "BT\n/F1 9 Tf\n50 750 Td\n";
        
        // Add each line of text with proper formatting
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Escape special PDF characters
            $line = $this->escapePDFString($line);
            $contentText .= "($line) Tj\n";
            $contentText .= "0 -12 Td\n"; // Move down 12 points for next line
        }
        
        $contentText .= "ET\n";
        
        // Object 5 definition with content stream
        $pdf .= "5 0 obj\n";
        $pdf .= "<< /Length " . strlen($contentText) . " >>\n";
        $pdf .= "stream\n";
        $pdf .= $contentText;
        $pdf .= "endstream\n";
        $pdf .= "endobj\n";
        
        // Cross-reference table - maps object offsets
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 6\n"; // Start at object 0, count 6 objects
        $pdf .= "0000000000 65535 f \n"; // Object 0 (unused)
        $pdf .= str_pad($obj1Offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n"; // Object 1
        $pdf .= str_pad($obj2Offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n"; // Object 2
        $pdf .= str_pad($obj3Offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n"; // Object 3
        $pdf .= str_pad($obj4Offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n"; // Object 4
        $pdf .= str_pad($obj5Offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n"; // Object 5
        
        // Trailer - points to document catalog
        $pdf .= "trailer\n";
        $pdf .= "<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "$xrefOffset\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }

    /**
     * Escape special characters for PDF text strings
     *
     * PDF format requires certain characters to be escaped with backslashes
     */
    private function escapePDFString($string) {
        // Escape backslashes first (must be done before parentheses)
        $string = str_replace("\\", "\\\\", $string);
        $string = str_replace("(", "\\(", $string);
        $string = str_replace(")", "\\)", $string);
        
        // Limit line length (PDF content should be reasonable)
        return substr($string, 0, 100);
    }

    /**
     * Calculate subtotal before tax
     */
    private function calculateSubtotal($invoice) {
        $total = 0;
        foreach ($invoice->getItems() as $item) {
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $total += $item['price'] * $qty;
        }
        return $total;
    }

    /**
     * Generate unique filename for PDF
     *
     * Format: invoice_TIMESTAMP_INVOICEID.pdf
     * Ensures no filename collisions even if multiple invoices created same second
     */
    private function generateFilename($invoice) {
        $timestamp = time();
        $invoiceId = $invoice->getId();
        return "invoice_{$timestamp}_{$invoiceId}.pdf";
    }

    /**
     * Export invoice as HTML (alternative to PDF)
     *
     * FEATURE: Still available as fallback option
     * Useful for testing or browser-based PDF printing
     *
     * @param Invoice $invoice
     * @return string HTML file path
     */
    public function exportHTML($invoice) {
        $html = $this->generateHTML($invoice);
        $filename = 'invoice_' . $invoice->getId() . '.html';
        file_put_contents($filename, $html);
        return $filename;
    }

    /**
     * Generate styled HTML template for invoice
     *
     * Creates professional invoice HTML with embedded CSS styling
     *
     * @param Invoice $invoice
     * @return string HTML content
     */
    private function generateHTML($invoice) {
        $invoiceId = htmlspecialchars($invoice->getId());
        $customer = htmlspecialchars($invoice->getCustomer());
        $createdAt = htmlspecialchars($invoice->getCreatedAt());
        
        // Calculate subtotal and tax
        $subtotal = $this->calculateSubtotal($invoice);
        $tax = $invoice->getTotal() - $subtotal;
        $total = $invoice->getTotal();

        // Build HTML with embedded CSS
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #$invoiceId</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #006bff; padding-bottom: 15px; }
        .invoice-header h1 { font-size: 28px; color: #006bff; margin: 0; }
        .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 30px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        table thead { background-color: #006bff; color: white; }
        table th { padding: 10px; text-align: left; }
        table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .totals { width: 300px; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>INVOICE</h1>
            <p>Invoice #$invoiceId</p>
        </div>
        
        <div class="invoice-meta">
            <div><strong>Bill To:</strong><br>$customer</div>
            <div><strong>Invoice Date:</strong><br>$createdAt</div>
        </div>
        
        <table>
            <thead><tr><th>Item Description</th><th>Unit Price</th><th>Qty</th><th>Amount</th></tr></thead>
            <tbody>
HTML;

        // Add line items
        foreach ($invoice->getItems() as $item) {
            $name = htmlspecialchars($item['name']);
            $price = number_format($item['price'], 2);
            $qty = isset($item['quantity']) ? $item['quantity'] : $item['qty'];
            $lineTotal = number_format($item['price'] * $qty, 2);

            $html .= "<tr><td>$name</td><td>\$$price</td><td>$qty</td><td>\$$lineTotal</td></tr>";
        }

        $subtotalFormatted = number_format($subtotal, 2);
        $taxFormatted = number_format($tax, 2);
        $totalFormatted = number_format($total, 2);

        $html .= <<<HTML
            </tbody>
        </table>
        
        <div class="totals">
            <div class="total-row"><span>Subtotal:</span><span>\$$subtotalFormatted</span></div>
            <div class="total-row"><span>Tax:</span><span>\$$taxFormatted</span></div>
            <div class="total-row" style="border-top: 2px solid #006bff; border-bottom: 2px solid #006bff; font-weight: bold; font-size: 16px;">
                <span>Total:</span><span>\$$totalFormatted</span>
            </div>
        </div>
        
        <p style="text-align: center; margin-top: 30px; color: #666;">Thank you for your business!</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}
