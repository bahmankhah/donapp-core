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

    public function fields()
    {
        // All fields are grouped in a single array for easier management
        $fields_to_save = [
            [
                'label' => 'نام ارزشیاب',
                'tag'   => '{user_meta:evaluator_name}',
                'meta_key' => 'evaluator_name',
            ],
            [
                'label' => 'شناسه ملی',
                'tag'   => '{user_meta:national_id}',
                'meta_key' => 'national_id',
            ],
            [
                'label' => 'شناسه ارزشیابی',
                'tag'   => '{user_meta:evaluation_id}',
                'meta_key' => 'evaluation_id',
            ],
            [
                'label' => 'تاریخ',
                'tag'   => '{user_meta:date}',
                'meta_key' => 'date',
            ],
            [
                'label' => 'مسئولیت ارزشیاب',
                'tag'   => '{user_meta:evaluator_role}',
                'meta_key' => 'evaluator_role',
            ],
            [
                'label' => 'سایر',
                'tag'   => '{user_meta:other}',
                'meta_key' => 'other',
            ],
            [
                'label' => 'پایه تحصیلی',
                'tag'   => '{user_meta:grade}',
                'meta_key' => 'grade',
            ],
            [
                'label' => 'نام کلاس',
                'tag'   => '{user_meta:class_name}',
                'meta_key' => 'class_name',
            ],
            [
                'label' => 'استان',
                'tag'   => '{user_meta:province}',
                'meta_key' => 'province',
            ],
            [
                'label' => 'شهر',
                'tag'   => '{user_meta:city}',
                'meta_key' => 'city',
            ],
            [
                'label' => 'نام موسسه',
                'tag'   => '{user_meta:institute_name}',
                'meta_key' => 'institute_name',
            ],
            [
                'label' => 'نام مدرسه',
                'tag'   => '{user_meta:school_name}',
                'meta_key' => 'school_name',
            ],
        ];

        // Optionally, you can add a 'group' key to each if you want to group them in UI, but for logic, this array is enough
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

        $merge_tags['donapp_core'] = [
            'label'=> 'اطلاعات کاربر Donap',
            'tags' => []
        ];
        foreach ( $this->fields() as $field ) {
            $merge_tags['donapp_core']['tags'][] = [
                'label' => $field['label'],
                'tag'   => $field['tag'],
                'group' => $field['group'],
            ];
        }
        appLogger('MERGE_TAGS: '.json_encode($merge_tags));
    
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
