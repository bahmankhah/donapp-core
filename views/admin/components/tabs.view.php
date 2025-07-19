<div class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab_id => $tab_data): ?>
        <a href="#<?php echo $tab_id; ?>" 
           class="nav-tab <?php echo ($tab_data['active'] ?? false) ? 'nav-tab-active' : ''; ?>" 
           id="<?php echo $tab_id; ?>-tab">
            <?php echo esc_html($tab_data['label']); ?>
        </a>
    <?php endforeach; ?>
</div>

<?php foreach ($tabs as $tab_id => $tab_data): ?>
    <div id="<?php echo $tab_id; ?>" 
         class="tab-content <?php echo ($tab_data['active'] ?? false) ? 'active' : ''; ?>">
        <?php echo $tab_data['content'] ?? ''; ?>
    </div>
<?php endforeach; ?>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $(this).addClass('nav-tab-active');
        $($(this).attr('href')).addClass('active');
    });
});
</script>
