<?php if ($pagination['total_pages'] > 1): ?>
<div class="donap-pagination">
    <div class="pagination-info">
        نمایش <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?> تا 
        <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total_items']); ?> 
        از <?php echo $pagination['total_items']; ?> مورد
    </div>
    
    <div class="pagination-links">
        <?php
        $current_page = $pagination['current_page'];
        $total_pages = $pagination['total_pages'];
        $base_url = $_SERVER['REQUEST_URI'];
        
        // Remove existing paged parameter
        $base_url = remove_query_arg('paged', $base_url);
        
        // Helper function to build pagination URL
        function build_pagination_url($base_url, $page) {
            return add_query_arg('paged', $page, $base_url);
        }
        ?>
        
        <?php if ($current_page > 1): ?>
            <a href="<?php echo build_pagination_url($base_url, 1); ?>" class="pagination-link first">
                « اول
            </a>
            <a href="<?php echo build_pagination_url($base_url, $current_page - 1); ?>" class="pagination-link prev">
                قبلی
            </a>
        <?php endif; ?>
        
        <?php
        // Show page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1): ?>
            <a href="<?php echo build_pagination_url($base_url, 1); ?>" class="pagination-link">1</a>
            <?php if ($start_page > 2): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
                <span class="pagination-link current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo build_pagination_url($base_url, $i); ?>" class="pagination-link"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
                <span class="pagination-dots">...</span>
            <?php endif; ?>
            <a href="<?php echo build_pagination_url($base_url, $total_pages); ?>" class="pagination-link"><?php echo $total_pages; ?></a>
        <?php endif; ?>
        
        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo build_pagination_url($base_url, $current_page + 1); ?>" class="pagination-link next">
                بعدی
            </a>
            <a href="<?php echo build_pagination_url($base_url, $total_pages); ?>" class="pagination-link last">
                آخر »
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
.donap-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    padding: 15px 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.pagination-info {
    color: #666;
    font-size: 14px;
}

.pagination-links {
    display: flex;
    gap: 5px;
}

.pagination-link {
    display: inline-block;
    padding: 8px 12px;
    background: #fff;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #0073aa;
    border-radius: 3px;
    transition: all 0.2s;
}

.pagination-link:hover {
    background: #0073aa;
    color: #fff;
    text-decoration: none;
}

.pagination-link.current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.pagination-link.first,
.pagination-link.last {
    font-weight: bold;
}

.pagination-dots {
    padding: 8px 4px;
    color: #666;
}

@media (max-width: 768px) {
    .donap-pagination {
        flex-direction: column;
        gap: 10px;
    }
    
    .pagination-links {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
<?php endif; ?>
