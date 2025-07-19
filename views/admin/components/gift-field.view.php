<div class="donap-gift-field-group">
    <label for="donap_gift_<?php echo $field; ?>" class="donap-field-label">
        <?php echo $label; ?>
    </label>
    <div class="donap-field-input">
        <input type="number" 
               id="donap_gift_<?php echo $field; ?>" 
               name="donap_gift_values[<?php echo $field; ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               step="1" 
               min="0" 
               class="regular-text donap-amount-input" />
        <span class="donap-field-suffix">تومان</span>
    </div>
    <p class="description donap-field-description">
        <?php echo $description; ?>
    </p>
</div>
