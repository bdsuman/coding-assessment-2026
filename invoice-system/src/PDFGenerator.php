<?php

// Load FPDF library
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * PDFGenerator - Generate PDF invoices
 *
 * FEATURE: Uses FPDF library to generate professional invoices
 * Automatically saves to downloads/ folder
 */
class PDFGenerator {

    /**
     * Generate PDF and save to downloads/ folder
     *
     * @param Invoice $invoice
     * @return string PDF filename saved to downloads/
     * @throws Exception If PDF generation fails
     */
    public function generatePDF($invoice) {
        try {
            // Create PDF using FPDF
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Courier', '', 10);

            // Header
            $pdf->SetFont('Courier', 'B', 14);
            $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
            $pdf->Ln(5);

            // Invoice info
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(0, 6, 'Invoice #' . $invoice->getId(), 0, 1);
            $pdf->Cell(0, 6, 'Date: ' . $invoice->getCreatedAt(), 0, 1);
            $pdf->Ln(5);

            // Bill To
            $pdf->SetFont('Courier', 'B', 10);
            $pdf->Cell(0, 6, 'Bill To:', 0, 1);
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(0, 6, $invoice->getCustomer(), 0, 1);
            $pdf->Ln(8);

            // Items header
            $pdf->SetFont('Courier', 'B', 9);
            $pdf->Cell(60, 6, 'Item', 1);
            $pdf->Cell(30, 6, 'Price', 1);
            $pdf->Cell(20, 6, 'Qty', 1);
            $pdf->Cell(40, 6, 'Total', 1);
            $pdf->Ln();

            // Items
            $pdf->SetFont('Courier', '', 9);
            foreach ($invoice->getItems() as $item) {
                $qty = InvoiceCalculator::getQuantity($item);
                $lineTotal = InvoiceCalculator::calculateLineItem($item);
                
                $pdf->Cell(60, 6, substr($item['name'], 0, 25), 1);
                $pdf->Cell(30, 6, '$' . number_format($item['price'], 2), 1);
                $pdf->Cell(20, 6, $qty, 1);
                $pdf->Cell(40, 6, '$' . number_format($lineTotal, 2), 1);
                $pdf->Ln();
            }

            // Totals
            $subtotal = InvoiceCalculator::calculateSubtotal($invoice);
            $tax = $invoice->getTotal() - $subtotal;
            $total = $invoice->getTotal();

            $pdf->Ln(5);
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(110, 6, 'Subtotal:', 0, 0, 'R');
            $pdf->Cell(40, 6, '$' . number_format($subtotal, 2), 0, 1, 'R');
            
            $pdf->Cell(110, 6, 'Tax:', 0, 0, 'R');
            $pdf->Cell(40, 6, '$' . number_format($tax, 2), 0, 1, 'R');
            
            $pdf->SetFont('Courier', 'B', 11);
            $pdf->Cell(110, 8, 'TOTAL:', 0, 0, 'R');
            $pdf->Cell(40, 8, '$' . number_format($total, 2), 0, 1, 'R');

            // Footer
            $pdf->Ln(10);
            $pdf->SetFont('Courier', 'I', 9);
            $pdf->Cell(0, 6, 'Thank you for your business!', 0, 1, 'C');

            // Save to downloads/ folder
            $filename = $this->generateFilename($invoice);
            $filepath = __DIR__ . '/../downloads/' . $filename;
            $pdf->Output('F', $filepath);

            return $filename;
        } catch (\Exception $e) {
            throw new \Exception("PDF generation failed: " . $e->getMessage());
        }
    }

    /**
     * Generate PDF content as string (for streaming)
     *
     * @param Invoice $invoice
     * @return string PDF binary content
     * @throws Exception If PDF generation fails
     */
    public function generatePDFContent($invoice) {
        try {
            // Create PDF using FPDF
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Courier', '', 10);

            // Header
            $pdf->SetFont('Courier', 'B', 14);
            $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
            $pdf->Ln(5);

            // Invoice info
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(0, 6, 'Invoice #' . $invoice->getId(), 0, 1);
            $pdf->Cell(0, 6, 'Date: ' . $invoice->getCreatedAt(), 0, 1);
            $pdf->Ln(5);

            // Bill To
            $pdf->SetFont('Courier', 'B', 10);
            $pdf->Cell(0, 6, 'Bill To:', 0, 1);
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(0, 6, $invoice->getCustomer(), 0, 1);
            $pdf->Ln(8);

            // Items header
            $pdf->SetFont('Courier', 'B', 9);
            $pdf->Cell(60, 6, 'Item', 1);
            $pdf->Cell(30, 6, 'Price', 1);
            $pdf->Cell(20, 6, 'Qty', 1);
            $pdf->Cell(40, 6, 'Total', 1);
            $pdf->Ln();

            // Items
            $pdf->SetFont('Courier', '', 9);
            foreach ($invoice->getItems() as $item) {
                $qty = InvoiceCalculator::getQuantity($item);
                $lineTotal = InvoiceCalculator::calculateLineItem($item);
                
                $pdf->Cell(60, 6, substr($item['name'], 0, 25), 1);
                $pdf->Cell(30, 6, '$' . number_format($item['price'], 2), 1);
                $pdf->Cell(20, 6, $qty, 1);
                $pdf->Cell(40, 6, '$' . number_format($lineTotal, 2), 1);
                $pdf->Ln();
            }

            // Totals
            $subtotal = InvoiceCalculator::calculateSubtotal($invoice);
            $tax = $invoice->getTotal() - $subtotal;
            $total = $invoice->getTotal();

            $pdf->Ln(5);
            $pdf->SetFont('Courier', '', 10);
            $pdf->Cell(110, 6, 'Subtotal:', 0, 0, 'R');
            $pdf->Cell(40, 6, '$' . number_format($subtotal, 2), 0, 1, 'R');
            
            $pdf->Cell(110, 6, 'Tax:', 0, 0, 'R');
            $pdf->Cell(40, 6, '$' . number_format($tax, 2), 0, 1, 'R');
            
            $pdf->SetFont('Courier', 'B', 11);
            $pdf->Cell(110, 8, 'TOTAL:', 0, 0, 'R');
            $pdf->Cell(40, 8, '$' . number_format($total, 2), 0, 1, 'R');

            // Footer
            $pdf->Ln(10);
            $pdf->SetFont('Courier', 'I', 9);
            $pdf->Cell(0, 6, 'Thank you for your business!', 0, 1, 'C');

            // Return as string
            return $pdf->Output('S');
        } catch (\Exception $e) {
            throw new \Exception("PDF generation failed: " . $e->getMessage());
        }
    }
    /**
     * Generate unique filename for PDF
     *
     * Format: invoice_TIMESTAMP_INVOICEID.pdf
     */
    private function generateFilename($invoice) {
        $timestamp = time();
        $invoiceId = $invoice->getId();
        return "invoice_{$timestamp}_{$invoiceId}.pdf";
    }
}
