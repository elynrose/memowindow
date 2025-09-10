<?php
/**
 * PriceFormatter - Centralized price formatting and conversion utilities
 * Handles consistent price formatting across Stripe, Printful, and display
 */
class PriceFormatter {
    
    /**
     * Convert cents to dollars (for Stripe)
     * @param int $cents Price in cents
     * @return float Price in dollars
     */
    public static function centsToDollars($cents) {
        return floatval($cents) / 100;
    }
    
    /**
     * Convert dollars to cents (for Stripe)
     * @param float $dollars Price in dollars
     * @return int Price in cents
     */
    public static function dollarsToCents($dollars) {
        return intval(round($dollars * 100));
    }
    
    /**
     * Format price for display (e.g., "$24.99")
     * @param int $cents Price in cents
     * @param string $currency Currency symbol (default: '$')
     * @return string Formatted price string
     */
    public static function formatDisplay($cents, $currency = '$') {
        return $currency . number_format(self::centsToDollars($cents), 2);
    }
    
    /**
     * Format price for Stripe (integer cents)
     * @param mixed $price Price in any format (cents, dollars, or string)
     * @return int Price in cents for Stripe
     */
    public static function formatForStripe($price) {
        if (is_int($price)) {
            return $price; // Already in cents
        }
        
        if (is_float($price)) {
            return self::dollarsToCents($price);
        }
        
        if (is_string($price)) {
            // Remove currency symbols and convert to float
            $cleanPrice = preg_replace('/[^0-9.]/', '', $price);
            return self::dollarsToCents(floatval($cleanPrice));
        }
        
        return 0;
    }
    
    /**
     * Format price for Printful (decimal string)
     * @param mixed $price Price in any format
     * @return string Price as decimal string for Printful
     */
    public static function formatForPrintful($price) {
        $cents = self::formatForStripe($price);
        return number_format(self::centsToDollars($cents), 2);
    }
    
    /**
     * Format price for database storage (integer cents)
     * @param mixed $price Price in any format
     * @return int Price in cents for database
     */
    public static function formatForDatabase($price) {
        return self::formatForStripe($price);
    }
    
    /**
     * Parse price from various formats
     * @param mixed $price Price in any format
     * @return array ['cents' => int, 'dollars' => float, 'display' => string, 'printful' => string]
     */
    public static function parse($price) {
        $cents = self::formatForStripe($price);
        
        return [
            'cents' => $cents,
            'dollars' => self::centsToDollars($cents),
            'display' => self::formatDisplay($cents),
            'printful' => self::formatForPrintful($cents),
            'stripe' => $cents
        ];
    }
    
    /**
     * Validate price format
     * @param mixed $price Price to validate
     * @return bool True if valid price
     */
    public static function isValid($price) {
        if (is_numeric($price)) {
            return floatval($price) >= 0;
        }
        
        if (is_string($price)) {
            $cleanPrice = preg_replace('/[^0-9.]/', '', $price);
            return is_numeric($cleanPrice) && floatval($cleanPrice) >= 0;
        }
        
        return false;
    }
    
    /**
     * Calculate tax amount
     * @param int $cents Price in cents
     * @param float $taxRate Tax rate as decimal (e.g., 0.08 for 8%)
     * @return int Tax amount in cents
     */
    public static function calculateTax($cents, $taxRate) {
        return intval(round($cents * $taxRate));
    }
    
    /**
     * Calculate total with tax
     * @param int $cents Price in cents
     * @param float $taxRate Tax rate as decimal
     * @return int Total price in cents including tax
     */
    public static function calculateTotalWithTax($cents, $taxRate) {
        return $cents + self::calculateTax($cents, $taxRate);
    }
    
    /**
     * Format price range for display
     * @param int $minCents Minimum price in cents
     * @param int $maxCents Maximum price in cents
     * @param string $currency Currency symbol
     * @return string Formatted price range
     */
    public static function formatRange($minCents, $maxCents, $currency = '$') {
        if ($minCents === $maxCents) {
            return self::formatDisplay($minCents, $currency);
        }
        
        return $currency . number_format(self::centsToDollars($minCents), 2) . 
               ' - ' . $currency . number_format(self::centsToDollars($maxCents), 2);
    }
    
    /**
     * Convert price between currencies (basic conversion)
     * @param int $cents Price in cents
     * @param float $exchangeRate Exchange rate (e.g., 0.85 for USD to EUR)
     * @return int Converted price in cents
     */
    public static function convertCurrency($cents, $exchangeRate) {
        return intval(round($cents * $exchangeRate));
    }
    
    /**
     * Get price breakdown for order display
     * @param int $subtotalCents Subtotal in cents
     * @param int $shippingCents Shipping in cents
     * @param int $taxCents Tax in cents
     * @return array Price breakdown
     */
    public static function getOrderBreakdown($subtotalCents, $shippingCents = 0, $taxCents = 0) {
        $totalCents = $subtotalCents + $shippingCents + $taxCents;
        
        return [
            'subtotal' => [
                'cents' => $subtotalCents,
                'display' => self::formatDisplay($subtotalCents)
            ],
            'shipping' => [
                'cents' => $shippingCents,
                'display' => self::formatDisplay($shippingCents)
            ],
            'tax' => [
                'cents' => $taxCents,
                'display' => self::formatDisplay($taxCents)
            ],
            'total' => [
                'cents' => $totalCents,
                'display' => self::formatDisplay($totalCents)
            ]
        ];
    }
    
    /**
     * Format price for JSON API responses
     * @param mixed $price Price in any format
     * @return array Formatted price data for API
     */
    public static function formatForAPI($price) {
        $parsed = self::parse($price);
        
        return [
            'amount' => $parsed['cents'],
            'amount_formatted' => $parsed['display'],
            'currency' => 'USD',
            'printful_price' => $parsed['printful']
        ];
    }
    
    /**
     * Legacy method for backward compatibility
     * @param mixed $price Price in any format
     * @return int Price in cents
     */
    public static function fromDatabase($price) {
        return self::formatForStripe($price);
    }
    
    /**
     * Legacy method for backward compatibility
     * @param int $cents Price in cents
     * @return string Formatted price string
     */
    public static function toDisplay($cents) {
        return self::formatDisplay($cents);
    }
}

// Example usage and testing
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "PriceFormatter Test Suite\n";
    echo "=======================\n\n";
    
    $testPrices = [
        2499,           // Cents
        24.99,          // Dollars
        '$24.99',       // String with currency
        '24.99',        // String without currency
        '24.99 USD'     // String with currency code
    ];
    
    foreach ($testPrices as $price) {
        echo "Input: " . var_export($price, true) . "\n";
        $parsed = PriceFormatter::parse($price);
        echo "  Cents: " . $parsed['cents'] . "\n";
        echo "  Dollars: " . $parsed['dollars'] . "\n";
        echo "  Display: " . $parsed['display'] . "\n";
        echo "  Printful: " . $parsed['printful'] . "\n";
        echo "  Stripe: " . $parsed['stripe'] . "\n";
        echo "  Valid: " . (PriceFormatter::isValid($price) ? 'Yes' : 'No') . "\n";
        echo "\n";
    }
    
    // Test order breakdown
    echo "Order Breakdown Example:\n";
    $breakdown = PriceFormatter::getOrderBreakdown(2499, 500, 200);
    foreach ($breakdown as $key => $value) {
        echo "  $key: " . $value['display'] . "\n";
    }
}
?>
