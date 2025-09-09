<?php
/**
 * PriceManager - Centralized price handling for MemoWindow
 * 
 * This class ensures consistent price formatting and conversion across the application.
 * All prices are stored in the database as cents (integers) to avoid floating point issues.
 */
class PriceManager {
    
    /**
     * Convert dollars to cents
     * 
     * @param float $dollars Price in dollars
     * @return int Price in cents
     */
    public static function dollarsToCents($dollars) {
        return intval(round($dollars * 100));
    }
    
    /**
     * Convert cents to dollars
     * 
     * @param int $cents Price in cents
     * @return float Price in dollars
     */
    public static function centsToDollars($cents) {
        return floatval($cents) / 100;
    }
    
    /**
     * Format price for display (e.g., "$24.99")
     * 
     * @param int $cents Price in cents
     * @return string Formatted price string
     */
    public static function formatPrice($cents) {
        $dollars = self::centsToDollars($cents);
        return '$' . number_format($dollars, 2);
    }
    
    /**
     * Format price for display with custom currency symbol
     * 
     * @param int $cents Price in cents
     * @param string $currency Currency symbol (default: '$')
     * @return string Formatted price string
     */
    public static function formatPriceWithCurrency($cents, $currency = '$') {
        $dollars = self::centsToDollars($cents);
        return $currency . number_format($dollars, 2);
    }
    
    /**
     * Get price in cents from database value
     * Handles both decimal and integer database values
     * 
     * @param mixed $dbValue Value from database
     * @return int Price in cents
     */
    public static function fromDatabase($dbValue) {
        // If it's already an integer, assume it's in cents
        if (is_int($dbValue)) {
            return $dbValue;
        }
        
        // If it's a float or string, convert to cents
        $floatValue = floatval($dbValue);
        
        // If the value is less than 100, assume it's in dollars and convert to cents
        if ($floatValue < 100) {
            return self::dollarsToCents($floatValue);
        }
        
        // Otherwise assume it's already in cents
        return intval($floatValue);
    }
    
    /**
     * Prepare price for database storage
     * Always stores as integer cents
     * 
     * @param mixed $price Price in dollars or cents
     * @return int Price in cents for database storage
     */
    public static function forDatabase($price) {
        $floatValue = floatval($price);
        
        // If the value is less than 100, assume it's in dollars and convert to cents
        if ($floatValue < 100) {
            return self::dollarsToCents($floatValue);
        }
        
        // Otherwise assume it's already in cents
        return intval($floatValue);
    }
    
    /**
     * Validate that a price is reasonable
     * 
     * @param int $cents Price in cents
     * @return bool True if price is valid
     */
    public static function isValidPrice($cents) {
        return is_numeric($cents) && $cents > 0 && $cents < 1000000; // Max $10,000
    }
    
    /**
     * Get price breakdown for display
     * 
     * @param int $cents Price in cents
     * @return array Array with 'cents', 'dollars', 'formatted' keys
     */
    public static function getPriceBreakdown($cents) {
        return [
            'cents' => intval($cents),
            'dollars' => self::centsToDollars($cents),
            'formatted' => self::formatPrice($cents)
        ];
    }
}
?>
