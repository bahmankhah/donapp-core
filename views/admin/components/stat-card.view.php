<div class="donap-card <?php echo $class ?? ''; ?>">
    <h3><?php echo esc_html($title); ?></h3>
    <p class="donap-stat <?php echo $stat_class ?? ''; ?>">
        <?php echo $value; ?>
        <?php if (isset($suffix)): ?>
            <span class="donap-stat-suffix"><?php echo $suffix; ?></span>
        <?php endif; ?>
    </p>
    <?php if (isset($description)): ?>
        <p class="donap-card-description"><?php echo $description; ?></p>
    <?php endif; ?>
</div>
