<?php

namespace App\Services;

use App\Helpers\GiftConfigHelper;

class GiftService
{
    /**
     * Calculate gift amount based on charge amount
     */
    public function calculateGift($chargeAmount)
    {
        return GiftConfigHelper::calculateGiftAmount($chargeAmount);
    }

    /**
     * Get gift percentage based on charge amount
     */
    public function getGiftPercentage($chargeAmount)
    {
        return GiftConfigHelper::getPercentageForAmount($chargeAmount);
    }

    /**
     * Get all gift configuration
     */
    public function getGiftConfiguration()
    {
        return GiftConfigHelper::getAll();
    }

    /**
     * Update gift configuration
     */
    public function updateGiftConfiguration($config)
    {
        return GiftConfigHelper::updateValues($config);
    }

    /**
     * Get gift range description for display
     */
    public function getGiftRangeDescription($chargeAmount)
    {
        return GiftConfigHelper::getRangeDescription($chargeAmount);
    }

    /**
     * Get formatted ranges for admin display
     */
    public function getFormattedRanges()
    {
        return GiftConfigHelper::getFormattedRanges();
    }

    /**
     * Validate gift configuration
     */
    public function validateConfiguration($config)
    {
        return GiftConfigHelper::validate($config);
    }

    /**
     * Export configuration for backup
     */
    public function exportConfiguration()
    {
        return GiftConfigHelper::exportConfig();
    }

    /**
     * Import configuration from backup
     */
    public function importConfiguration($config)
    {
        return GiftConfigHelper::importConfig($config);
    }
}
