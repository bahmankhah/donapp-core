<?php

namespace App\Helpers;

/**
 * Gift Configuration Helper
 * 
 * Provides easy access to gift configuration values
 */
class GiftConfigHelper
{
    const OPTION_NAME = 'donap_gift_values';
    
    const RANGE_TILL_50K = 'till_50k';
    const RANGE_50K_TO_100K = '50k_to_100k';
    const RANGE_100K_TO_200K = '100k_to_200k';
    const RANGE_ABOVE_200K = 'above_200k';

    /**
     * Get default gift configuration
     */
    public static function getDefaults()
    {
        return [
            self::RANGE_TILL_50K => 0,
            self::RANGE_50K_TO_100K => 0,
            self::RANGE_100K_TO_200K => 0,
            self::RANGE_ABOVE_200K => 0
        ];
    }

    /**
     * Get all gift values
     */
    public static function getAll()
    {
        return get_option(self::OPTION_NAME, self::getDefaults());
    }

    /**
     * Get specific gift value by range
     */
    public static function getValue($range)
    {
        $values = self::getAll();
        return isset($values[$range]) ? floatval($values[$range]) : 0;
    }

    /**
     * Update gift values
     */
    public static function updateValues($values)
    {
        // Sanitize values (now they are constant amounts, not percentages)
        $sanitized = [];
        foreach ($values as $key => $value) {
            if (in_array($key, [self::RANGE_TILL_50K, self::RANGE_50K_TO_100K, self::RANGE_100K_TO_200K, self::RANGE_ABOVE_200K])) {
                $sanitized[$key] = max(0, intval($value)); // Ensure positive integers
            }
        }
        
        return update_option(self::OPTION_NAME, $sanitized);
    }

    /**
     * Get gift percentage for a specific amount
     */
    public static function getPercentageForAmount($amount)
    {
        if ($amount <= 50000) {
            return self::getValue(self::RANGE_TILL_50K);
        } elseif ($amount <= 100000) {
            return self::getValue(self::RANGE_50K_TO_100K);
        } elseif ($amount <= 200000) {
            return self::getValue(self::RANGE_100K_TO_200K);
        } else {
            return self::getValue(self::RANGE_ABOVE_200K);
        }
    }

    /**
     * Calculate gift amount for a charge amount
     */
    public static function calculateGiftAmount($chargeAmount)
    {
        $values = self::getAll();
        
        // Return constant amounts based on charge ranges
        if ($chargeAmount <= 50000) {
            return $values[self::RANGE_TILL_50K]; // Constant Toman amount
        } elseif ($chargeAmount <= 100000) {
            return $values[self::RANGE_50K_TO_100K]; // Constant Toman amount
        } elseif ($chargeAmount <= 200000) {
            return $values[self::RANGE_100K_TO_200K]; // Constant Toman amount
        } else {
            return $values[self::RANGE_ABOVE_200K]; // Constant Toman amount
        }
    }

    /**
     * Get range description for display
     */
    public static function getRangeDescription($amount)
    {
        if ($amount <= 50000) {
            return 'تا ۵۰ هزار تومان';
        } elseif ($amount <= 100000) {
            return 'از ۵۰ تا ۱۰۰ هزار تومان';
        } elseif ($amount <= 200000) {
            return 'از ۱۰۰ تا ۲۰۰ هزار تومان';
        } else {
            return 'بالای ۲۰۰ هزار تومان';
        }
    }

    /**
     * Get formatted gift ranges for display
     */
    public static function getFormattedRanges()
    {
        return [
            self::RANGE_TILL_50K => [
                'label' => 'تا ۵۰ هزار تومان',
                'description' => 'مبلغ هدیه برای شارژهای تا ۵۰ هزار تومان',
                'value' => self::getValue(self::RANGE_TILL_50K)
            ],
            self::RANGE_50K_TO_100K => [
                'label' => 'از ۵۰ تا ۱۰۰ هزار تومان',
                'description' => 'مبلغ هدیه برای شارژهای ۵۰ تا ۱۰۰ هزار تومان',
                'value' => self::getValue(self::RANGE_50K_TO_100K)
            ],
            self::RANGE_100K_TO_200K => [
                'label' => 'از ۱۰۰ تا ۲۰۰ هزار تومان',
                'description' => 'مبلغ هدیه برای شارژهای ۱۰۰ تا ۲۰۰ هزار تومان',
                'value' => self::getValue(self::RANGE_100K_TO_200K)
            ],
            self::RANGE_ABOVE_200K => [
                'label' => 'بالای ۲۰۰ هزار تومان',
                'description' => 'مبلغ هدیه برای شارژهای بالای ۲۰۰ هزار تومان',
                'value' => self::getValue(self::RANGE_ABOVE_200K)
            ]
        ];
    }

    /**
     * Validate gift configuration
     */
    public static function validate($values)
    {
        $errors = [];
        
        foreach ($values as $key => $value) {
            if (!in_array($key, [self::RANGE_TILL_50K, self::RANGE_50K_TO_100K, self::RANGE_100K_TO_200K, self::RANGE_ABOVE_200K])) {
                $errors[] = "کلید محدوده نامعتبر: {$key}";
                continue;
            }
            
            $numValue = intval($value);
            if ($numValue < 0) {
                $errors[] = "مقدار برای {$key} باید مثبت باشد";
            }
        }
        
        return $errors;
    }

    /**
     * Export gift configuration
     */
    public static function exportConfig()
    {
        return [
            'version' => '1.0',
            'exported_at' => current_time('mysql'),
            'gift_values' => self::getAll()
        ];
    }

    /**
     * Import gift configuration
     */
    public static function importConfig($config)
    {
        if (!isset($config['gift_values']) || !is_array($config['gift_values'])) {
            return new \WP_Error('invalid_config', 'Invalid configuration format');
        }
        
        $errors = self::validate($config['gift_values']);
        if (!empty($errors)) {
            return new \WP_Error('validation_failed', implode(', ', $errors));
        }
        
        return self::updateValues($config['gift_values']);
    }
}
