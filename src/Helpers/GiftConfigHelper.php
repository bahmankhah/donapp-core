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
        // Sanitize values
        $sanitized = [];
        foreach ($values as $key => $value) {
            if (in_array($key, [self::RANGE_TILL_50K, self::RANGE_50K_TO_100K, self::RANGE_100K_TO_200K, self::RANGE_ABOVE_200K])) {
                $sanitized[$key] = max(0, min(100, floatval($value)));
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
     * Calculate gift amount for a specific charge
     */
    public static function calculateGiftAmount($chargeAmount)
    {
        $percentage = self::getPercentageForAmount($chargeAmount);
        return ($chargeAmount * $percentage) / 100;
    }

    /**
     * Get range description for display
     */
    public static function getRangeDescription($amount)
    {
        if ($amount <= 50000) {
            return 'Till 50,000 Charge';
        } elseif ($amount <= 100000) {
            return 'From 50,000 Till 100,000 Charge';
        } elseif ($amount <= 200000) {
            return 'From 100,000 Till 200,000 Charge';
        } else {
            return 'Above 200,000 Charge';
        }
    }

    /**
     * Get formatted gift ranges for display
     */
    public static function getFormattedRanges()
    {
        return [
            self::RANGE_TILL_50K => [
                'label' => 'Till 50,000 Charge',
                'description' => 'Gift percentage for charges up to 50,000 Toman',
                'value' => self::getValue(self::RANGE_TILL_50K)
            ],
            self::RANGE_50K_TO_100K => [
                'label' => 'From 50,000 Till 100,000 Charge',
                'description' => 'Gift percentage for charges from 50,000 to 100,000 Toman',
                'value' => self::getValue(self::RANGE_50K_TO_100K)
            ],
            self::RANGE_100K_TO_200K => [
                'label' => 'From 100,000 Till 200,000 Charge',
                'description' => 'Gift percentage for charges from 100,000 to 200,000 Toman',
                'value' => self::getValue(self::RANGE_100K_TO_200K)
            ],
            self::RANGE_ABOVE_200K => [
                'label' => 'Above 200,000 Charge',
                'description' => 'Gift percentage for charges above 200,000 Toman',
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
                $errors[] = "Invalid range key: {$key}";
                continue;
            }
            
            $numValue = floatval($value);
            if ($numValue < 0 || $numValue > 100) {
                $errors[] = "Value for {$key} must be between 0 and 100";
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
