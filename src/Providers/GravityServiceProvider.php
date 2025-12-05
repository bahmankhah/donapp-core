<?php

namespace App\Providers;

use Kernel\Container;
use Exception;

class GravityServiceProvider
{
    private $main_menu_slug = 'donap-dashboard';
    private $capability = 'manage_options';

    public function register()
    {
    }

    public function boot()
    {
        add_action('admin_menu', [$this, 'register_gravity_menu'], 20);
        $this->populateForm();
    }

    public function fields(){
        $fields_to_save = [
            [
                'label' => 'کد ملی',
                'tag'   => '{user_meta:national_code}',
                'group' => 'اطلاعات دناپ',
                'meta_key' => 'national_code',
            ],
            [
                'label' => 'نام و نام خانوادگی',
                'tag'   => '{user_meta:name}',
                'group' => 'اطلاعات دناپ',

                'meta_key' => 'name',
            ],
            [
                'label' => 'تاریخ تولد',
                'tag'   => '{user_meta:birth_date}',
                'group' => 'اطلاعات دناپ',

                'meta_key' => 'birth_date',
            ],
            [
                'label' => 'نام پدر',
                'tag'   => '{user_meta:father_name}',
                'group' => 'اطلاعات دناپ',

                'meta_key' => 'father_name',
            ],
            [
                'label' => 'مسئولیت ارزشیاب',
                'tag'   => '{user_meta:school_title}',
                'group' => 'اطلاعات دناپ',

                'meta_key' => 'school_title',
            ],
            [
                'label' => 'شناسه ارزشیابی',
                'tag'   => '{user_meta:evaluation_id}',
                'group' => 'اطلاعات دناپ',

                'meta_key' => 'evaluation_id',
            ],
        ];
        return $fields_to_save;
    }

    public function populateForm()
    {
        // add_filter('gform_pre_render', [$this, 'populate_gravity_form_fields']);
        // add_filter('gform_pre_validation', [$this, 'populate_gravity_form_fields']);
        // add_filter('gform_pre_submission_filter', [$this, 'populate_gravity_form_fields']);
        // add_filter('gform_admin_pre_render', [$this, 'populate_gravity_form_fields']);
        
        add_filter( 'gform_custom_merge_tags', [$this, 'add_custom_user_meta_merge_tag'], 10, 4 );
        add_filter( 'gform_replace_merge_tags', [$this, 'replace_custom_merge_tags'], 10, 7 );
        add_action('gform_after_submission', [$this, 'save_user_meta_after_submission'], 10, 2);
    }
    
    public function replace_custom_merge_tags($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format){
        foreach ( $this->fields() as $field ) {
            $meta_value = get_user_meta(get_current_user_id(), $field['meta_key'], true);
            if ( strpos( $text, $field['tag'] ) !== false ) {
                $replace_value = $meta_value ?? '';
                if ( $url_encode ) {
                    $replace_value = rawurlencode( $replace_value );
                }
                if ( $esc_html ) {
                    $replace_value = esc_html( $replace_value );
                }
                if ( $nl2br ) {
                    $replace_value = nl2br( $replace_value );
                }
                $text = str_replace( $field['tag'], $replace_value, $text );
            }
        }
        return $text;
    }

    public function add_custom_user_meta_merge_tag( $merge_tags, $form_id, $fields, $element_id ) {

        foreach ( $this->fields() as $field ) {
            $merge_tags[] = [
                'label' => $field['label'],
                'tag'   => $field['tag'],
                'group' => $field['group'],
            ];
        }
    
        return $merge_tags;
    }

    public function save_user_meta_after_submission($entry, $form)
    {
        $feildsToSave = [];
        foreach($this->fields() as $f){
            $feildsToSave[$f['tag']] = $f['meta_key']; 
        }
        foreach ($form['fields'] as $field) {
            if (in_array($field['defaultValue'],array_keys($feildsToSave))) {
                $field_value = rgar($entry, $field['id']);
    
                if (!empty($field_value)) {
                    update_user_meta(get_current_user_id(), $feildsToSave[$field['defaultValue']], $field_value);
                }
            }
        }
    }

//     public function populate_gravity_form_fields($form)
//     {
//         $fields_to_save = $this->fields();
// foreach ($form['fields'] as $field) {
//             appLogger('Field: '. json_encode($field));
//         }

//         return $form;
//     }
    /**
     * Register Gravity Flow submenu under Donap dashboard
     */
    public function register_gravity_menu()
    {
        // Add submenu under the main Donap menu
        add_submenu_page(
            $this->main_menu_slug,
            'فرم‌های تأیید شده گرویتی فلو',          // Page title
            'فرم‌های گرویتی فلو',                   // Menu title
            $this->capability,                      // Capability
            'donap-gravity-flow',                   // Menu slug
            [$this, 'gravity_flow_page']           // Function
        );
    }

    /**
     * Gravity Flow page content
     */
    public function gravity_flow_page()
    {
        $gravityService = Container::resolve('GravityService');

        // Handle CSV export
        if (isset($_GET['export_csv']) && wp_verify_nonce($_GET['gravity_nonce'], 'export_gravity_csv')) {
            $this->handle_csv_export($gravityService);
            return;
        }

        // Get pagination parameters
        $page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = 20;

        // Get filters from request
        $filters = [
            'form_filter' => $_GET['form_filter'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];

        $entries_result = $gravityService->getApprovedGravityFlowEntries($page, $per_page);
        $stats = $gravityService->getApprovedEntriesStats();

        $error_message = '';
        if (!class_exists('GFForms') || !class_exists('Gravity_Flow')) {
            $error_message = 'توجه: افزونه‌های Gravity Forms و Gravity Flow نصب نیستند. داده‌های نمایش داده شده نمونه هستند.';
        }

        $data = [
            'entries' => $entries_result['data'],
            'pagination' => $entries_result['pagination'],
            'stats' => $stats,
            'current_filters' => $filters,
            'export_nonce' => wp_create_nonce('export_gravity_csv'),
            'warning_message' => $error_message
        ];

        echo view('admin/gravity-flow', $data);
    }

    /**
     * Handle CSV export
     */
    private function handle_csv_export($gravityService)
    {
        $export_result = $gravityService->exportApprovedEntriesToCSV();

        if (!$export_result['success']) {
            wp_die($export_result['message']);
            return;
        }

        $csv_data = $export_result['data'];
        $filename = $export_result['filename'];

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        // Add BOM for proper UTF-8 handling in Excel
        echo "\xEF\xBB\xBF";

        // Output CSV data
        $output = fopen('php://output', 'w');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        exit;
    }
}
