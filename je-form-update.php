<?php
/**
 * Plugin Name: JE Form
 * Plugin URI: https://www.linkedin.com/in/azmayenfarhan/
 * Description: Advanced form builder with e-signature, special select fields, auto-location address field, and all standard form fields. Premium, responsive, and feature-rich.
 * Version: 1.0.1
 * Author: <a href="https://www.linkedin.com/in/azmayenfarhan/">Azmayen Farhan</a>
 * License: GPL v2 or later
 * Text Domain: je-form
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JEFORMS_VERSION', '1.0.1');
define('JEFORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JEFORMS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main JE Forms Class
 */
class JE_Forms_Pro {
    
    private static $instance = null;
    private $form_fields = array();
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->define_form_fields();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_jeforms_save_form', array($this, 'ajax_save_form'));
        add_action('wp_ajax_jeforms_delete_form', array($this, 'ajax_delete_form'));
        add_action('wp_ajax_jeforms_get_form', array($this, 'ajax_get_form'));
        add_action('wp_ajax_jeforms_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_jeforms_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_jeforms_delete_submission', array($this, 'ajax_delete_submission'));
        add_action('wp_ajax_jeforms_export_submissions', array($this, 'ajax_export_submissions'));
        add_action('wp_ajax_jeforms_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_jeforms_duplicate_form', array($this, 'ajax_duplicate_form'));
        
        // Shortcode
        add_shortcode('jeform', array($this, 'render_form_shortcode'));
        
        // Initialize
        add_action('init', array($this, 'init'));
        
        // Form preview handler
        add_action('template_redirect', array($this, 'handle_form_preview'));
    }
    
    /**
     * Define available form fields
     */
    private function define_form_fields() {
        $this->form_fields = array(
            'text' => array(
                'label' => 'Text',
                'icon' => 'dashicons-editor-textcolor',
                'category' => 'basic'
            ),
            'email' => array(
                'label' => 'Email',
                'icon' => 'dashicons-email',
                'category' => 'basic'
            ),
            'textarea' => array(
                'label' => 'Textarea',
                'icon' => 'dashicons-text',
                'category' => 'basic'
            ),
            'number' => array(
                'label' => 'Number',
                'icon' => 'dashicons-calculator',
                'category' => 'basic'
            ),
            'tel' => array(
                'label' => 'Phone',
                'icon' => 'dashicons-phone',
                'category' => 'basic'
            ),
            'url' => array(
                'label' => 'URL',
                'icon' => 'dashicons-admin-links',
                'category' => 'basic'
            ),
            'password' => array(
                'label' => 'Password',
                'icon' => 'dashicons-lock',
                'category' => 'basic'
            ),
            'date' => array(
                'label' => 'Date',
                'icon' => 'dashicons-calendar',
                'category' => 'basic'
            ),
            'time' => array(
                'label' => 'Time',
                'icon' => 'dashicons-clock',
                'category' => 'basic'
            ),
            'datetime' => array(
                'label' => 'Date & Time',
                'icon' => 'dashicons-calendar-alt',
                'category' => 'basic'
            ),
            'select' => array(
                'label' => 'Select',
                'icon' => 'dashicons-arrow-down-alt2',
                'category' => 'choice'
            ),
            'radio' => array(
                'label' => 'Radio',
                'icon' => 'dashicons-marker',
                'category' => 'choice'
            ),
            'checkbox' => array(
                'label' => 'Checkbox',
                'icon' => 'dashicons-yes',
                'category' => 'choice'
            ),
            'acceptance' => array(
                'label' => 'Acceptance',
                'icon' => 'dashicons-yes-alt',
                'category' => 'choice'
            ),
            'file' => array(
                'label' => 'File Upload',
                'icon' => 'dashicons-upload',
                'category' => 'advanced'
            ),
            'hidden' => array(
                'label' => 'Hidden',
                'icon' => 'dashicons-hidden',
                'category' => 'advanced'
            ),
            'html' => array(
                'label' => 'HTML',
                'icon' => 'dashicons-code-standards',
                'category' => 'advanced'
            ),
            'signature' => array(
                'label' => 'E-Signature',
                'icon' => 'dashicons-edit',
                'category' => 'advanced'
            ),
            'special_select' => array(
                'label' => 'Special Select',
                'icon' => 'dashicons-forms',
                'category' => 'advanced'
            ),
            'auto_address' => array(
                'label' => 'Auto-Location Address',
                'icon' => 'dashicons-location-alt',
                'category' => 'advanced'
            ),
            'heading' => array(
                'label' => 'Heading',
                'icon' => 'dashicons-heading',
                'category' => 'layout'
            ),
            'divider' => array(
                'label' => 'Divider',
                'icon' => 'dashicons-minus',
                'category' => 'layout'
            ),
            'spacer' => array(
                'label' => 'Spacer',
                'icon' => 'dashicons-image-flip-vertical',
                'category' => 'layout'
            )
        );
    }
    
    /**
     * Plugin activation - FIXED database table creation
     */
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Forms table
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $sql_forms = "CREATE TABLE IF NOT EXISTS $forms_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            fields longtext NOT NULL,
            settings longtext NOT NULL,
            styling longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Submissions table
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        $sql_submissions = "CREATE TABLE IF NOT EXISTS $submissions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            data longtext NOT NULL,
            files longtext DEFAULT NULL,
            signatures longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            status varchar(20) DEFAULT 'unread',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Execute SQL queries
        dbDelta($sql_forms);
        dbDelta($sql_submissions);
        
        // Default settings
        $default_settings = array(
            'email_from_name' => get_bloginfo('name'),
            'email_from_email' => get_option('admin_email'),
            'default_success_message' => 'Thank you! Your submission has been received.',
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'enable_recaptcha' => false,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'upload_path' => 'jeforms-uploads',
            'allowed_file_types' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
            'max_file_size' => 5,
            'signature_width' => 400,
            'signature_height' => 200,
            'signature_line_color' => '#000000',
            'signature_line_width' => 3,
            'primary_color' => '#0073aa',
            'success_color' => '#28a745',
            'error_color' => '#dc3545',
            'border_color' => '#e2e8f0',
            'enable_debug' => false,
            'retention_days' => 365,
            'ajax_timeout' => 30
        );
        
        if (!get_option('jeforms_settings')) {
            add_option('jeforms_settings', $default_settings, '', 'no');
        }
        
        // Create upload directory
        $upload_dir = wp_upload_dir();
        $jeforms_upload_dir = $upload_dir['basedir'] . '/jeforms-uploads';
        if (!file_exists($jeforms_upload_dir)) {
            wp_mkdir_p($jeforms_upload_dir);
            // Create .htaccess for security
            $htaccess_content = "Options -Indexes\n<FilesMatch '\.(php|php5|phtml)$'>\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            file_put_contents($jeforms_upload_dir . '/.htaccess', $htaccess_content);
            // Create index.php for security
            file_put_contents($jeforms_upload_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Create signature directory
        $jeforms_signature_dir = $upload_dir['basedir'] . '/jeforms-signatures';
        if (!file_exists($jeforms_signature_dir)) {
            wp_mkdir_p($jeforms_signature_dir);
            file_put_contents($jeforms_signature_dir . '/.htaccess', $htaccess_content);
            file_put_contents($jeforms_signature_dir . '/index.php', '<?php // Silence is golden');
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Initialize
     */
    public function init() {
        load_plugin_textdomain('je-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('JE Forms', 'je-form'),
            __('JE Forms', 'je-form'),
            'manage_options',
            'jeforms',
            array($this, 'render_forms_page'),
            'dashicons-feedback',
            30
        );
        
        // Submenu - All Forms
        add_submenu_page(
            'jeforms',
            __('All Forms', 'je-form'),
            __('All Forms', 'je-form'),
            'manage_options',
            'jeforms',
            array($this, 'render_forms_page')
        );
        
        // Submenu - Add New
        add_submenu_page(
            'jeforms',
            __('Add New Form', 'je-form'),
            __('Add New', 'je-form'),
            'manage_options',
            'jeforms-add-new',
            array($this, 'render_form_builder_page')
        );
        
        // Submenu - Submissions
        add_submenu_page(
            'jeforms',
            __('Submissions', 'je-form'),
            __('Submissions', 'je-form'),
            'manage_options',
            'jeforms-submissions',
            array($this, 'render_submissions_page')
        );
        
        // Submenu - Settings
        add_submenu_page(
            'jeforms',
            __('Settings', 'je-form'),
            __('Settings', 'je-form'),
            'manage_options',
            'jeforms-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Admin enqueue scripts
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'jeforms') === false) {
            return;
        }
        
        // WordPress core scripts
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Register inline scripts and styles
        add_action('admin_footer', array($this, 'admin_inline_scripts'));
        add_action('admin_head', array($this, 'admin_inline_styles'));
    }
    
    /**
     * Frontend enqueue scripts
     */
    public function frontend_enqueue_scripts() {
        // Only load on pages with our shortcode or admin
        global $post;
        
        if (is_admin()) {
            return;
        }
        
        // Check if shortcode exists in content
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'jeform')) {
            wp_enqueue_script('jquery');
            // Add OpenStreetMap Nominatim API for reverse geocoding
            wp_enqueue_script('jeforms-location', '', array('jquery'), JEFORMS_VERSION, true);
            add_action('wp_footer', array($this, 'frontend_inline_scripts'), 100);
            add_action('wp_head', array($this, 'frontend_inline_styles'));
        }
    }
    
    /**
     * Admin inline styles
     */
    public function admin_inline_styles() {
        ?>
        <style>
        :root{--jeforms-primary:#0073aa;--jeforms-primary-dark:#005a87;--jeforms-secondary:#6c757d;--jeforms-success:#28a745;--jeforms-danger:#dc3545;--jeforms-warning:#ffc107;--jeforms-info:#17a2b8;--jeforms-light:#f8f9fa;--jeforms-dark:#343a40;--jeforms-border:#e2e4e7;--jeforms-bg:#f1f1f1;--jeforms-white:#ffffff;--jeforms-shadow:0 2px 5px rgba(0,0,0,0.08);--jeforms-shadow-lg:0 5px 15px rgba(0,0,0,0.1);--jeforms-radius:6px;--jeforms-radius-lg:10px;}.jeforms-admin-wrap{margin:20px 20px 0 0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;line-height:1.5;}.jeforms-admin-wrap *{box-sizing:border-box;}.jeforms-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:25px 30px;margin-bottom:25px;border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);display:flex;justify-content:space-between;align-items:center;color:white;}.jeforms-header h1{margin:0;font-size:24px;font-weight:600;display:flex;align-items:center;gap:12px;color:white;}.jeforms-header h1 .dashicons{font-size:30px;width:30px;height:30px;}.jeforms-header-actions{display:flex;gap:12px;}.jeforms-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:14px;font-weight:500;text-decoration:none;border-radius:var(--jeforms-radius);cursor:pointer;transition:all 0.25s ease;border:none;outline:none;user-select:none;}.jeforms-btn:focus{outline:2px solid rgba(0,115,170,0.3);outline-offset:2px;}.jeforms-btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:var(--jeforms-white);box-shadow:0 4px 12px rgba(102,126,234,0.3);}.jeforms-btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(102,126,234,0.4);}.jeforms-btn-secondary{background:var(--jeforms-white);color:var(--jeforms-secondary);border:1px solid var(--jeforms-border);box-shadow:var(--jeforms-shadow);}.jeforms-btn-secondary:hover{background:var(--jeforms-light);transform:translateY(-1px);box-shadow:0 4px 10px rgba(0,0,0,0.1);}.jeforms-btn-success{background:linear-gradient(135deg,#28a745 0%,#20c997 100%);color:var(--jeforms-white);box-shadow:0 4px 12px rgba(40,167,69,0.3);}.jeforms-btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(40,167,69,0.4);}.jeforms-btn-danger{background:linear-gradient(135deg,#dc3545 0%,#e83e8c 100%);color:var(--jeforms-white);box-shadow:0 4px 12px rgba(220,53,69,0.3);}.jeforms-btn-danger:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(220,53,69,0.4);}.jeforms-btn-sm{padding:6px 12px;font-size:12px;}.jeforms-card{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);margin-bottom:25px;overflow:hidden;border:1px solid var(--jeforms-border);}.jeforms-card-header{padding:18px 24px;border-bottom:1px solid var(--jeforms-border);background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);display:flex;justify-content:space-between;align-items:center;}.jeforms-card-header h2{margin:0;font-size:16px;font-weight:600;color:var(--jeforms-dark);}.jeforms-card-body{padding:24px;}.jeforms-builder-wrap{display:grid;grid-template-columns:300px 1fr 350px;gap:25px;min-height:calc(100vh - 200px);}.jeforms-builder-sidebar{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);overflow:hidden;border:1px solid var(--jeforms-border);}.jeforms-builder-main{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);display:flex;flex-direction:column;border:1px solid var(--jeforms-border);}.jeforms-builder-settings{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);overflow:hidden;border:1px solid var(--jeforms-border);}.jeforms-field-palette{padding:20px;}.jeforms-field-category{margin-bottom:25px;}.jeforms-field-category h4{font-size:12px;font-weight:600;text-transform:uppercase;color:var(--jeforms-secondary);margin:0 0 12px 0;padding-bottom:10px;border-bottom:2px solid var(--jeforms-border);letter-spacing:0.5px;}.jeforms-field-items{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}.jeforms-field-item{background:var(--jeforms-white);border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);padding:12px 10px;text-align:center;cursor:grab;transition:all 0.25s ease;font-size:13px;display:flex;flex-direction:column;align-items:center;gap:8px;}.jeforms-field-item:hover{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);border-color:var(--jeforms-primary);transform:translateY(-3px);box-shadow:var(--jeforms-shadow);}.jeforms-field-item .dashicons{font-size:20px;width:20px;height:20px;color:var(--jeforms-primary);}.jeforms-field-item:hover .dashicons{color:#764ba2;}.jeforms-canvas-header{padding:20px 24px;border-bottom:1px solid var(--jeforms-border);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-canvas-header input{font-size:18px;font-weight:600;border:2px solid transparent;padding:12px 16px;border-radius:var(--jeforms-radius);width:350px;transition:all 0.25s ease;background:white;box-shadow:0 2px 4px rgba(0,0,0,0.05);}.jeforms-canvas-header input:hover,.jeforms-canvas-header input:focus{border-color:var(--jeforms-primary);outline:none;box-shadow:0 4px 12px rgba(0,115,170,0.1);}.jeforms-canvas-body{flex:1;padding:25px;overflow-y:auto;background:linear-gradient(135deg,#fafbfc 0%,#f1f3f4 100%);min-height:500px;}.jeforms-canvas-dropzone{min-height:500px;border:3px dashed var(--jeforms-border);border-radius:var(--jeforms-radius-lg);padding:25px;transition:all 0.25s ease;background:white;}.jeforms-canvas-dropzone.dragover{border-color:var(--jeforms-primary);background:rgba(0,115,170,0.03);box-shadow:0 0 0 4px rgba(0,115,170,0.1);}.jeforms-canvas-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:400px;color:var(--jeforms-secondary);text-align:center;}.jeforms-canvas-empty .dashicons{font-size:60px;width:60px;height:60px;margin-bottom:20px;color:var(--jeforms-border);}.jeforms-canvas-empty p{font-size:16px;color:var(--jeforms-secondary);max-width:300px;line-height:1.6;}.jeforms-form-row{display:flex;flex-wrap:wrap;margin:0 -12px;}.jeforms-canvas-field{background:var(--jeforms-white);border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);margin:12px;cursor:move;transition:all 0.25s ease;position:relative;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.05);}.jeforms-canvas-field:hover{border-color:var(--jeforms-primary);transform:translateY(-2px);box-shadow:var(--jeforms-shadow-lg);}.jeforms-canvas-field.selected{border-color:var(--jeforms-primary);box-shadow:0 0 0 3px rgba(0,115,170,0.15);}.jeforms-canvas-field.ui-sortable-helper{box-shadow:var(--jeforms-shadow-lg);transform:rotate(2deg);}.jeforms-field-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);border-bottom:1px solid var(--jeforms-border);border-radius:var(--jeforms-radius) var(--jeforms-radius) 0 0;}.jeforms-field-title{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:600;color:var(--jeforms-dark);}.jeforms-field-title .dashicons{font-size:18px;width:18px;height:18px;color:var(--jeforms-primary);}.jeforms-field-actions{display:flex;gap:6px;opacity:0.7;transition:opacity 0.25s ease;}.jeforms-canvas-field:hover .jeforms-field-actions{opacity:1;}.jeforms-field-actions button{background:white;border:1px solid var(--jeforms-border);padding:6px;cursor:pointer;color:var(--jeforms-secondary);border-radius:4px;transition:all 0.2s ease;display:flex;align-items:center;justify-content:center;width:32px;height:32px;}.jeforms-field-actions button:hover{background:var(--jeforms-primary);color:white;border-color:var(--jeforms-primary);}.jeforms-field-actions button.delete:hover{background:var(--jeforms-danger);border-color:var(--jeforms-danger);}.jeforms-field-preview{padding:20px;}.jeforms-field-preview label{display:block;font-size:14px;font-weight:500;margin-bottom:8px;color:var(--jeforms-dark);}.jeforms-field-preview label .required{color:var(--jeforms-danger);}.jeforms-field-preview input[type="text"],.jeforms-field-preview input[type="email"],.jeforms-field-preview input[type="number"],.jeforms-field-preview input[type="tel"],.jeforms-field-preview input[type="url"],.jeforms-field-preview input[type="password"],.jeforms-field-preview input[type="date"],.jeforms-field-preview input[type="time"],.jeforms-field-preview input[type="datetime-local"],.jeforms-field-preview textarea,.jeforms-field-preview select{width:100%;padding:10px 14px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);font-size:14px;background:var(--jeforms-light);pointer-events:none;transition:all 0.2s ease;}.jeforms-field-preview textarea{min-height:100px;resize:none;}.jeforms-width-10{width:calc(10% - 24px);}.jeforms-width-20{width:calc(20% - 24px);}.jeforms-width-30{width:calc(30% - 24px);}.jeforms-width-40{width:calc(40% - 24px);}.jeforms-width-50{width:calc(50% - 24px);}.jeforms-width-60{width:calc(60% - 24px);}.jeforms-width-70{width:calc(70% - 24px);}.jeforms-width-80{width:calc(80% - 24px);}.jeforms-width-90{width:calc(90% - 24px);}.jeforms-width-100{width:calc(100% - 24px);}.jeforms-settings-panel{height:100%;display:flex;flex-direction:column;}.jeforms-settings-tabs{display:flex;border-bottom:1px solid var(--jeforms-border);background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-settings-tab{flex:1;padding:14px;text-align:center;font-size:13px;font-weight:500;color:var(--jeforms-secondary);background:none;border:none;cursor:pointer;transition:all 0.25s ease;border-bottom:3px solid transparent;position:relative;overflow:hidden;}.jeforms-settings-tab:hover{color:var(--jeforms-primary);background:rgba(0,115,170,0.05);}.jeforms-settings-tab.active{color:var(--jeforms-primary);border-bottom-color:var(--jeforms-primary);background:white;font-weight:600;}.jeforms-settings-content{flex:1;overflow-y:auto;padding:25px;}.jeforms-settings-section{display:none;animation:fadeIn 0.3s ease;}@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}.jeforms-settings-section.active{display:block;}.jeforms-setting-group{margin-bottom:25px;}.jeforms-setting-group label{display:block;font-size:13px;font-weight:600;color:var(--jeforms-dark);margin-bottom:8px;}.jeforms-setting-group input[type="text"],.jeforms-setting-group input[type="email"],.jeforms-setting-group input[type="number"],.jeforms-setting-group textarea,.jeforms-setting-group select{width:100%;padding:12px 16px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);font-size:14px;transition:all 0.25s ease;background:white;}.jeforms-setting-group input:focus,.jeforms-setting-group textarea:focus,.jeforms-setting-group select:focus{border-color:var(--jeforms-primary);outline:none;}.jeforms-setting-group textarea{min-height:100px;resize:vertical;line-height:1.6;}.jeforms-setting-group .description{font-size:12px;color:var(--jeforms-secondary);margin-top:6px;line-height:1.5;}#jeforms-form-title:focus,.jeforms-field-setting:focus,.jeforms-form-setting:focus,.jeforms-style-setting:focus{border-color:var(--jeforms-primary) !important;outline:none !important;}.jeforms-toggle{display:flex;align-items:center;gap:12px;}.jeforms-toggle-switch{position:relative;width:50px;height:26px;}.jeforms-toggle-switch input{opacity:0;width:0;height:0;}.jeforms-toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:var(--jeforms-border);border-radius:34px;transition:.4s;}.jeforms-toggle-slider:before{position:absolute;content:"";height:18px;width:18px;left:4px;bottom:4px;background:var(--jeforms-white);border-radius:50%;transition:.4s;box-shadow:0 2px 4px rgba(0,0,0,0.1);}.jeforms-toggle-switch input:checked + .jeforms-toggle-slider{background:var(--jeforms-primary);}.jeforms-toggle-switch input:checked + .jeforms-toggle-slider:before{transform:translateX(24px);}.jeforms-options-builder{border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);padding:15px;background:white;}.jeforms-option-item{display:flex;gap:10px;margin-bottom:10px;align-items:center;}.jeforms-option-item input{flex:1;padding:10px 12px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);transition:all 0.25s ease;}.jeforms-option-item input:focus{border-color:var(--jeforms-primary);outline:none;}.jeforms-option-item button{padding:10px 14px;background:var(--jeforms-light);border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);cursor:pointer;color:var(--jeforms-secondary);transition:all 0.25s ease;display:flex;align-items:center;justify-content:center;min-width:44px;}.jeforms-option-item button:hover{color:var(--jeforms-danger);border-color:var(--jeforms-danger);background:white;}.jeforms-add-option{padding:12px;width:100%;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);border:2px dashed var(--jeforms-border);border-radius:var(--jeforms-radius);cursor:pointer;font-size:13px;color:var(--jeforms-secondary);transition:all 0.25s ease;font-weight:500;}.jeforms-add-option:hover{border-color:var(--jeforms-primary);color:var(--jeforms-primary);background:white;transform:translateY(-1px);}.jeforms-special-fields{border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);max-height:350px;overflow-y:auto;margin-bottom:15px;background:white;}.jeforms-special-field-item{padding:15px;border-bottom:1px solid var(--jeforms-border);background:var(--jeforms-white);transition:all 0.25s ease;}.jeforms-special-field-item:hover{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-special-field-item:last-child{border-bottom:none;}.jeforms-special-field-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}.jeforms-special-field-header span{font-size:13px;font-weight:600;color:var(--jeforms-dark);}.jeforms-special-field-item .jeforms-setting-group{margin-bottom:15px;}.jeforms-special-field-item .jeforms-setting-group:last-child{margin-bottom:0;}.jeforms-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--jeforms-border);border-radius:var(--jeforms-radius);overflow:hidden;}.jeforms-table th,.jeforms-table td{padding:15px 18px;text-align:left;border-bottom:1px solid var(--jeforms-border);}.jeforms-table th{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);font-size:12px;font-weight:600;color:var(--jeforms-secondary);text-transform:uppercase;letter-spacing:0.5px;}.jeforms-table tr:hover td{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-table .actions{display:flex;gap:8px;}.jeforms-form-status{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:500;letter-spacing:0.3px;}.jeforms-form-status.active{background:rgba(40,167,69,0.1);color:var(--jeforms-success);border:1px solid rgba(40,167,69,0.2);}.jeforms-form-status.draft{background:rgba(108,117,125,0.1);color:var(--jeforms-secondary);border:1px solid rgba(108,117,125,0.2);}.jeforms-shortcode{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);padding:8px 12px;border-radius:var(--jeforms-radius);font-family:'Courier New',monospace;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;border:1px solid var(--jeforms-border);transition:all 0.25s ease;}.jeforms-shortcode:hover{background:white;border-color:var(--jeforms-primary);color:var(--jeforms-primary);transform:translateY(-1px);box-shadow:var(--jeforms-shadow);}.jeforms-submission-status{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:500;letter-spacing:0.3px;}.jeforms-submission-status.unread{background:rgba(0,115,170,0.1);color:var(--jeforms-primary);border:1px solid rgba(0,115,170,0.2);}.jeforms-submission-status.read{background:rgba(108,117,125,0.1);color:var(--jeforms-secondary);border:1px solid rgba(108,117,125,0.2);}.jeforms-submission-detail{display:grid;gap:20px;}.jeforms-submission-field{padding:20px;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);border-radius:var(--jeforms-radius);border:1px solid var(--jeforms-border);}.jeforms-submission-field label{display:block;font-size:12px;font-weight:600;color:var(--jeforms-secondary);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;}.jeforms-submission-field .value{font-size:15px;color:var(--jeforms-dark);word-break:break-word;line-height:1.6;}.jeforms-submission-signature{max-width:100%;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);background:var(--jeforms-white);padding:10px;}.jeforms-settings-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:25px;}.jeforms-settings-grid .jeforms-card{margin-bottom:0;}.jeforms-modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:100000;opacity:0;visibility:hidden;transition:all 0.3s ease;backdrop-filter:blur(5px);}.jeforms-modal-overlay.active{opacity:1;visibility:visible;}.jeforms-modal{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:700px;width:90%;max-height:85vh;overflow:hidden;transform:translateY(-30px) scale(0.95);transition:transform 0.4s ease;}.jeforms-modal-overlay.active .jeforms-modal{transform:translateY(0) scale(1);}.jeforms-modal-header{padding:25px;border-bottom:1px solid var(--jeforms-border);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;}.jeforms-modal-header h3{margin:0;font-size:18px;font-weight:600;color:white;}.jeforms-modal-close{background:rgba(255,255,255,0.1);border:none;font-size:28px;cursor:pointer;color:white;line-height:1;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all 0.25s ease;}.jeforms-modal-close:hover{background:rgba(255,255,255,0.2);transform:rotate(90deg);}.jeforms-modal-body{padding:25px;overflow-y:auto;max-height:calc(85vh - 140px);}.jeforms-modal-footer{padding:20px 25px;border-top:1px solid var(--jeforms-border);display:flex;justify-content:flex-end;gap:12px;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-pagination{display:flex;justify-content:center;gap:6px;padding:25px;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);border-top:1px solid var(--jeforms-border);}.jeforms-pagination a,.jeforms-pagination span{padding:10px 15px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);text-decoration:none;color:var(--jeforms-secondary);font-size:14px;transition:all 0.25s ease;min-width:44px;text-align:center;}.jeforms-pagination a:hover{background:var(--jeforms-primary);border-color:var(--jeforms-primary);color:var(--jeforms-white);transform:translateY(-2px);}.jeforms-pagination span.current{background:var(--jeforms-primary);border-color:var(--jeforms-primary);color:var(--jeforms-white);font-weight:600;}.jeforms-alert{padding:18px 25px;border-radius:var(--jeforms-radius);margin-bottom:25px;display:flex;align-items:center;gap:12px;border-left:5px solid;box-shadow:var(--jeforms-shadow);}.jeforms-alert-success{background:rgba(40,167,69,0.1);color:var(--jeforms-success);border-left-color:var(--jeforms-success);border:1px solid rgba(40,167,69,0.2);}.jeforms-alert-error{background:rgba(220,53,69,0.1);color:var(--jeforms-danger);border-left-color:var(--jeforms-danger);border:1px solid rgba(220,53,69,0.2);}.jeforms-alert-info{background:rgba(23,162,184,0.1);color:var(--jeforms-info);border-left-color:var(--jeforms-info);border:1px solid rgba(23,162,184,0.2);}.jeforms-empty-state{text-align:center;padding:60px 30px;}.jeforms-empty-state .dashicons{font-size:80px;width:80px;height:80px;color:var(--jeforms-border);margin-bottom:25px;opacity:0.5;}.jeforms-empty-state h3{font-size:22px;color:var(--jeforms-dark);margin:0 0 15px;font-weight:600;}.jeforms-empty-state p{color:var(--jeforms-secondary);margin:0 0 30px;font-size:16px;max-width:400px;margin-left:auto;margin-right:auto;line-height:1.6;}.jeforms-empty-state .jeforms-btn{display:inline-flex !important;padding:14px 28px;font-size:15px;}.jeforms-empty-state .jeforms-btn .dashicons{font-size:16px;width:16px;height:16px;margin:0;opacity:1;margin-right:8px;}.jeforms-loading{display:inline-block;width:24px;height:24px;border:3px solid rgba(0,115,170,0.2);border-radius:50%;border-top-color:var(--jeforms-primary);animation:jeforms-spin 1s linear infinite;}@keyframes jeforms-spin{to{transform:rotate(360deg);}}.jeforms-signature-preview{border:3px dashed var(--jeforms-border);border-radius:var(--jeforms-radius);padding:30px;text-align:center;background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);min-height:150px;display:flex;align-items:center;justify-content:center;color:var(--jeforms-secondary);font-size:13px;}.jeforms-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:25px;margin-bottom:25px;}.jeforms-stat-card{background:var(--jeforms-white);border-radius:var(--jeforms-radius-lg);box-shadow:var(--jeforms-shadow);padding:25px;display:flex;align-items:center;gap:20px;border:1px solid var(--jeforms-border);transition:all 0.3s ease;}.jeforms-stat-card:hover{transform:translateY(-5px);box-shadow:var(--jeforms-shadow-lg);}.jeforms-stat-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;}.jeforms-stat-icon .dashicons{font-size:28px;width:28px;height:28px;color:var(--jeforms-white);}.jeforms-stat-icon.blue{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);}.jeforms-stat-icon.green{background:linear-gradient(135deg,#28a745 0%,#20c997 100%);}.jeforms-stat-icon.orange{background:linear-gradient(135deg,#ffc107 0%,#fd7e14 100%);}.jeforms-stat-icon.purple{background:linear-gradient(135deg,#6f42c1 0%,#d63384 100%);}.jeforms-stat-content h4{margin:0;font-size:28px;font-weight:700;color:var(--jeforms-dark);line-height:1;}.jeforms-stat-content p{margin:8px 0 0;font-size:14px;color:var(--jeforms-secondary);}.jeforms-filter-bar{display:flex;justify-content:space-between;align-items:center;padding:20px 25px;border-bottom:1px solid var(--jeforms-border);background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);}.jeforms-filter-bar select{padding:10px 14px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);font-size:14px;background:white;min-width:200px;transition:all 0.25s ease;}.jeforms-filter-bar select:focus{border-color:var(--jeforms-primary);outline:none;}.jeforms-search-box{display:flex;gap:12px;}.jeforms-search-box input{padding:10px 14px;border:2px solid var(--jeforms-border);border-radius:var(--jeforms-radius);font-size:14px;width:300px;transition:all 0.25s ease;background:white;}.jeforms-search-box input:focus{border-color:var(--jeforms-primary);outline:none;}@media (max-width:1400px){.jeforms-builder-wrap{grid-template-columns:280px 1fr 320px;}.jeforms-settings-grid{grid-template-columns:1fr;}}@media (max-width:1200px){.jeforms-builder-wrap{grid-template-columns:1fr;}.jeforms-builder-sidebar,.jeforms-builder-settings{display:none;}.jeforms-builder-sidebar.mobile-active,.jeforms-builder-settings.mobile-active{display:block;position:fixed;top:32px;bottom:0;width:320px;z-index:99999;border-radius:0;}.jeforms-builder-sidebar.mobile-active{left:0;}.jeforms-builder-settings.mobile-active{right:0;}.jeforms-stats{grid-template-columns:repeat(2,1fr);}.jeforms-settings-grid{grid-template-columns:1fr;}}@media (max-width:782px){.jeforms-header{flex-direction:column;gap:20px;text-align:center;padding:20px;}.jeforms-header-actions{flex-wrap:wrap;justify-content:center;width:100%;}.jeforms-canvas-header{flex-direction:column;gap:15px;padding:15px;}.jeforms-canvas-header input{width:100%;font-size:16px;}.jeforms-width-10,.jeforms-width-20,.jeforms-width-30,.jeforms-width-40,.jeforms-width-50,.jeforms-width-60,.jeforms-width-70,.jeforms-width-80,.jeforms-width-90,.jeforms-width-100{width:calc(100% - 24px);}.jeforms-stats{grid-template-columns:1fr;}.jeforms-filter-bar{flex-direction:column;gap:15px;align-items:stretch;}.jeforms-search-box input{width:100%;}.jeforms-table{display:block;overflow-x:auto;}}@media (max-width:600px){.jeforms-admin-wrap{margin:15px 15px 0 0;}.jeforms-builder-sidebar.mobile-active,.jeforms-builder-settings.mobile-active{width:100%;}.jeforms-settings-content{padding:15px;}}
        </style>
        <?php
    }
    
    /**
     * Admin inline scripts - COMPLETELY REWRITTEN to fix focus issues and signature handling
     */
    public function admin_inline_scripts() {
        $screen = get_current_screen();
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('jeforms_admin_nonce');
        ?>
        <script>
        (function($) {
            'use strict';
            
            var JEFormsAdmin = {
                
                currentForm: {
                    id: 0,
                    title: 'Untitled Form',
                    fields: [],
                    settings: {
                        email_to: '<?php echo esc_js(get_option('admin_email')); ?>',
                        email_subject: 'New Form Submission',
                        email_from_name: '<?php echo esc_js(get_bloginfo('name')); ?>',
                        email_from_email: '<?php echo esc_js(get_option('admin_email')); ?>',
                        success_message: 'Thank you! Your submission has been received.',
                        redirect_url: '',
                        submit_button_text: 'Submit',
                        form_class: '',
                        enable_ajax: true,
                        store_submissions: true,
                        honeypot: true
                    },
                    styling: {
                        form_bg_color: '#ffffff',
                        form_padding: '30',
                        form_border_radius: '10',
                        label_color: '#333333',
                        label_font_size: '14',
                        input_bg_color: '#ffffff',
                        input_border_color: '#e2e4e7',
                        input_focus_color: '#0073aa',
                        input_text_color: '#333333',
                        input_padding: '12',
                        input_border_radius: '6',
                        button_bg_color: '#0073aa',
                        button_text_color: '#ffffff',
                        button_hover_color: '#005a87',
                        button_padding: '14',
                        button_border_radius: '6',
                        button_width: 'auto',
                        button_alignment: 'left'
                    }
                },
                
                selectedFieldIndex: -1,
                isRendering: false,
                lastInputFocus: null,
                
                fieldTypes: <?php echo json_encode($this->form_fields); ?>,
                
                init: function() {
                    this.bindEvents();
                    this.initSortable();
                    this.initColorPickers();
                    
                    var urlParams = new URLSearchParams(window.location.search);
                    var formId = urlParams.get('form_id');
                    if (formId && formId > 0) {
                        this.loadForm(formId);
                    }
                    
                    $(document).on('focus', 'input, textarea, select', function() {
                        JEFormsAdmin.lastInputFocus = {
                            element: this,
                            value: $(this).val(),
                            selectionStart: this.selectionStart,
                            selectionEnd: this.selectionEnd
                        };
                    });
                },
                
                bindEvents: function() {
                    var self = this;
                    
                    $(document).on('mousedown', '.jeforms-field-item', function(e) {
                        if (e.button === 0) {
                            var fieldType = $(this).data('type');
                            self.addField(fieldType);
                        }
                    });
                    
                    $(document).on('click', '.jeforms-canvas-field', function(e) {
                        if (!$(e.target).closest('.jeforms-field-actions').length) {
                            var index = $(this).data('index');
                            self.selectField(index);
                        }
                    });
                    
                    $(document).on('click', '.jeforms-field-actions .delete', function(e) {
                        e.stopPropagation();
                        var index = $(this).closest('.jeforms-canvas-field').data('index');
                        self.deleteField(index);
                    });
                    
                    $(document).on('click', '.jeforms-field-actions .duplicate', function(e) {
                        e.stopPropagation();
                        var index = $(this).closest('.jeforms-canvas-field').data('index');
                        self.duplicateField(index);
                    });
                    
                    $(document).on('blur', '#jeforms-form-title', function() {
                        self.currentForm.title = $(this).val();
                    });
                    
                    $(document).on('click', '.jeforms-settings-tab', function() {
                        var target = $(this).data('target');
                        $('.jeforms-settings-tab').removeClass('active');
                        $(this).addClass('active');
                        $('.jeforms-settings-section').removeClass('active');
                        $('#' + target).addClass('active');
                    });
                    
                    $(document).on('blur change', '.jeforms-field-setting', function(e) {
                        if (self.isRendering) return;
                        
                        var setting = $(this).data('setting');
                        var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                        
                        if (self.selectedFieldIndex >= 0) {
                            self.currentForm.fields[self.selectedFieldIndex][setting] = value;
                            
                            if (['label', 'width', 'type'].indexOf(setting) !== -1) {
                                self.renderCanvas();
                                self.selectField(self.selectedFieldIndex);
                            } else {
                                self.updateFieldPreview(self.selectedFieldIndex);
                            }
                        }
                    });
                    
                    $(document).on('blur change', '.jeforms-form-setting', function() {
                        var setting = $(this).data('setting');
                        var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                        self.currentForm.settings[setting] = value;
                    });
                    
                    $(document).on('blur change', '.jeforms-style-setting', function() {
                        var setting = $(this).data('setting');
                        var value = $(this).val();
                        self.currentForm.styling[setting] = value;
                    });
                    
                    $(document).on('click', '.jeforms-add-option', function() {
                        self.addOption();
                    });
                    
                    $(document).on('click', '.jeforms-remove-option', function() {
                        var index = $(this).data('index');
                        self.removeOption(index);
                    });
                    
                    $(document).on('blur', '.jeforms-option-input', function() {
                        var index = $(this).data('index');
                        var type = $(this).data('type');
                        if (self.selectedFieldIndex >= 0) {
                            if (!self.currentForm.fields[self.selectedFieldIndex].options) {
                                self.currentForm.fields[self.selectedFieldIndex].options = [];
                            }
                            if (!self.currentForm.fields[self.selectedFieldIndex].options[index]) {
                                self.currentForm.fields[self.selectedFieldIndex].options[index] = {};
                            }
                            self.currentForm.fields[self.selectedFieldIndex].options[index][type] = $(this).val();
                        }
                    });
                    
                    $(document).on('click', '.jeforms-add-special-field', function() {
                        self.addSpecialField();
                    });
                    
                    $(document).on('click', '.jeforms-remove-special-field', function() {
                        var index = $(this).data('index');
                        self.removeSpecialField(index);
                    });
                    
                    $(document).on('blur change', '.jeforms-special-field-setting', function() {
                        var fieldIndex = $(this).closest('.jeforms-special-field-item').index();
                        var setting = $(this).data('setting');
                        var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                        
                        if (self.selectedFieldIndex >= 0) {
                            if (!self.currentForm.fields[self.selectedFieldIndex].special_fields) {
                                self.currentForm.fields[self.selectedFieldIndex].special_fields = [];
                            }
                            if (!self.currentForm.fields[self.selectedFieldIndex].special_fields[fieldIndex]) {
                                self.currentForm.fields[self.selectedFieldIndex].special_fields[fieldIndex] = {};
                            }
                            self.currentForm.fields[self.selectedFieldIndex].special_fields[fieldIndex][setting] = value;
                        }
                    });
                    
                    $(document).on('click', '#jeforms-save-form', function() {
                        self.saveForm();
                    });
                    
                    $(document).on('click', '#jeforms-preview-form', function() {
                        self.previewForm();
                    });
                    
                    $(document).on('click', '.jeforms-shortcode', function() {
                        var text = $(this).text().trim();
                        navigator.clipboard.writeText(text).then(function() {
                            self.showNotice('Shortcode copied to clipboard!', 'success');
                        });
                    });
                    
                    $(document).on('click', '.jeforms-delete-form', function(e) {
                        e.preventDefault();
                        var formId = $(this).data('id');
                        if (confirm('Are you sure you want to delete this form? This action cannot be undone.')) {
                            self.deleteForm(formId);
                        }
                    });
                    
                    $(document).on('click', '.jeforms-duplicate-form', function(e) {
                        e.preventDefault();
                        var formId = $(this).data('id');
                        self.duplicateForm(formId);
                    });
                    
                    $(document).on('click', '.jeforms-delete-submission', function(e) {
                        e.preventDefault();
                        var submissionId = $(this).data('id');
                        if (confirm('Are you sure you want to delete this submission?')) {
                            self.deleteSubmission(submissionId);
                        }
                    });
                    
                    $(document).on('click', '.jeforms-view-submission', function(e) {
                        e.preventDefault();
                        var submissionId = $(this).data('id');
                        self.viewSubmission(submissionId);
                    });
                    
                    $(document).on('click', '.jeforms-modal-close, .jeforms-modal-overlay', function(e) {
                        if (e.target === this) {
                            self.closeModal();
                        }
                    });
                    
                    $(document).on('click', '#jeforms-export-csv', function() {
                        self.exportSubmissions();
                    });
                    
                    $(document).on('click', '#jeforms-save-settings', function() {
                        self.saveSettings();
                    });
                    
                    $(document).on('keydown', '.jeforms-field-setting, .jeforms-form-setting, .jeforms-style-setting', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            $(this).blur();
                        }
                    });
                },
                
                initSortable: function() {
                    var self = this;
                    
                    if ($('.jeforms-canvas-dropzone').length) {
                        $('.jeforms-canvas-dropzone').sortable({
                            items: '.jeforms-canvas-field',
                            handle: '.jeforms-field-header',
                            placeholder: 'jeforms-field-placeholder',
                            tolerance: 'pointer',
                            cursor: 'move',
                            opacity: 0.8,
                            start: function(e, ui) {
                                ui.placeholder.height(ui.item.outerHeight());
                            },
                            update: function(event, ui) {
                                self.reorderFields();
                            }
                        });
                    }
                },
                
                initColorPickers: function() {
                    if ($.fn.wpColorPicker) {
                        $('.jeforms-color-picker').wpColorPicker({
                            change: function(event, ui) {
                                $(this).val(ui.color.toString()).trigger('change');
                            }
                        });
                    }
                },
                
                addField: function(type) {
                    var field = {
                        type: type,
                        id: 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                        label: this.fieldTypes[type].label,
                        placeholder: '',
                        default_value: '',
                        required: false,
                        width: '100',
                        css_class: '',
                        options: [],
                        special_fields: [],
                        min: '',
                        max: '',
                        step: '',
                        rows: 4,
                        allowed_types: 'jpg,jpeg,png,pdf',
                        max_size: 5,
                        multiple: false,
                        acceptance_text: 'I accept the terms and conditions',
                        html_content: '<p>Custom HTML content</p>',
                        heading_text: 'Section Heading',
                        heading_tag: 'h3',
                        spacer_height: 20,
                        location_accuracy: 'high',
                        location_timeout: 10000,
                        allow_manual_override: false
                    };
                    
                    if (type === 'select' || type === 'radio' || type === 'checkbox') {
                        field.options = [
                            { label: 'Option 1', value: 'option_1' },
                            { label: 'Option 2', value: 'option_2' },
                            { label: 'Option 3', value: 'option_3' }
                        ];
                    }
                    
                    if (type === 'special_select') {
                        field.special_fields = [
                            {
                                name: 'Text Field',
                                type: 'text',
                                placeholder: 'Enter text',
                                required: false
                            },
                            {
                                name: 'Email Field',
                                type: 'email',
                                placeholder: 'Enter email',
                                required: false
                            },
                            {
                                name: 'Signature',
                                type: 'signature',
                                placeholder: '',
                                required: false
                            }
                        ];
                    }
                    
                    if (type === 'auto_address') {
                        field.default_value = 'Location will be auto-detected...';
                    }
                    
                    this.currentForm.fields.push(field);
                    this.renderCanvas();
                    this.selectField(this.currentForm.fields.length - 1);
                },
                
                selectField: function(index) {
                    this.selectedFieldIndex = index;
                    $('.jeforms-canvas-field').removeClass('selected');
                    $('.jeforms-canvas-field[data-index="' + index + '"]').addClass('selected');
                    this.renderFieldSettings();
                    
                    $('.jeforms-settings-tab[data-target="field-settings"]').click();
                },
                
                deleteField: function(index) {
                    if (confirm('Are you sure you want to delete this field?')) {
                        this.currentForm.fields.splice(index, 1);
                        this.selectedFieldIndex = -1;
                        this.renderCanvas();
                        this.renderFieldSettings();
                    }
                },
                
                duplicateField: function(index) {
                    var field = JSON.parse(JSON.stringify(this.currentForm.fields[index]));
                    field.id = 'field_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    this.currentForm.fields.splice(index + 1, 0, field);
                    this.renderCanvas();
                    this.selectField(index + 1);
                },
                
                reorderFields: function() {
                    var self = this;
                    var newOrder = [];
                    
                    $('.jeforms-canvas-field').each(function() {
                        var index = $(this).data('index');
                        newOrder.push(self.currentForm.fields[index]);
                    });
                    
                    this.currentForm.fields = newOrder;
                    this.renderCanvas();
                },
                
                renderCanvas: function() {
                    var self = this;
                    var $dropzone = $('.jeforms-canvas-dropzone');
                    
                    if (!$dropzone.length) return;
                    
                    if (this.currentForm.fields.length === 0) {
                        $dropzone.html(
                            '<div class="jeforms-canvas-empty">' +
                                '<span class="dashicons dashicons-welcome-widgets-menus"></span>' +
                                '<p>Drag and drop fields here or click on field items to add them to your form</p>' +
                            '</div>'
                        );
                        return;
                    }
                    
                    var html = '<div class="jeforms-form-row">';
                    
                    this.currentForm.fields.forEach(function(field, index) {
                        html += self.renderFieldPreview(field, index);
                    });
                    
                    html += '</div>';
                    
                    $dropzone.html(html);
                    
                    this.initSortable();
                },
                
                updateFieldPreview: function(index) {
                    var field = this.currentForm.fields[index];
                    var $fieldElement = $('.jeforms-canvas-field[data-index="' + index + '"]');
                    
                    if ($fieldElement.length) {
                        $fieldElement.find('.jeforms-field-title').html(
                            '<span class="dashicons ' + this.fieldTypes[field.type].icon + '"></span>' +
                            field.label +
                            (field.required ? ' <span style="color: #dc3545;">*</span>' : '')
                        );
                        
                        var widthClass = 'jeforms-width-' + field.width;
                        $fieldElement.removeClass(function(index, className) {
                            return (className.match(/(^|\s)jeforms-width-\S+/g) || []).join(' ');
                        }).addClass(widthClass);
                    }
                },
                
                renderFieldPreview: function(field, index) {
                    var fieldInfo = this.fieldTypes[field.type];
                    var widthClass = 'jeforms-width-' + field.width;
                    var selectedClass = index === this.selectedFieldIndex ? ' selected' : '';
                    
                    var html = '<div class="jeforms-canvas-field ' + widthClass + selectedClass + '" data-index="' + index + '">';
                    html += '<div class="jeforms-field-header">';
                    html += '<span class="jeforms-field-title">';
                    html += '<span class="dashicons ' + fieldInfo.icon + '"></span>';
                    html += field.label;
                    if (field.required) {
                        html += ' <span style="color: #dc3545;">*</span>';
                    }
                    html += '</span>';
                    html += '<div class="jeforms-field-actions">';
                    html += '<button type="button" class="duplicate" title="Duplicate"><span class="dashicons dashicons-admin-page"></span></button>';
                    html += '<button type="button" class="delete" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
                    html += '</div>';
                    html += '</div>';
                    html += '<div class="jeforms-field-preview">';
                    html += this.renderFieldInput(field);
                    html += '</div>';
                    html += '</div>';
                    
                    return html;
                },
                
                renderFieldInput: function(field) {
                    var html = '';
                    var labelHtml = '<label>' + field.label + (field.required ? ' <span class="required">*</span>' : '') + '</label>';
                    
                    switch (field.type) {
                        case 'text':
                        case 'email':
                        case 'tel':
                        case 'url':
                        case 'password':
                            html = labelHtml + '<input type="' + field.type + '" placeholder="' + (field.placeholder || '') + '" value="' + (field.default_value || '') + '">';
                            break;
                            
                        case 'number':
                            html = labelHtml + '<input type="number" placeholder="' + (field.placeholder || '') + '" value="' + (field.default_value || '') + '">';
                            break;
                            
                        case 'textarea':
                            html = labelHtml + '<textarea placeholder="' + (field.placeholder || '') + '">' + (field.default_value || '') + '</textarea>';
                            break;
                            
                        case 'date':
                            html = labelHtml + '<input type="date" value="' + (field.default_value || '') + '">';
                            break;
                            
                        case 'time':
                            html = labelHtml + '<input type="time" value="' + (field.default_value || '') + '">';
                            break;
                            
                        case 'datetime':
                            html = labelHtml + '<input type="datetime-local" value="' + (field.default_value || '') + '">';
                            break;
                            
                        case 'select':
                            html = labelHtml + '<select>';
                            html += '<option value="">Select an option</option>';
                            if (field.options) {
                                field.options.forEach(function(opt) {
                                    var selected = (opt.value === field.default_value) ? ' selected' : '';
                                    html += '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
                                });
                            }
                            html += '</select>';
                            break;
                            
                        case 'radio':
                            html = labelHtml + '<div style="padding-top:5px;">';
                            if (field.options) {
                                field.options.forEach(function(opt, i) {
                                    var checked = (opt.value === field.default_value) ? ' checked' : '';
                                    html += '<label style="display:block;margin-bottom:5px;font-weight:normal;">';
                                    html += '<input type="radio" name="preview_radio_' + field.id + '" value="' + opt.value + '"' + checked + ' style="width:auto;margin-right:5px;">';
                                    html += opt.label;
                                    html += '</label>';
                                });
                            }
                            html += '</div>';
                            break;
                            
                        case 'checkbox':
                            html = labelHtml + '<div style="padding-top:5px;">';
                            if (field.options) {
                                field.options.forEach(function(opt, i) {
                                    html += '<label style="display:block;margin-bottom:5px;font-weight:normal;">';
                                    html += '<input type="checkbox" style="width:auto;margin-right:5px;">';
                                    html += opt.label;
                                    html += '</label>';
                                });
                            }
                            html += '</div>';
                            break;
                            
                        case 'acceptance':
                            html = '<label style="font-weight:normal;display:flex;align-items:flex-start;gap:8px;">';
                            html += '<input type="checkbox" style="width:auto;margin-top:3px;">';
                            html += '<span>' + (field.acceptance_text || 'I accept the terms and conditions') + (field.required ? ' <span class="required">*</span>' : '') + '</span>';
                            html += '</label>';
                            break;
                            
                        case 'file':
                            html = labelHtml + '<input type="file" style="padding:8px 0;">';
                            break;
                            
                        case 'hidden':
                            html = '<div style="background:#f8f9fa;padding:12px;border-radius:4px;text-align:center;color:#6c757d;font-size:12px;border:1px dashed #dee2e6;">';
                            html += '<span class="dashicons dashicons-hidden" style="vertical-align:middle;"></span>';
                            html += ' Hidden Field: ' + (field.default_value || 'No value');
                            html += '</div>';
                            break;
                            
                        case 'html':
                            html = '<div style="background:#f8f9fa;padding:15px;border-radius:4px;font-size:13px;border:1px dashed #dee2e6;">';
                            html += field.html_content || '<p>Custom HTML content</p>';
                            html += '</div>';
                            break;
                            
                        case 'signature':
                            html = labelHtml;
                            html += '<div class="jeforms-signature-preview">';
                            html += '<canvas style="width:100%;height:150px;border:1px solid #ddd;background:#fff;"></canvas>';
                            html += '<div style="margin-top:10px;font-size:11px;color:#666;">Signature Pad (Draw here in live form)</div>';
                            html += '</div>';
                            break;
                            
                        case 'special_select':
                            html = labelHtml;
                            html += '<select style="margin-bottom:15px;padding:10px 14px;border:2px solid #e2e4e7;border-radius:6px;width:100%;background:#f8f9fa;">';
                            html += '<option>Select field type</option>';
                            if (field.special_fields && field.special_fields.length > 0) {
                                field.special_fields.forEach(function(sf, i) {
                                    html += '<option>' + (sf.name || 'Field ' + (i + 1)) + '</option>';
                                });
                            }
                            html += '</select>';
                            html += '<div style="background:#f0f0f0;padding:20px;border-radius:6px;text-align:center;color:#666;font-size:12px;border:1px dashed #ddd;">';
                            html += 'Dynamic field appears here based on selection';
                            if (field.special_fields) {
                                field.special_fields.forEach(function(sf, i) {
                                    if (sf.type === 'signature') {
                                        html += '<div style="margin-top:10px;padding:10px;background:#fff;border-radius:4px;display:none;" class="signature-preview-' + i + '">';
                                        html += '<canvas style="width:100%;height:100px;border:1px solid #ddd;"></canvas>';
                                        html += '</div>';
                                    }
                                });
                            }
                            html += '</div>';
                            break;
                            
                        case 'auto_address':
                            html = labelHtml;
                            html += '<div style="position:relative;">';
                            html += '<input type="text" value="' + (field.default_value || 'Location will be auto-detected...') + '" readonly style="background:#f0f0f0;color:#666;cursor:not-allowed;padding-right:40px;">';
                            html += '<span class="dashicons dashicons-location" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#666;"></span>';
                            html += '</div>';
                            html += '<div style="margin-top:8px;font-size:11px;color:#666;display:flex;align-items:center;gap:5px;">';
                            html += '<span class="dashicons dashicons-info" style="font-size:12px;"></span>';
                            html += 'Address will be auto-filled using your device location';
                            html += '</div>';
                            break;
                            
                        case 'heading':
                            var tag = field.heading_tag || 'h3';
                            html = '<' + tag + ' style="margin:0;padding:10px 0;color:#333;">' + (field.heading_text || 'Section Heading') + '</' + tag + '>';
                            break;
                            
                        case 'divider':
                            html = '<hr style="border:0;border-top:2px solid #e2e4e7;margin:15px 0;">';
                            break;
                            
                        case 'spacer':
                            html = '<div style="height:' + (field.spacer_height || 20) + 'px;background:#f8f9fa;border:1px dashed #dee2e6;border-radius:4px;"></div>';
                            break;
                            
                        default:
                            html = labelHtml + '<input type="text" placeholder="' + (field.placeholder || '') + '" value="' + (field.default_value || '') + '">';
                    }
                    
                    return html;
                },
                
                renderFieldSettings: function() {
                    var self = this;
                    var $container = $('#field-settings .jeforms-settings-content-inner');
                    
                    if (!$container.length) return;
                    
                    if (this.selectedFieldIndex < 0) {
                        $container.html('<div class="jeforms-empty-state" style="padding:50px 20px;"><span class="dashicons dashicons-admin-generic" style="font-size:40px;width:40px;height:40px;"></span><p>Select a field to edit its settings</p></div>');
                        return;
                    }
                    
                    var field = this.currentForm.fields[this.selectedFieldIndex];
                    var html = '';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>Label</label>';
                    html += '<input type="text" class="jeforms-field-setting" data-setting="label" value="' + this.escapeHtml(field.label) + '" autocomplete="off">';
                    html += '</div>';
                    
                    if (['text', 'email', 'tel', 'url', 'password', 'number', 'textarea'].indexOf(field.type) !== -1) {
                        html += '<div class="jeforms-setting-group">';
                        html += '<label>Placeholder</label>';
                        html += '<input type="text" class="jeforms-field-setting" data-setting="placeholder" value="' + this.escapeHtml(field.placeholder || '') + '" autocomplete="off">';
                        html += '</div>';
                    }
                    
                    if (['text', 'email', 'tel', 'url', 'number', 'textarea', 'hidden', 'auto_address'].indexOf(field.type) !== -1) {
                        html += '<div class="jeforms-setting-group">';
                        html += '<label>Default Value</label>';
                        html += '<input type="text" class="jeforms-field-setting" data-setting="default_value" value="' + this.escapeHtml(field.default_value || '') + '" autocomplete="off"' + (field.type === 'auto_address' ? ' readonly style="background:#f0f0f0;"' : '') + '>';
                        html += '</div>';
                    }
                    
                    if (['hidden', 'html', 'heading', 'divider', 'spacer'].indexOf(field.type) === -1) {
                        html += '<div class="jeforms-setting-group">';
                        html += '<div class="jeforms-toggle">';
                        html += '<label class="jeforms-toggle-switch">';
                        html += '<input type="checkbox" class="jeforms-field-setting" data-setting="required"' + (field.required ? ' checked' : '') + '>';
                        html += '<span class="jeforms-toggle-slider"></span>';
                        html += '</label>';
                        html += '<span>Required</span>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>Width</label>';
                    html += '<select class="jeforms-field-setting" data-setting="width">';
                    var widths = ['10', '20', '30', '40', '50', '60', '70', '80', '90', '100'];
                    widths.forEach(function(w) {
                        html += '<option value="' + w + '"' + (field.width == w ? ' selected' : '') + '>' + w + '%</option>';
                    });
                    html += '</select>';
                    html += '</div>';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>CSS Class</label>';
                    html += '<input type="text" class="jeforms-field-setting" data-setting="css_class" value="' + this.escapeHtml(field.css_class || '') + '" autocomplete="off">';
                    html += '</div>';
                    
                    switch (field.type) {
                        case 'number':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Min Value</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="min" value="' + (field.min || '') + '" autocomplete="off">';
                            html += '</div>';
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Max Value</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="max" value="' + (field.max || '') + '" autocomplete="off">';
                            html += '</div>';
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Step</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="step" value="' + (field.step || '') + '" autocomplete="off">';
                            html += '</div>';
                            break;
                            
                        case 'textarea':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Rows</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="rows" value="' + (field.rows || 4) + '" min="2" max="20" autocomplete="off">';
                            html += '</div>';
                            break;
                            
                        case 'select':
                        case 'radio':
                        case 'checkbox':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Options</label>';
                            html += '<div class="jeforms-options-builder">';
                            if (field.options && field.options.length > 0) {
                                field.options.forEach(function(opt, i) {
                                    html += '<div class="jeforms-option-item">';
                                    html += '<input type="text" class="jeforms-option-input" data-index="' + i + '" data-type="label" placeholder="Label" value="' + self.escapeHtml(opt.label || '') + '" autocomplete="off">';
                                    html += '<input type="text" class="jeforms-option-input" data-index="' + i + '" data-type="value" placeholder="Value" value="' + self.escapeHtml(opt.value || '') + '" autocomplete="off">';
                                    html += '<button type="button" class="jeforms-remove-option" data-index="' + i + '"><span class="dashicons dashicons-no"></span></button>';
                                    html += '</div>';
                                });
                            }
                            html += '<button type="button" class="jeforms-add-option">+ Add Option</button>';
                            html += '</div>';
                            html += '</div>';
                            
                            if (field.type === 'select') {
                                html += '<div class="jeforms-setting-group">';
                                html += '<div class="jeforms-toggle">';
                                html += '<label class="jeforms-toggle-switch">';
                                html += '<input type="checkbox" class="jeforms-field-setting" data-setting="multiple"' + (field.multiple ? ' checked' : '') + '>';
                                html += '<span class="jeforms-toggle-slider"></span>';
                                html += '</label>';
                                html += '<span>Allow Multiple Selection</span>';
                                html += '</div>';
                                html += '</div>';
                            }
                            break;
                            
                        case 'acceptance':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Acceptance Text</label>';
                            html += '<textarea class="jeforms-field-setting" data-setting="acceptance_text" autocomplete="off">' + this.escapeHtml(field.acceptance_text || 'I accept the terms and conditions') + '</textarea>';
                            html += '</div>';
                            break;
                            
                        case 'file':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Allowed File Types</label>';
                            html += '<input type="text" class="jeforms-field-setting" data-setting="allowed_types" value="' + this.escapeHtml(field.allowed_types || 'jpg,jpeg,png,pdf') + '" autocomplete="off">';
                            html += '<p class="description">Comma-separated list of extensions</p>';
                            html += '</div>';
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Max File Size (MB)</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="max_size" value="' + (field.max_size || 5) + '" min="1" autocomplete="off">';
                            html += '</div>';
                            html += '<div class="jeforms-setting-group">';
                            html += '<div class="jeforms-toggle">';
                            html += '<label class="jeforms-toggle-switch">';
                            html += '<input type="checkbox" class="jeforms-field-setting" data-setting="multiple"' + (field.multiple ? ' checked' : '') + '>';
                            html += '<span class="jeforms-toggle-slider"></span>';
                            html += '</label>';
                            html += '<span>Allow Multiple Files</span>';
                            html += '</div>';
                            html += '</div>';
                            break;
                            
                        case 'html':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>HTML Content</label>';
                            html += '<textarea class="jeforms-field-setting" data-setting="html_content" rows="8" autocomplete="off">' + this.escapeHtml(field.html_content || '<p>Custom HTML content</p>') + '</textarea>';
                            html += '</div>';
                            break;
                            
                        case 'heading':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Heading Text</label>';
                            html += '<input type="text" class="jeforms-field-setting" data-setting="heading_text" value="' + this.escapeHtml(field.heading_text || 'Section Heading') + '" autocomplete="off">';
                            html += '</div>';
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Heading Tag</label>';
                            html += '<select class="jeforms-field-setting" data-setting="heading_tag">';
                            ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(function(tag) {
                                html += '<option value="' + tag + '"' + (field.heading_tag == tag ? ' selected' : '') + '>' + tag.toUpperCase() + '</option>';
                            });
                            html += '</select>';
                            html += '</div>';
                            break;
                            
                        case 'spacer':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Height (px)</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="spacer_height" value="' + (field.spacer_height || 20) + '" min="5" max="200" autocomplete="off">';
                            html += '</div>';
                            break;
                            
                        case 'special_select':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Dynamic Fields</label>';
                            html += '<div class="jeforms-special-fields">';
                            if (field.special_fields && field.special_fields.length > 0) {
                                field.special_fields.forEach(function(sf, i) {
                                    html += self.renderSpecialFieldItem(sf, i);
                                });
                            }
                            html += '</div>';
                            html += '<button type="button" class="jeforms-add-special-field jeforms-btn jeforms-btn-secondary jeforms-btn-sm" style="margin-top:15px;width:100%;">+ Add Dynamic Field</button>';
                            html += '</div>';
                            break;
                            
                        case 'auto_address':
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Location Accuracy</label>';
                            html += '<select class="jeforms-field-setting" data-setting="location_accuracy">';
                            html += '<option value="high"' + (field.location_accuracy === 'high' ? ' selected' : '') + '>High Accuracy (GPS)</option>';
                            html += '<option value="low"' + (field.location_accuracy === 'low' ? ' selected' : '') + '>Low Accuracy (IP-based)</option>';
                            html += '</select>';
                            html += '<p class="description">High accuracy uses device GPS, low accuracy uses IP geolocation</p>';
                            html += '</div>';
                            
                            html += '<div class="jeforms-setting-group">';
                            html += '<label>Location Timeout (ms)</label>';
                            html += '<input type="number" class="jeforms-field-setting" data-setting="location_timeout" value="' + (field.location_timeout || 10000) + '" min="1000" max="30000" step="1000" autocomplete="off">';
                            html += '<p class="description">How long to wait for location detection (1000-30000 milliseconds)</p>';
                            html += '</div>';
                            
                            html += '<div class="jeforms-setting-group">';
                            html += '<div class="jeforms-toggle">';
                            html += '<label class="jeforms-toggle-switch">';
                            html += '<input type="checkbox" class="jeforms-field-setting" data-setting="allow_manual_override"' + (field.allow_manual_override ? ' checked' : '') + '>';
                            html += '<span class="jeforms-toggle-slider"></span>';
                            html += '</label>';
                            html += '<span>Allow Manual Override</span>';
                            html += '</div>';
                            html += '<p class="description">If checked, users can edit the auto-filled address</p>';
                            html += '</div>';
                            break;
                    }
                    
                    self.isRendering = true;
                    $container.html(html);
                    self.isRendering = false;
                    
                    if (self.lastInputFocus && self.lastInputFocus.element) {
                        var $element = $(self.lastInputFocus.element);
                        if ($element.length && $element.is(':visible')) {
                            $element.focus();
                            if (self.lastInputFocus.selectionStart !== undefined) {
                                $element[0].setSelectionRange(self.lastInputFocus.selectionStart, self.lastInputFocus.selectionEnd);
                            }
                        }
                    }
                },
                
                renderSpecialFieldItem: function(sf, index) {
                    var html = '<div class="jeforms-special-field-item">';
                    html += '<div class="jeforms-special-field-header">';
                    html += '<span>Field #' + (index + 1) + '</span>';
                    html += '<button type="button" class="jeforms-remove-special-field" data-index="' + index + '"><span class="dashicons dashicons-no"></span></button>';
                    html += '</div>';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>Display Name</label>';
                    html += '<input type="text" class="jeforms-special-field-setting" data-setting="name" value="' + this.escapeHtml(sf.name || '') + '" placeholder="Enter display name" autocomplete="off">';
                    html += '</div>';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>Field Type</label>';
                    html += '<select class="jeforms-special-field-setting" data-setting="type">';
                    var allowedTypes = ['text', 'email', 'textarea', 'number', 'tel', 'url', 'date', 'time', 'datetime', 'signature'];
                    allowedTypes.forEach(function(type) {
                        html += '<option value="' + type + '"' + (sf.type == type ? ' selected' : '') + '>' + (this.fieldTypes[type] ? this.fieldTypes[type].label : type) + '</option>';
                    }.bind(this));
                    html += '</select>';
                    html += '</div>';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<label>Placeholder</label>';
                    html += '<input type="text" class="jeforms-special-field-setting" data-setting="placeholder" value="' + this.escapeHtml(sf.placeholder || '') + '" autocomplete="off">';
                    html += '</div>';
                    
                    html += '<div class="jeforms-setting-group">';
                    html += '<div class="jeforms-toggle">';
                    html += '<label class="jeforms-toggle-switch">';
                    html += '<input type="checkbox" class="jeforms-special-field-setting" data-setting="required"' + (sf.required ? ' checked' : '') + '>';
                    html += '<span class="jeforms-toggle-slider"></span>';
                    html += '</label>';
                    html += '<span>Required</span>';
                    html += '</div>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    return html;
                },
                
                addOption: function() {
                    if (this.selectedFieldIndex < 0) return;
                    
                    var field = this.currentForm.fields[this.selectedFieldIndex];
                    if (!field.options) {
                        field.options = [];
                    }
                    
                    var num = field.options.length + 1;
                    field.options.push({
                        label: 'Option ' + num,
                        value: 'option_' + num
                    });
                    
                    this.renderFieldSettings();
                },
                
                removeOption: function(index) {
                    if (this.selectedFieldIndex < 0) return;
                    
                    var field = this.currentForm.fields[this.selectedFieldIndex];
                    if (field.options && field.options.length > 1) {
                        field.options.splice(index, 1);
                        this.renderFieldSettings();
                    }
                },
                
                addSpecialField: function() {
                    if (this.selectedFieldIndex < 0) return;
                    
                    var field = this.currentForm.fields[this.selectedFieldIndex];
                    if (!field.special_fields) {
                        field.special_fields = [];
                    }
                    
                    field.special_fields.push({
                        name: 'Field ' + (field.special_fields.length + 1),
                        type: 'text',
                        placeholder: '',
                        required: false
                    });
                    
                    this.renderFieldSettings();
                },
                
                removeSpecialField: function(index) {
                    if (this.selectedFieldIndex < 0) return;
                    
                    var field = this.currentForm.fields[this.selectedFieldIndex];
                    if (field.special_fields) {
                        field.special_fields.splice(index, 1);
                        this.renderFieldSettings();
                    }
                },
                
                saveForm: function() {
                    var self = this;
                    
                    if (!this.currentForm.title.trim()) {
                        this.showNotice('Please enter a form title', 'error');
                        $('#jeforms-form-title').focus();
                        return;
                    }
                    
                    if (this.currentForm.fields.length === 0) {
                        this.showNotice('Please add at least one field to the form', 'error');
                        return;
                    }
                    
                    $('#jeforms-save-form').prop('disabled', true).html('<span class="jeforms-loading"></span> Saving...');
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_save_form',
                            nonce: '<?php echo $nonce; ?>',
                            form_id: this.currentForm.id,
                            title: this.currentForm.title,
                            fields: JSON.stringify(this.currentForm.fields),
                            settings: JSON.stringify(this.currentForm.settings),
                            styling: JSON.stringify(this.currentForm.styling)
                        },
                        success: function(response) {
                            if (response.success) {
                                self.currentForm.id = response.data.form_id;
                                self.showNotice('Form saved successfully!', 'success');
                                
                                if (!window.location.href.includes('form_id=')) {
                                    var newUrl = window.location.href + '&form_id=' + response.data.form_id;
                                    window.history.pushState({}, '', newUrl);
                                }
                                
                                $('.jeforms-shortcode-display').html('[jeform id="' + response.data.form_id + '"]');
                            } else {
                                self.showNotice(response.data.message || 'Error saving form', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('JE Forms Save Error:', xhr.responseText);
                            self.showNotice('Error saving form. Please try again.', 'error');
                        },
                        complete: function() {
                            $('#jeforms-save-form').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Form');
                        }
                    });
                },
                
                loadForm: function(formId) {
                    var self = this;
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_get_form',
                            nonce: '<?php echo $nonce; ?>',
                            form_id: formId
                        },
                        success: function(response) {
                            if (response.success) {
                                self.currentForm = {
                                    id: response.data.id,
                                    title: response.data.title,
                                    fields: response.data.fields || [],
                                    settings: Object.assign({}, self.currentForm.settings, response.data.settings || {}),
                                    styling: Object.assign({}, self.currentForm.styling, response.data.styling || {})
                                };
                                
                                $('#jeforms-form-title').val(self.currentForm.title);
                                self.renderCanvas();
                                self.loadFormSettings();
                                self.loadFormStyling();
                                
                                self.showNotice('Form loaded successfully!', 'success');
                            }
                        },
                        error: function() {
                            self.showNotice('Error loading form', 'error');
                        }
                    });
                },
                
                loadFormSettings: function() {
                    var settings = this.currentForm.settings;
                    for (var key in settings) {
                        var $input = $('.jeforms-form-setting[data-setting="' + key + '"]');
                        if ($input.length) {
                            if ($input.is(':checkbox')) {
                                $input.prop('checked', settings[key]);
                            } else {
                                $input.val(settings[key]);
                            }
                        }
                    }
                },
                
                loadFormStyling: function() {
                    var styling = this.currentForm.styling;
                    for (var key in styling) {
                        var $input = $('.jeforms-style-setting[data-setting="' + key + '"]');
                        if ($input.length) {
                            $input.val(styling[key]);
                            if ($input.hasClass('jeforms-color-picker') && $.fn.wpColorPicker) {
                                $input.wpColorPicker('color', styling[key]);
                            }
                        }
                    }
                },
                
                deleteForm: function(formId) {
                    var self = this;
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_delete_form',
                            nonce: '<?php echo $nonce; ?>',
                            form_id: formId
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showNotice('Form deleted successfully!', 'success');
                                setTimeout(function() {
                                    window.location.href = '<?php echo admin_url('admin.php?page=jeforms'); ?>';
                                }, 1000);
                            } else {
                                self.showNotice(response.data.message || 'Error deleting form', 'error');
                            }
                        }
                    });
                },
                
                duplicateForm: function(formId) {
                    var self = this;
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_duplicate_form',
                            nonce: '<?php echo $nonce; ?>',
                            form_id: formId
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showNotice('Form duplicated successfully!', 'success');
                                location.reload();
                            } else {
                                self.showNotice(response.data.message || 'Error duplicating form', 'error');
                            }
                        }
                    });
                },
                
                deleteSubmission: function(submissionId) {
                    var self = this;
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_delete_submission',
                            nonce: '<?php echo $nonce; ?>',
                            submission_id: submissionId
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showNotice('Submission deleted successfully!', 'success');
                                location.reload();
                            } else {
                                self.showNotice(response.data.message || 'Error deleting submission', 'error');
                            }
                        }
                    });
                },
                
                viewSubmission: function(submissionId) {
                    var $modal = $('#jeforms-submission-modal');
                    var $content = $modal.find('.jeforms-modal-body');
                    
                    $content.html('<div style="text-align:center;padding:50px;"><span class="jeforms-loading"></span><p style="margin-top:15px;color:#666;">Loading submission details...</p></div>');
                    $modal.addClass('active');
                    
                    var $row = $('tr[data-submission-id="' + submissionId + '"]');
                    var data = $row.data('submission-data');
                    
                    if (data) {
                        var html = '<div class="jeforms-submission-detail">';
                        for (var key in data) {
                            html += '<div class="jeforms-submission-field">';
                            html += '<label>' + this.escapeHtml(key) + '</label>';
                            
                            var value = data[key];
                            if (typeof value === 'string' && value.indexOf('data:image') === 0) {
                                html += '<div class="value"><img src="' + value + '" class="jeforms-submission-signature" alt="Signature" style="max-width:300px;height:auto;"></div>';
                            } else if (typeof value === 'object') {
                                html += '<div class="value">' + this.escapeHtml(JSON.stringify(value)) + '</div>';
                            } else {
                                html += '<div class="value">' + this.escapeHtml(value || '-') + '</div>';
                            }
                            
                            html += '</div>';
                        }
                        html += '</div>';
                        $content.html(html);
                    }
                },
                
                closeModal: function() {
                    $('.jeforms-modal-overlay').removeClass('active');
                },
                
                exportSubmissions: function() {
                    var formId = $('#jeforms-export-form').val();
                    window.location.href = '<?php echo $ajax_url; ?>?action=jeforms_export_submissions&nonce=<?php echo $nonce; ?>&form_id=' + formId;
                },
                
                saveSettings: function() {
                    var self = this;
                    var settings = {};
                    
                    $('.jeforms-global-setting').each(function() {
                        var key = $(this).data('setting');
                        var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
                        settings[key] = value;
                    });
                    
                    $('#jeforms-save-settings').prop('disabled', true).html('<span class="jeforms-loading"></span> Saving...');
                    
                    $.ajax({
                        url: '<?php echo $ajax_url; ?>',
                        type: 'POST',
                        data: {
                            action: 'jeforms_save_settings',
                            nonce: '<?php echo $nonce; ?>',
                            settings: JSON.stringify(settings)
                        },
                        success: function(response) {
                            if (response.success) {
                                self.showNotice('Settings saved successfully!', 'success');
                            } else {
                                self.showNotice(response.data.message || 'Error saving settings', 'error');
                            }
                        },
                        complete: function() {
                            $('#jeforms-save-settings').prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Settings');
                        }
                    });
                },
                
                previewForm: function() {
                    if (this.currentForm.id) {
                        window.open('<?php echo home_url('?jeforms_preview='); ?>' + this.currentForm.id, '_blank');
                    } else {
                        this.showNotice('Please save the form first to preview', 'info');
                    }
                },
                
                showNotice: function(message, type) {
                    var $notice = $('<div class="jeforms-alert jeforms-alert-' + type + '">' + message + '</div>');
                    $('.jeforms-admin-wrap').prepend($notice);
                    
                    setTimeout(function() {
                        $notice.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 4000);
                },
                
                escapeHtml: function(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.appendChild(document.createTextNode(text));
                    return div.innerHTML;
                }
            };
            
            $(document).ready(function() {
                if ($('.jeforms-admin-wrap').length) {
                    JEFormsAdmin.init();
                }
            });
            
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Frontend inline styles
     */
    public function frontend_inline_styles() {
        ?>
        <style>
        .jeforms-form-wrapper{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;line-height:1.6;}.jeforms-form{background:linear-gradient(135deg,#ffffff 0%,#f8f9fa 100%);padding:40px;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.1);border:1px solid #e9ecef;position:relative;overflow:hidden;}.jeforms-form:before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);}.jeforms-form-row{display:flex;flex-wrap:wrap;margin:0 -15px;}.jeforms-form-field{padding:0 15px;margin-bottom:25px;box-sizing:border-box;position:relative;}.jeforms-form-field.jeforms-width-10{width:10%;}.jeforms-form-field.jeforms-width-20{width:20%;}.jeforms-form-field.jeforms-width-30{width:30%;}.jeforms-form-field.jeforms-width-40{width:40%;}.jeforms-form-field.jeforms-width-50{width:50%;}.jeforms-form-field.jeforms-width-60{width:60%;}.jeforms-form-field.jeforms-width-70{width:70%;}.jeforms-form-field.jeforms-width-80{width:80%;}.jeforms-form-field.jeforms-width-90{width:90%;}.jeforms-form-field.jeforms-width-100{width:100%;}@media (max-width:768px){.jeforms-form{padding:25px;}.jeforms-form-row{margin:0 -10px;}.jeforms-form-field{padding:0 10px;width:100%!important;}}.jeforms-form-field label{display:block;font-size:15px;font-weight:600;color:#2d3748;margin-bottom:10px;transition:color 0.3s ease;}.jeforms-form-field label .jeforms-required{color:#e53e3e;margin-left:3px;}.jeforms-form-field input[type="text"],.jeforms-form-field input[type="email"],.jeforms-form-field input[type="number"],.jeforms-form-field input[type="tel"],.jeforms-form-field input[type="url"],.jeforms-form-field input[type="password"],.jeforms-form-field input[type="date"],.jeforms-form-field input[type="time"],.jeforms-form-field input[type="datetime-local"],.jeforms-form-field textarea,.jeforms-form-field select{width:100%;padding:14px 18px;font-size:15px;border:2px solid #e2e8f0;border-radius:8px;background:white;color:#2d3748;transition:all 0.3s ease;box-sizing:border-box;font-family:inherit;}.jeforms-form-field input:focus,.jeforms-form-field textarea:focus,.jeforms-form-field select:focus{border-color:#667eea;outline:none;box-shadow:0 0 0 4px rgba(102,126,234,0.1);transform:translateY(-2px);}.jeforms-form-field textarea{min-height:140px;resize:vertical;line-height:1.6;}.jeforms-form-field.has-error input,.jeforms-form-field.has-error textarea,.jeforms-form-field.has-error select{border-color:#e53e3e;background:#fff5f5;}.jeforms-form-field.has-error input:focus,.jeforms-form-field.has-error textarea:focus,.jeforms-form-field.has-error select:focus{box-shadow:0 0 0 4px rgba(229,62,62,0.1);}.jeforms-field-error{color:#e53e3e;font-size:13px;margin-top:8px;display:flex;align-items:center;gap:6px;}.jeforms-field-error:before{content:'⚠';font-size:14px;}.jeforms-radio-group,.jeforms-checkbox-group{display:flex;flex-direction:column;gap:12px;margin-top:5px;}.jeforms-radio-item,.jeforms-checkbox-item{display:flex;align-items:center;gap:10px;padding:8px 0;position:relative;}.jeforms-radio-item input,.jeforms-checkbox-item input{width:20px;height:20px;margin:0;position:relative;cursor:pointer;}.jeforms-radio-item label,.jeforms-checkbox-item label{margin:0;font-weight:500;cursor:pointer;color:#4a5568;flex:1;padding:4px 0;}.jeforms-acceptance{display:flex;align-items:flex-start;gap:12px;padding:15px;background:#f7fafc;border-radius:8px;border:1px solid #e2e8f0;}.jeforms-acceptance input{width:20px;height:20px;margin-top:2px;flex-shrink:0;}.jeforms-acceptance label{margin:0;font-weight:500;color:#4a5568;line-height:1.6;}.jeforms-file-upload{position:relative;}.jeforms-file-input{position:absolute;left:-9999px;}.jeforms-file-label{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;border:3px dashed #cbd5e0;border-radius:12px;background:linear-gradient(135deg,#f7fafc 0%,#edf2f7 100%);cursor:pointer;transition:all 0.3s ease;text-align:center;min-height:150px;}.jeforms-file-label:hover{border-color:#667eea;background:linear-gradient(135deg,#edf2f7 0%,#e2e8f0 100%);transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.15);}.jeforms-file-label.dragover{border-color:#667eea;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;}.jeforms-file-label.dragover .dashicons,.jeforms-file-label.dragover span{color:white;}.jeforms-file-label .dashicons{font-size:48px;width:48px;height:48px;margin-bottom:15px;color:#718096;transition:all 0.3s ease;}.jeforms-file-label span{color:#718096;font-size:14px;font-weight:500;transition:all 0.3s ease;}.jeforms-file-list{margin-top:15px;}.jeforms-file-item{display:flex;align-items:center;gap:12px;padding:12px 16px;background:white;border-radius:8px;margin-bottom:8px;border:1px solid #e2e8f0;transition:all 0.3s ease;}.jeforms-file-item:hover{transform:translateX(5px);box-shadow:0 4px 12px rgba(0,0,0,0.08);}.jeforms-file-item .file-name{flex:1;font-size:14px;color:#2d3748;font-weight:500;word-break:break-all;}.jeforms-file-item .file-size{font-size:12px;color:#718096;background:#f7fafc;padding:3px 8px;border-radius:4px;}.jeforms-file-item .remove-file{color:#e53e3e;cursor:pointer;font-size:20px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:4px;transition:all 0.3s ease;}.jeforms-file-item .remove-file:hover{background:#fed7d7;transform:rotate(90deg);}.jeforms-signature-wrapper{border:2px solid #e2e8f0;border-radius:12px;overflow:hidden;background:white;box-shadow:0 4px 6px rgba(0,0,0,0.05);}.jeforms-signature-canvas{width:100%;height:200px;background:white;cursor:crosshair;touch-action:none;display:block;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;}.jeforms-special-field-content .jeforms-signature-wrapper{margin-top:10px;}.jeforms-special-field-content .jeforms-signature-canvas{height:150px;}.jeforms-signature-actions{display:flex;justify-content:space-between;padding:15px;background:#f7fafc;border-top:1px solid #e2e8f0;align-items:center;}.jeforms-signature-hint{font-size:13px;color:#718096;font-style:italic;}.jeforms-signature-clear{padding:10px 20px;font-size:13px;font-weight:600;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:6px;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;gap:8px;}.jeforms-signature-clear:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.3);}.jeforms-signature-clear:active{transform:translateY(0);}.jeforms-special-select-wrapper .jeforms-dynamic-field{margin-top:20px;padding:20px;background:linear-gradient(135deg,#f7fafc 0%,#edf2f7 100%);border-radius:12px;border:2px solid #e2e8f0;animation:fadeIn 0.3s ease;}@keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}.jeforms-submit-wrapper{padding:0 15px;margin-top:20px;}.jeforms-submit-wrapper.align-left{text-align:left;}.jeforms-submit-wrapper.align-center{text-align:center;}.jeforms-submit-wrapper.align-right{text-align:right;}.jeforms-submit-btn{display:inline-flex;align-items:center;justify-content:center;gap:12px;padding:16px 40px;font-size:16px;font-weight:600;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:10px;cursor:pointer;transition:all 0.4s ease;min-width:180px;position:relative;overflow:hidden;box-shadow:0 4px 15px rgba(102,126,234,0.3);}.jeforms-submit-btn:before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.7s ease;}.jeforms-submit-btn:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(102,126,234,0.4);}.jeforms-submit-btn:hover:before{left:100%;}.jeforms-submit-btn:active{transform:translateY(-1px);}.jeforms-submit-btn:disabled{opacity:0.7;cursor:not-allowed;transform:none!important;box-shadow:none!important;}.jeforms-submit-btn.full-width{width:100%;}.jeforms-submit-btn .jeforms-spinner{display:inline-block;width:20px;height:20px;border:3px solid rgba(255,255,255,0.3);border-radius:50%;border-top-color:white;animation:jeforms-spin 1s linear infinite;}@keyframes jeforms-spin{to{transform:rotate(360deg);}}.jeforms-message{padding:20px 25px;border-radius:12px;margin-bottom:25px;display:flex;align-items:center;gap:15px;border-left:5px solid;box-shadow:0 4px 12px rgba(0,0,0,0.08);animation:slideIn 0.5s ease;}@keyframes slideIn{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}.jeforms-message-success{background:linear-gradient(135deg,rgba(72,187,120,0.1) 0%,rgba(72,187,120,0.05) 100%);color:#38a169;border-left-color:#38a169;border:1px solid rgba(72,187,120,0.2);}.jeforms-message-error{background:linear-gradient(135deg,rgba(245,101,101,0.1) 0%,rgba(245,101,101,0.05) 100%);color:#e53e3e;border-left-color:#e53e3e;border:1px solid rgba(245,101,101,0.2);}.jeforms-honeypot{position:absolute;left:-9999px;opacity:0;height:0;width:0;}.jeforms-heading{margin:10px 0 20px;padding:0;color:#2d3748;font-weight:700;position:relative;padding-bottom:10px;}.jeforms-heading:after{content:'';position:absolute;bottom:0;left:0;width:60px;height:3px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:2px;}.jeforms-divider{border:0;height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:25px 0;}.jeforms-spacer{display:block;}.jeforms-html-content{padding:15px 0;}.jeforms-loading-overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.9);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:100;backdrop-filter:blur(5px);border-radius:inherit;}.jeforms-form-wrapper{position:relative;}.jeforms-special-fields::-webkit-scrollbar{width:8px;}.jeforms-special-fields::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px;}.jeforms-special-fields::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:4px;}.jeforms-special-fields::-webkit-scrollbar-thumb:hover{background:#a8a8a8;}@media (max-width:768px){.jeforms-signature-canvas{height:150px;touch-action:none;-webkit-tap-highlight-color:transparent;}.jeforms-signature-clear{padding:8px 16px;font-size:12px;}}
        </style>
        <?php
    }
    
    /**
     * Frontend inline scripts - FIXED: Now includes auto-location address functionality
     */
    public function frontend_inline_scripts() {
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('jeforms_submit_nonce');
        ?>
        <script>
        (function($) {
            'use strict';
            
            var JEFormsFrontend = {
                
                signatureCanvases: {},
                isDrawing: false,
                lastX: 0,
                lastY: 0,
                currentTouchId: null,
                
                init: function() {
                    this.bindEvents();
                    this.initSignatures();
                    this.initDragAndDrop();
                    this.initAutoLocationFields();
                },
                
                bindEvents: function() {
                    var self = this;
                    
                    $(document).on('submit', '.jeforms-form', function(e) {
                        e.preventDefault();
                        self.submitForm($(this));
                    });
                    
                    $(document).on('change', '.jeforms-special-select', function() {
                        self.handleSpecialSelect($(this));
                    });
                    
                    $(document).on('change', '.jeforms-file-input', function() {
                        self.handleFileSelect($(this));
                    });
                    
                    $(document).on('click', '.remove-file', function() {
                        $(this).closest('.jeforms-file-item').remove();
                        $(this).closest('.jeforms-file-upload').find('.jeforms-file-input').val('');
                    });
                    
                    $(document).on('click', '.jeforms-signature-clear', function() {
                        var fieldId = $(this).data('field-id');
                        self.clearSignature(fieldId);
                    });
                    
                    $(document).on('focus', '.jeforms-form input, .jeforms-form textarea, .jeforms-form select', function() {
                        $(this).closest('.jeforms-form-field').addClass('focused');
                    });
                    
                    $(document).on('blur', '.jeforms-form input, .jeforms-form textarea, .jeforms-form select', function() {
                        $(this).closest('.jeforms-form-field').removeClass('focused');
                    });
                    
                    $(document).on('click', '.jeforms-get-location', function() {
                        var $button = $(this);
                        var $field = $button.closest('.jeforms-form-field');
                        self.getLocationForField($field);
                    });
                },
                
                initAutoLocationFields: function() {
                    var self = this;
                    
                    $('.jeforms-auto-address-field').each(function() {
                        var $field = $(this);
                        var readonly = $field.data('readonly') === 'true';
                        
                        if (!readonly) {
                            self.getLocationForField($field);
                        }
                    });
                },
                
                getLocationForField: function($field) {
                    var self = this;
                    var $input = $field.find('input[type="text"]');
                    var $status = $field.find('.jeforms-location-status');
                    var accuracy = $field.data('accuracy') || 'high';
                    var timeout = parseInt($field.data('timeout')) || 10000;
                    
                    $input.prop('readonly', true).css('background', '#f7fafc');
                    $status.html('<span class="jeforms-loading" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:8px;"></span>Detecting location...');
                    
                    if (accuracy === 'high' && navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                self.reverseGeocode(position.coords.latitude, position.coords.longitude, $input, $status);
                            },
                            function(error) {
                                self.getLocationFromIP($input, $status);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: timeout,
                                maximumAge: 0
                            }
                        );
                    } else {
                        self.getLocationFromIP($input, $status);
                    }
                },
                
                reverseGeocode: function(lat, lng, $input, $status) {
                    var self = this;
                    
                    $.ajax({
                        url: 'https://nominatim.openstreetmap.org/reverse',
                        method: 'GET',
                        data: {
                            format: 'json',
                            lat: lat,
                            lon: lng,
                            zoom: 18,
                            addressdetails: 1
                        },
                        headers: {
                            'Accept-Language': 'en',
                            'User-Agent': 'JEForms/1.0'
                        },
                        success: function(response) {
                            if (response && response.address) {
                                var addressParts = [];
                                if (response.address.road) addressParts.push(response.address.road);
                                if (response.address.suburb) addressParts.push(response.address.suburb);
                                if (response.address.city || response.address.town || response.address.village) {
                                    addressParts.push(response.address.city || response.address.town || response.address.village);
                                }
                                if (response.address.state) addressParts.push(response.address.state);
                                if (response.address.postcode) addressParts.push(response.address.postcode);
                                if (response.address.country) addressParts.push(response.address.country);
                                
                                var fullAddress = addressParts.join(', ');
                                $input.val(fullAddress);
                                $status.html('<span style="color:#48bb78;font-size:12px;">✓ Location detected</span>');
                            } else {
                                self.getLocationFromIP($input, $status);
                            }
                        },
                        error: function() {
                            self.getLocationFromIP($input, $status);
                        }
                    });
                },
                
                getLocationFromIP: function($input, $status) {
                    var self = this;
                    
                    $.ajax({
                        url: 'https://ipapi.co/json/',
                        method: 'GET',
                        success: function(response) {
                            if (response && response.city) {
                                var addressParts = [];
                                if (response.city) addressParts.push(response.city);
                                if (response.region) addressParts.push(response.region);
                                if (response.country_name) addressParts.push(response.country_name);
                                
                                var fullAddress = addressParts.join(', ');
                                $input.val(fullAddress);
                                $status.html('<span style="color:#48bb78;font-size:12px;">✓ Approximate location detected</span>');
                            } else {
                                $input.val('Unable to detect location');
                                $status.html('<span style="color:#e53e3e;font-size:12px;">✗ Location detection failed</span>');
                            }
                        },
                        error: function() {
                            $input.val('Location detection unavailable');
                            $status.html('<span style="color:#e53e3e;font-size:12px;">✗ Service unavailable</span>');
                        }
                    });
                },
                
                initSignatures: function() {
                    var self = this;
                    
                    $('.jeforms-signature-canvas').each(function() {
                        var canvas = this;
                        var fieldId = $(canvas).data('field-id');
                        
                        if (self.signatureCanvases[fieldId]) {
                            return;
                        }
                        
                        var ctx = canvas.getContext('2d');
                        var rect = canvas.getBoundingClientRect();
                        
                        canvas.width = rect.width;
                        canvas.height = rect.height;
                        
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        
                        ctx.lineWidth = 3;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.strokeStyle = '#000000';
                        
                        var signatureData = {
                            canvas: canvas,
                            ctx: ctx,
                            drawing: false,
                            hasSignature: false,
                            rect: rect
                        };
                        
                        self.signatureCanvases[fieldId] = signatureData;
                        
                        $(canvas).on('mousedown', function(e) {
                            self.startDrawing(fieldId, e);
                        });
                        
                        $(canvas).on('mousemove', function(e) {
                            self.draw(fieldId, e);
                        });
                        
                        $(canvas).on('mouseup', function() {
                            self.stopDrawing(fieldId);
                        });
                        
                        $(canvas).on('mouseleave', function() {
                            self.stopDrawing(fieldId);
                        });
                        
                        $(canvas).on('touchstart', function(e) {
                            e.preventDefault();
                            if (e.originalEvent.touches.length === 1) {
                                var touch = e.originalEvent.touches[0];
                                self.currentTouchId = touch.identifier;
                                self.startDrawing(fieldId, {
                                    clientX: touch.clientX,
                                    clientY: touch.clientY
                                });
                            }
                        });
                        
                        $(canvas).on('touchmove', function(e) {
                            e.preventDefault();
                            if (e.originalEvent.touches.length === 1) {
                                var touch = e.originalEvent.touches[0];
                                if (touch.identifier === self.currentTouchId) {
                                    self.draw(fieldId, {
                                        clientX: touch.clientX,
                                        clientY: touch.clientY
                                    });
                                }
                            }
                        });
                        
                        $(canvas).on('touchend', function(e) {
                            e.preventDefault();
                            self.stopDrawing(fieldId);
                            self.currentTouchId = null;
                        });
                        
                        $(canvas).on('touchcancel', function(e) {
                            e.preventDefault();
                            self.stopDrawing(fieldId);
                            self.currentTouchId = null;
                        });
                    });
                },
                
                startDrawing: function(fieldId, e) {
                    var sig = this.signatureCanvases[fieldId];
                    if (!sig) return;
                    
                    sig.drawing = true;
                    var rect = sig.canvas.getBoundingClientRect();
                    
                    this.lastX = e.clientX - rect.left;
                    this.lastY = e.clientY - rect.top;
                    
                    sig.ctx.beginPath();
                    sig.ctx.moveTo(this.lastX, this.lastY);
                },
                
                draw: function(fieldId, e) {
                    var sig = this.signatureCanvases[fieldId];
                    if (!sig || !sig.drawing) return;
                    
                    var rect = sig.canvas.getBoundingClientRect();
                    var x = e.clientX - rect.left;
                    var y = e.clientY - rect.top;
                    
                    sig.ctx.lineTo(x, y);
                    sig.ctx.stroke();
                    
                    this.lastX = x;
                    this.lastY = y;
                    sig.hasSignature = true;
                },
                
                stopDrawing: function(fieldId) {
                    var sig = this.signatureCanvases[fieldId];
                    if (!sig) return;
                    
                    sig.drawing = false;
                    sig.ctx.closePath();
                },
                
                clearSignature: function(fieldId) {
                    var sig = this.signatureCanvases[fieldId];
                    if (!sig) return;
                    
                    sig.ctx.fillStyle = '#ffffff';
                    sig.ctx.fillRect(0, 0, sig.canvas.width, sig.canvas.height);
                    sig.hasSignature = false;
                },
                
                getSignatureData: function(fieldId) {
                    var sig = this.signatureCanvases[fieldId];
                    if (sig && sig.hasSignature) {
                        return sig.canvas.toDataURL('image/png');
                    }
                    return '';
                },
                
                handleSpecialSelect: function($select) {
                    var $wrapper = $select.closest('.jeforms-special-select-wrapper');
                    var $dynamicField = $wrapper.find('.jeforms-dynamic-field');
                    var selectedIndex = $select.val();
                    
                    $dynamicField.find('.jeforms-special-field-content').hide();
                    
                    if (selectedIndex !== '') {
                        $dynamicField.show();
                        var $content = $dynamicField.find('.jeforms-special-field-content[data-index="' + selectedIndex + '"]');
                        $content.show();
                        
                        var $sigCanvas = $content.find('.jeforms-signature-canvas');
                        if ($sigCanvas.length) {
                            setTimeout(function() {
                                JEFormsFrontend.initSignatures();
                                
                                $sigCanvas.each(function() {
                                    var canvas = this;
                                    var fieldId = $(canvas).data('field-id');
                                    var sig = JEFormsFrontend.signatureCanvases[fieldId];
                                    
                                    if (sig) {
                                        var rect = canvas.getBoundingClientRect();
                                        canvas.width = rect.width;
                                        canvas.height = rect.height;
                                        
                                        sig.ctx.fillStyle = '#ffffff';
                                        sig.ctx.fillRect(0, 0, canvas.width, canvas.height);
                                        sig.rect = rect;
                                    }
                                });
                            }, 50);
                        }
                    } else {
                        $dynamicField.hide();
                    }
                },
                
                initDragAndDrop: function() {
                    var self = this;
                    
                    $('.jeforms-file-label').each(function() {
                        var $label = $(this);
                        
                        $label.on('dragenter', function(e) {
                            e.preventDefault();
                            $label.addClass('dragover');
                        });
                        
                        $label.on('dragover', function(e) {
                            e.preventDefault();
                        });
                        
                        $label.on('dragleave', function(e) {
                            if (!$(this).has(e.relatedTarget).length) {
                                $label.removeClass('dragover');
                            }
                        });
                        
                        $label.on('drop', function(e) {
                            e.preventDefault();
                            $label.removeClass('dragover');
                            
                            var files = e.originalEvent.dataTransfer.files;
                            if (files.length > 0) {
                                var $input = $label.siblings('.jeforms-file-input');
                                $input[0].files = files;
                                self.handleFileSelect($input);
                            }
                        });
                    });
                },
                
                handleFileSelect: function($input) {
                    var files = $input[0].files;
                    var $wrapper = $input.closest('.jeforms-file-upload');
                    var $list = $wrapper.find('.jeforms-file-list');
                    var multiple = $input.attr('multiple');
                    
                    if (!multiple) {
                        $list.empty();
                    }
                    
                    for (var i = 0; i < files.length; i++) {
                        var file = files[i];
                        var fileSize = (file.size / 1024).toFixed(1);
                        var fileSizeText = fileSize > 1024 ? (fileSize / 1024).toFixed(1) + ' MB' : fileSize + ' KB';
                        
                        var html = '<div class="jeforms-file-item">';
                        html += '<span class="file-name">' + file.name + '</span>';
                        html += '<span class="file-size">' + fileSizeText + '</span>';
                        html += '<span class="remove-file">&times;</span>';
                        html += '</div>';
                        $list.append(html);
                    }
                },
                
                submitForm: function($form) {
                    var self = this;
                    var formId = $form.data('form-id');
                    var $wrapper = $form.closest('.jeforms-form-wrapper');
                    var $submitBtn = $form.find('.jeforms-submit-btn');
                    var originalText = $submitBtn.html();
                    
                    $form.find('.jeforms-form-field').removeClass('has-error');
                    $form.find('.jeforms-field-error').remove();
                    $form.find('.jeforms-message').remove();
                    
                    var errors = this.validateForm($form);
                    
                    if (errors.length > 0) {
                        errors.forEach(function(error) {
                            var $field = $form.find('[name="' + error.field + '"]').closest('.jeforms-form-field');
                            $field.addClass('has-error');
                            $field.append('<div class="jeforms-field-error">' + error.message + '</div>');
                        });
                        
                        var $firstError = $form.find('.has-error').first();
                        if ($firstError.length) {
                            $('html, body').animate({
                                scrollTop: $firstError.offset().top - 100
                            }, 500);
                            
                            $firstError.addClass('shake');
                            setTimeout(function() {
                                $firstError.removeClass('shake');
                            }, 500);
                        }
                        
                        return;
                    }
                    
                    var formData = new FormData($form[0]);
                    
                    formData.append('action', 'jeforms_submit_form');
                    formData.append('nonce', '<?php echo $nonce; ?>');
                    formData.append('form_id', formId);
                    
                    $form.find('.jeforms-signature-canvas').each(function() {
                        var fieldId = $(this).data('field-id');
                        var fieldName = $(this).data('field-name');
                        var sigData = self.getSignatureData(fieldId);
                        if (sigData) {
                            formData.append(fieldName, sigData);
                        }
                    });
                    
                    $form.find('.jeforms-special-select-wrapper').each(function() {
                        var $wrapper = $(this);
                        var $select = $wrapper.find('.jeforms-special-select');
                        var selectorName = $select.attr('name');
                        var baseName = selectorName.replace('_selector', '');
                        var selectedIndex = $select.val();
                        
                        if (selectedIndex !== '') {
                            var $activeField = $wrapper.find('.jeforms-special-field-content[data-index="' + selectedIndex + '"]');
                            var fieldType = $activeField.data('type');
                            var fieldName = $activeField.data('name');
                            var isRequired = $activeField.data('required') === '1';
                            
                            formData.append(baseName + '_type', fieldType);
                            formData.append(baseName + '_name', fieldName);
                            formData.append(baseName + '_selected_index', selectedIndex);
                            
                            if (fieldType === 'signature') {
                                var $sigCanvas = $activeField.find('.jeforms-signature-canvas');
                                if ($sigCanvas.length) {
                                    var sigFieldId = $sigCanvas.data('field-id');
                                    var sigData = self.getSignatureData(sigFieldId);
                                    if (sigData) {
                                        formData.append(baseName + '_value', sigData);
                                    } else if (isRequired) {
                                        errors.push({
                                            field: selectorName,
                                            message: fieldName + ' signature is required'
                                        });
                                    }
                                }
                            } else {
                                var $input = $activeField.find('input, textarea, select');
                                if ($input.length) {
                                    var value = $input.val();
                                    formData.append(baseName + '_value', value);
                                    
                                    if (isRequired && !value) {
                                        errors.push({
                                            field: selectorName,
                                            message: fieldName + ' is required'
                                        });
                                    }
                                }
                            }
                        }
                    });
                    
                    if (errors.length > 0) {
                        errors.forEach(function(error) {
                            var $field = $form.find('[name="' + error.field + '"]').closest('.jeforms-form-field');
                            $field.addClass('has-error');
                            $field.append('<div class="jeforms-field-error">' + error.message + '</div>');
                        });
                        return;
                    }
                    
                    $submitBtn.prop('disabled', true).html('<span class="jeforms-spinner"></span> Processing...');
                    
                    $wrapper.append('<div class="jeforms-loading-overlay"><div class="jeforms-loading"></div><p style="margin-top:15px;color:#667eea;font-weight:500;">Submitting your form...</p></div>');
                    
                    $.ajax({
                        url: '<?php echo esc_js($ajax_url); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        timeout: 60000,
                        success: function(response) {
                            $wrapper.find('.jeforms-loading-overlay').remove();
                            
                            if (response.success) {
                                var successHtml = '<div class="jeforms-message jeforms-message-success">';
                                successHtml += '<svg style="width:24px;height:24px;flex-shrink:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>';
                                successHtml += '<div>' + response.data.message + '</div>';
                                successHtml += '</div>';
                                
                                $form.prepend(successHtml);
                                
                                $form[0].reset();
                                
                                for (var fieldId in self.signatureCanvases) {
                                    self.clearSignature(fieldId);
                                }
                                
                                $form.find('.jeforms-special-select').val('').trigger('change');
                                
                                $form.find('.jeforms-file-list').empty();
                                
                                $('html, body').animate({
                                    scrollTop: $wrapper.offset().top - 50
                                }, 500);
                                
                                if (response.data.redirect) {
                                    setTimeout(function() {
                                        window.location.href = response.data.redirect;
                                    }, 2000);
                                }
                            } else {
                                var errorHtml = '<div class="jeforms-message jeforms-message-error">';
                                errorHtml += '<svg style="width:24px;height:24px;flex-shrink:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                                errorHtml += '<div>' + (response.data.message || 'An error occurred. Please try again.') + '</div>';
                                errorHtml += '</div>';
                                $form.prepend(errorHtml);
                            }
                        },
                        error: function(xhr, status, error) {
                            $wrapper.find('.jeforms-loading-overlay').remove();
                            console.error('Form submission error:', xhr.responseText);
                            
                            var errorHtml = '<div class="jeforms-message jeforms-message-error">';
                            errorHtml += '<svg style="width:24px;height:24px;flex-shrink:0;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                            errorHtml += '<div>Submission failed. Please try again.</div>';
                            if (xhr.responseText) {
                                errorHtml += '<div style="font-size:12px;margin-top:5px;">Error details: ' + xhr.responseText.substring(0, 100) + '</div>';
                            }
                            errorHtml += '</div>';
                            $form.prepend(errorHtml);
                        },
                        complete: function() {
                            $submitBtn.prop('disabled', false).html(originalText);
                        }
                    });
                },
                
                validateForm: function($form) {
                    var self = this;
                    var errors = [];
                    
                    $form.find('.jeforms-form-field').each(function() {
                        var $field = $(this);
                        var $input = $field.find('input, textarea, select');
                        var fieldName = $input.attr('name');
                        var fieldType = $field.data('type') || $input.attr('type');
                        var fieldLabel = $field.find('label').text().replace(' *', '').replace(':', '').trim();
                        var required = $input.data('required') == '1' || $field.data('required') == '1' || $input.prop('required');
                        
                        if (!required || !fieldName) {
                            return;
                        }
                        
                        var value = '';
                        
                        if (fieldType === 'checkbox' && $field.data('type') !== 'acceptance') {
                            if (!$field.find('input[type="checkbox"]:checked').length) {
                                errors.push({
                                    field: fieldName,
                                    message: fieldLabel + ' is required'
                                });
                            }
                        } else if (fieldType === 'signature') {
                            var fieldId = $field.find('.jeforms-signature-canvas').data('field-id');
                            if (!self.signatureCanvases[fieldId] || !self.signatureCanvases[fieldId].hasSignature) {
                                errors.push({
                                    field: fieldName,
                                    message: fieldLabel + ' signature is required'
                                });
                            }
                        } else if (fieldType === 'acceptance') {
                            if (!$field.find('input[type="checkbox"]').is(':checked')) {
                                errors.push({
                                    field: fieldName,
                                    message: 'You must accept the terms'
                                });
                            }
                        } else if (fieldType === 'special_select') {
                            var selectedIndex = $field.find('.jeforms-special-select').val();
                            if (selectedIndex === '') {
                                errors.push({
                                    field: fieldName,
                                    message: fieldLabel + ' is required'
                                });
                            }
                        } else if (fieldType === 'file') {
                            if (!$field.find('.jeforms-file-input')[0].files.length) {
                                errors.push({
                                    field: fieldName,
                                    message: fieldLabel + ' is required'
                                });
                            }
                        } else {
                            value = $input.val();
                            if (!value || value.trim() === '') {
                                errors.push({
                                    field: fieldName,
                                    message: fieldLabel + ' is required'
                                });
                            }
                        }
                    });
                    
                    $form.find('input[type="email"]').each(function() {
                        var value = $(this).val();
                        if (value && !self.isValidEmail(value)) {
                            var fieldLabel = $(this).closest('.jeforms-form-field').find('label').text().replace(' *', '').replace(':', '').trim();
                            errors.push({
                                field: $(this).attr('name'),
                                message: 'Please enter a valid email address'
                            });
                        }
                    });
                    
                    $form.find('input[type="url"]').each(function() {
                        var value = $(this).val();
                        if (value && !self.isValidUrl(value)) {
                            errors.push({
                                field: $(this).attr('name'),
                                message: 'Please enter a valid URL'
                            });
                        }
                    });
                    
                    return errors;
                },
                
                isValidEmail: function(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                },
                
                isValidUrl: function(url) {
                    if (!url) return true;
                    try {
                        new URL(url);
                        return true;
                    } catch (e) {
                        return false;
                    }
                }
            };
            
            $(document).ready(function() {
                JEFormsFrontend.init();
                
                var style = document.createElement('style');
                style.textContent = '.shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; } @keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }';
                document.head.appendChild(style);
                
                function setViewportHeight() {
                    let vh = window.innerHeight * 0.01;
                    document.documentElement.style.setProperty('--vh', vh + 'px');
                }
                
                setViewportHeight();
                window.addEventListener('resize', setViewportHeight);
                window.addEventListener('orientationchange', setViewportHeight);
            });
            
        })(jQuery);
        </script>
        <?php
    }
    
    /**
     * Render forms list page
     */
    public function render_forms_page() {
        global $wpdb;
        
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        $this->check_tables_exist();
        
        $total_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table");
        $active_forms = $wpdb->get_var("SELECT COUNT(*) FROM $forms_table WHERE status = 'active'");
        $total_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
        $unread_submissions = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'unread'");
        
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $forms = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.*, 
                    (SELECT COUNT(*) FROM $submissions_table WHERE form_id = f.id) as submission_count
                FROM $forms_table f 
                ORDER BY f.created_at DESC 
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        $total_pages = ceil($total_forms / $per_page);
        
        ?>
        <div class="jeforms-admin-wrap">
            <div class="jeforms-header">
                <h1><span class="dashicons dashicons-feedback"></span> JE Forms Dashboard</h1>
                <div class="jeforms-header-actions">
                    <a href="<?php echo admin_url('admin.php?page=jeforms-add-new'); ?>" class="jeforms-btn jeforms-btn-primary">
                        <span class="dashicons dashicons-plus"></span> Create New Form
                    </a>
                </div>
            </div>
            
            <div class="jeforms-stats">
                <div class="jeforms-stat-card">
                    <div class="jeforms-stat-icon blue">
                        <span class="dashicons dashicons-feedback"></span>
                    </div>
                    <div class="jeforms-stat-content">
                        <h4><?php echo esc_html($total_forms); ?></h4>
                        <p>Total Forms</p>
                    </div>
                </div>
                <div class="jeforms-stat-card">
                    <div class="jeforms-stat-icon green">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="jeforms-stat-content">
                        <h4><?php echo esc_html($active_forms); ?></h4>
                        <p>Active Forms</p>
                    </div>
                </div>
                <div class="jeforms-stat-card">
                    <div class="jeforms-stat-icon purple">
                        <span class="dashicons dashicons-email"></span>
                    </div>
                    <div class="jeforms-stat-content">
                        <h4><?php echo esc_html($total_submissions); ?></h4>
                        <p>Total Submissions</p>
                    </div>
                </div>
                <div class="jeforms-stat-card">
                    <div class="jeforms-stat-icon orange">
                        <span class="dashicons dashicons-email-alt"></span>
                    </div>
                    <div class="jeforms-stat-content">
                        <h4><?php echo esc_html($unread_submissions); ?></h4>
                        <p>Unread Submissions</p>
                    </div>
                </div>
            </div>
            
            <div class="jeforms-card">
                <div class="jeforms-card-header">
                    <h2>All Forms</h2>
                    <div style="font-size:13px;color:#666;">
                        Showing <?php echo esc_html(count($forms)); ?> of <?php echo esc_html($total_forms); ?> forms
                    </div>
                </div>
                
                <?php if (empty($forms)) : ?>
                    <div class="jeforms-empty-state">
                        <span class="dashicons dashicons-feedback"></span>
                        <h3>No forms yet</h3>
                        <p>Create your first form to start collecting submissions from your visitors.</p>
                        <a href="<?php echo admin_url('admin.php?page=jeforms-add-new'); ?>" class="jeforms-btn jeforms-btn-primary">
                            <span class="dashicons dashicons-plus"></span> Create Your First Form
                        </a>
                    </div>
                <?php else : ?>
                    <table class="jeforms-table">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Shortcode</th>
                                <th>Submissions</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forms as $form) : 
                                $fields = json_decode($form->fields, true);
                                $field_count = is_array($fields) ? count($fields) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=jeforms-add-new&form_id=' . $form->id); ?>">
                                                <?php echo esc_html($form->title); ?>
                                            </a>
                                            <div style="font-size:12px;color:#718096;margin-top:5px;">
                                                <?php echo esc_html($field_count); ?> fields
                                            </div>
                                        </strong>
                                    </td>
                                    <td>
                                        <code class="jeforms-shortcode" title="Click to copy">
                                            [jeform id="<?php echo esc_attr($form->id); ?>"]
                                        </code>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=jeforms-submissions&form_id=' . $form->id); ?>" style="color:#667eea;text-decoration:none;font-weight:600;">
                                            <?php echo esc_html($form->submission_count); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="jeforms-form-status <?php echo esc_attr($form->status); ?>">
                                            <?php echo esc_html(ucfirst($form->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('M j, Y', strtotime($form->created_at))); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?php echo admin_url('admin.php?page=jeforms-add-new&form_id=' . $form->id); ?>" class="jeforms-btn jeforms-btn-secondary jeforms-btn-sm" title="Edit">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                            <a href="#" class="jeforms-btn jeforms-btn-secondary jeforms-btn-sm jeforms-duplicate-form" data-id="<?php echo esc_attr($form->id); ?>" title="Duplicate">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </a>
                                            <a href="<?php echo admin_url('admin.php?page=jeforms-submissions&form_id=' . $form->id); ?>" class="jeforms-btn jeforms-btn-secondary jeforms-btn-sm" title="View Submissions">
                                                <span class="dashicons dashicons-email"></span>
                                            </a>
                                            <a href="#" class="jeforms-btn jeforms-btn-danger jeforms-btn-sm jeforms-delete-form" data-id="<?php echo esc_attr($form->id); ?>" title="Delete">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1) : ?>
                        <div class="jeforms-pagination">
                            <?php
                            for ($i = 1; $i <= $total_pages; $i++) {
                                $class = ($i == $current_page) ? 'current' : '';
                                if ($i == $current_page) {
                                    echo '<span class="' . $class . '">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . add_query_arg('paged', $i) . '">' . $i . '</a>';
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render form builder page
     */
    public function render_form_builder_page() {
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        ?>
        <div class="jeforms-admin-wrap">
            <div class="jeforms-header">
                <h1><span class="dashicons dashicons-edit"></span> <?php echo $form_id ? 'Edit Form' : 'Create New Form'; ?></h1>
                <div class="jeforms-header-actions">
                    <button type="button" id="jeforms-preview-form" class="jeforms-btn jeforms-btn-secondary">
                        <span class="dashicons dashicons-visibility"></span> Preview
                    </button>
                    <button type="button" id="jeforms-save-form" class="jeforms-btn jeforms-btn-success">
                        <span class="dashicons dashicons-saved"></span> Save Form
                    </button>
                </div>
            </div>
            
            <div class="jeforms-builder-wrap">
                <div class="jeforms-builder-sidebar">
                    <div class="jeforms-field-palette">
                        <div class="jeforms-field-category">
                            <h4>Basic Fields</h4>
                            <div class="jeforms-field-items">
                                <?php foreach ($this->form_fields as $type => $field) : ?>
                                    <?php if ($field['category'] === 'basic') : ?>
                                        <div class="jeforms-field-item" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="dashicons <?php echo esc_attr($field['icon']); ?>"></span>
                                            <?php echo esc_html($field['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="jeforms-field-category">
                            <h4>Choice Fields</h4>
                            <div class="jeforms-field-items">
                                <?php foreach ($this->form_fields as $type => $field) : ?>
                                    <?php if ($field['category'] === 'choice') : ?>
                                        <div class="jeforms-field-item" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="dashicons <?php echo esc_attr($field['icon']); ?>"></span>
                                            <?php echo esc_html($field['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="jeforms-field-category">
                            <h4>Advanced Fields</h4>
                            <div class="jeforms-field-items">
                                <?php foreach ($this->form_fields as $type => $field) : ?>
                                    <?php if ($field['category'] === 'advanced') : ?>
                                        <div class="jeforms-field-item" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="dashicons <?php echo esc_attr($field['icon']); ?>"></span>
                                            <?php echo esc_html($field['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="jeforms-field-category">
                            <h4>Layout Elements</h4>
                            <div class="jeforms-field-items">
                                <?php foreach ($this->form_fields as $type => $field) : ?>
                                    <?php if ($field['category'] === 'layout') : ?>
                                        <div class="jeforms-field-item" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="dashicons <?php echo esc_attr($field['icon']); ?>"></span>
                                            <?php echo esc_html($field['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-builder-main">
                    <div class="jeforms-canvas-header">
                        <input type="text" id="jeforms-form-title" placeholder="Enter your form title here..." value="Untitled Form" autocomplete="off">
                        <div class="jeforms-shortcode-display" style="font-family:'Courier New',monospace;font-size:13px;color:#718096;background:#f7fafc;padding:8px 12px;border-radius:6px;border:1px solid #e2e8f0;">
                            <?php if ($form_id) : ?>
                                <strong style="color:#2d3748;">Shortcode:</strong> [jeform id="<?php echo esc_attr($form_id); ?>"]
                            <?php else : ?>
                                <span style="color:#a0aec0;">Save form to generate shortcode</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="jeforms-canvas-body">
                        <div class="jeforms-canvas-dropzone">
                            <div class="jeforms-canvas-empty">
                                <span class="dashicons dashicons-welcome-widgets-menus"></span>
                                <p>Drag and drop fields from the left panel or click on field items to add them to your form</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-builder-settings">
                    <div class="jeforms-settings-panel">
                        <div class="jeforms-settings-tabs">
                            <button type="button" class="jeforms-settings-tab active" data-target="field-settings">Field Settings</button>
                            <button type="button" class="jeforms-settings-tab" data-target="form-settings">Form Settings</button>
                            <button type="button" class="jeforms-settings-tab" data-target="email-settings">Email Settings</button>
                            <button type="button" class="jeforms-settings-tab" data-target="style-settings">Style Settings</button>
                        </div>
                        
                        <div class="jeforms-settings-content">
                            <div id="field-settings" class="jeforms-settings-section active">
                                <div class="jeforms-settings-content-inner">
                                    <div class="jeforms-empty-state" style="padding:50px 20px;">
                                        <span class="dashicons dashicons-admin-generic" style="font-size:40px;width:40px;height:40px;"></span>
                                        <p>Select a field to edit its settings</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="form-settings" class="jeforms-settings-section">
                                <div class="jeforms-setting-group">
                                    <label>Success Message</label>
                                    <textarea class="jeforms-form-setting" data-setting="success_message" rows="3" autocomplete="off">Thank you! Your submission has been received.</textarea>
                                    <p class="description">Message shown after successful form submission</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Redirect URL (Optional)</label>
                                    <input type="url" class="jeforms-form-setting" data-setting="redirect_url" placeholder="https://example.com/thank-you" autocomplete="off">
                                    <p class="description">Leave empty to show success message on same page</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Submit Button Text</label>
                                    <input type="text" class="jeforms-form-setting" data-setting="submit_button_text" value="Submit" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Form CSS Class</label>
                                    <input type="text" class="jeforms-form-setting" data-setting="form_class" placeholder="custom-form-class" autocomplete="off">
                                    <p class="description">Additional CSS classes for custom styling</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <div class="jeforms-toggle">
                                        <label class="jeforms-toggle-switch">
                                            <input type="checkbox" class="jeforms-form-setting" data-setting="enable_ajax" checked>
                                            <span class="jeforms-toggle-slider"></span>
                                        </label>
                                        <span>AJAX Submission</span>
                                    </div>
                                    <p class="description">Submit form without page reload</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <div class="jeforms-toggle">
                                        <label class="jeforms-toggle-switch">
                                            <input type="checkbox" class="jeforms-form-setting" data-setting="store_submissions" checked>
                                            <span class="jeforms-toggle-slider"></span>
                                        </label>
                                        <span>Store Submissions</span>
                                    </div>
                                    <p class="description">Save submissions to database</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <div class="jeforms-toggle">
                                        <label class="jeforms-toggle-switch">
                                            <input type="checkbox" class="jeforms-form-setting" data-setting="honeypot" checked>
                                            <span class="jeforms-toggle-slider"></span>
                                        </label>
                                        <span>Honeypot Protection</span>
                                    </div>
                                    <p class="description">Basic spam protection using hidden field</p>
                                </div>
                            </div>
                            
                            <div id="email-settings" class="jeforms-settings-section">
                                <div class="jeforms-setting-group">
                                    <label>Send To Email</label>
                                    <input type="email" class="jeforms-form-setting" data-setting="email_to" value="<?php echo esc_attr(get_option('admin_email')); ?>" autocomplete="off">
                                    <p class="description">Separate multiple emails with commas</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Email Subject</label>
                                    <input type="text" class="jeforms-form-setting" data-setting="email_subject" value="New Form Submission" autocomplete="off">
                                    <p class="description">Subject line for notification emails</p>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>From Name</label>
                                    <input type="text" class="jeforms-form-setting" data-setting="email_from_name" value="<?php echo esc_attr(get_bloginfo('name')); ?>" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>From Email</label>
                                    <input type="email" class="jeforms-form-setting" data-setting="email_from_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" autocomplete="off">
                                </div>
                            </div>
                            
                            <div id="style-settings" class="jeforms-settings-section">
                                <div class="jeforms-setting-group">
                                    <label>Form Background Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="form_bg_color" value="#ffffff">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Form Padding (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="form_padding" value="40" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Form Border Radius (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="form_border_radius" value="15" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Label Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="label_color" value="#2d3748">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Label Font Size (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="label_font_size" value="15" min="10" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Background Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="input_bg_color" value="#ffffff">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Border Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="input_border_color" value="#e2e8f0">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Focus Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="input_focus_color" value="#667eea">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Text Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="input_text_color" value="#2d3748">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Padding (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="input_padding" value="14" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Input Border Radius (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="input_border_radius" value="8" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Background Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="button_bg_color" value="#667eea">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Text Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="button_text_color" value="#ffffff">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Hover Color</label>
                                    <input type="text" class="jeforms-style-setting jeforms-color-picker" data-setting="button_hover_color" value="#764ba2">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Padding (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="button_padding" value="16" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Border Radius (px)</label>
                                    <input type="number" class="jeforms-style-setting" data-setting="button_border_radius" value="10" min="0" autocomplete="off">
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Width</label>
                                    <select class="jeforms-style-setting" data-setting="button_width">
                                        <option value="auto">Auto</option>
                                        <option value="full">Full Width</option>
                                    </select>
                                </div>
                                
                                <div class="jeforms-setting-group">
                                    <label>Button Alignment</label>
                                    <select class="jeforms-style-setting" data-setting="button_alignment">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        global $wpdb;
        
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        $this->check_tables_exist();
        
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        $forms = $wpdb->get_results("SELECT id, title FROM $forms_table ORDER BY title ASC");
        
        $where = array('1=1');
        $params = array();
        
        if ($form_id) {
            $where[] = 's.form_id = %d';
            $params[] = $form_id;
        }
        
        if ($status) {
            $where[] = 's.status = %s';
            $params[] = $status;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $count_sql = "SELECT COUNT(*) FROM $submissions_table s WHERE $where_clause";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total_items = $wpdb->get_var($count_sql);
        $total_pages = ceil($total_items / $per_page);
        
        $query = "SELECT s.*, f.title as form_title 
                  FROM $submissions_table s 
                  LEFT JOIN $forms_table f ON s.form_id = f.id 
                  WHERE $where_clause 
                  ORDER BY s.created_at DESC 
                  LIMIT %d OFFSET %d";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
        
        ?>
        <div class="jeforms-admin-wrap">
            <div class="jeforms-header">
                <h1><span class="dashicons dashicons-email"></span> Form Submissions</h1>
                <div class="jeforms-header-actions">
                    <select id="jeforms-export-form" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:6px;min-width:200px;">
                        <option value="">Export All Forms</option>
                        <?php foreach ($forms as $form) : ?>
                            <option value="<?php echo esc_attr($form->id); ?>" <?php selected($form_id, $form->id); ?>>
                                <?php echo esc_html($form->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="jeforms-export-csv" class="jeforms-btn jeforms-btn-secondary">
                        <span class="dashicons dashicons-download"></span> Export as CSV
                    </button>
                </div>
            </div>
            
            <div class="jeforms-card">
                <div class="jeforms-filter-bar">
                    <div style="display:flex;gap:15px;align-items:center;">
                        <select onchange="window.location.href=this.value" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:6px;min-width:200px;">
                            <option value="<?php echo admin_url('admin.php?page=jeforms-submissions'); ?>">All Forms</option>
                            <?php foreach ($forms as $form) : ?>
                                <option value="<?php echo admin_url('admin.php?page=jeforms-submissions&form_id=' . $form->id); ?>" <?php selected($form_id, $form->id); ?>>
                                    <?php echo esc_html($form->title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select onchange="window.location.href=this.value" style="padding:10px 14px;border:2px solid #e2e8f0;border-radius:6px;min-width:150px;">
                            <option value="<?php echo add_query_arg('status', '', remove_query_arg('status')); ?>">All Statuses</option>
                            <option value="<?php echo add_query_arg('status', 'unread'); ?>" <?php selected($status, 'unread'); ?>>Unread</option>
                            <option value="<?php echo add_query_arg('status', 'read'); ?>" <?php selected($status, 'read'); ?>>Read</option>
                        </select>
                    </div>
                    <div style="font-size:14px;color:#4a5568;">
                        <strong><?php echo esc_html($total_items); ?></strong> submissions found
                    </div>
                </div>
                
                <?php if (empty($submissions)) : ?>
                    <div class="jeforms-empty-state">
                        <span class="dashicons dashicons-email"></span>
                        <h3>No submissions yet</h3>
                        <p>Submissions will appear here when someone submits one of your forms.</p>
                        <a href="<?php echo admin_url('admin.php?page=jeforms'); ?>" class="jeforms-btn jeforms-btn-primary">
                            <span class="dashicons dashicons-feedback"></span> View Forms
                        </a>
                    </div>
                <?php else : ?>
                    <table class="jeforms-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Form</th>
                                <th>Preview</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission) : 
                                $data = json_decode($submission->data, true);
                                $preview = '';
                                if (is_array($data)) {
                                    $preview_items = array_slice($data, 0, 2);
                                    $preview_parts = array();
                                    foreach ($preview_items as $key => $value) {
                                        if (is_string($value) && strpos($value, 'data:image') !== 0) {
                                            $preview_parts[] = wp_trim_words($value, 5);
                                        } elseif (is_string($value)) {
                                            $preview_parts[] = '[Signature]';
                                        }
                                    }
                                    $preview = implode(' | ', $preview_parts);
                                }
                            ?>
                                <tr data-submission-id="<?php echo esc_attr($submission->id); ?>" data-submission-data='<?php echo esc_attr(wp_json_encode($data)); ?>'>
                                    <td style="font-family:'Courier New',monospace;color:#667eea;font-weight:600;">#<?php echo esc_html($submission->id); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($submission->form_title); ?></strong>
                                        <?php if ($submission->user_id) : ?>
                                            <div style="font-size:11px;color:#718096;margin-top:3px;">
                                                User ID: <?php echo esc_html($submission->user_id); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width:300px;word-break:break-word;"><?php echo esc_html($preview) ?: '<em style="color:#a0aec0;">No preview available</em>'; ?></td>
                                    <td>
                                        <span class="jeforms-submission-status <?php echo esc_attr($submission->status); ?>">
                                            <?php echo esc_html(ucfirst($submission->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($submission->created_at))); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="#" class="jeforms-btn jeforms-btn-secondary jeforms-btn-sm jeforms-view-submission" data-id="<?php echo esc_attr($submission->id); ?>" title="View Details">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </a>
                                            <a href="#" class="jeforms-btn jeforms-btn-danger jeforms-btn-sm jeforms-delete-submission" data-id="<?php echo esc_attr($submission->id); ?>" title="Delete">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1) : ?>
                        <div class="jeforms-pagination">
                            <?php
                            for ($i = 1; $i <= $total_pages; $i++) {
                                $class = ($i == $current_page) ? 'current' : '';
                                if ($i == $current_page) {
                                    echo '<span class="' . $class . '">' . $i . '</span>';
                                } else {
                                    echo '<a href="' . add_query_arg('paged', $i) . '">' . $i . '</a>';
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="jeforms-modal-overlay" id="jeforms-submission-modal">
            <div class="jeforms-modal">
                <div class="jeforms-modal-header">
                    <h3>Submission Details</h3>
                    <button type="button" class="jeforms-modal-close">&times;</button>
                </div>
                <div class="jeforms-modal-body">
                </div>
                <div class="jeforms-modal-footer">
                    <button type="button" class="jeforms-btn jeforms-btn-secondary jeforms-modal-close">Close</button>
                    <button type="button" class="jeforms-btn jeforms-btn-danger" onclick="window.print();">
                        <span class="dashicons dashicons-printer"></span> Print
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = get_option('jeforms_settings', array());
        ?>
        <div class="jeforms-admin-wrap">
            <div class="jeforms-header">
                <h1><span class="dashicons dashicons-admin-settings"></span> JE Forms Settings</h1>
                <div class="jeforms-header-actions">
                    <button type="button" id="jeforms-save-settings" class="jeforms-btn jeforms-btn-success">
                        <span class="dashicons dashicons-saved"></span> Save Settings
                    </button>
                </div>
            </div>
            
            <div class="jeforms-settings-grid">
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>General Settings</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <label>Default From Name</label>
                            <input type="text" class="jeforms-global-setting" data-setting="email_from_name" value="<?php echo esc_attr($settings['email_from_name'] ?? get_bloginfo('name')); ?>" autocomplete="off">
                            <p class="description">Default sender name for form notification emails</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Default From Email</label>
                            <input type="email" class="jeforms-global-setting" data-setting="email_from_email" value="<?php echo esc_attr($settings['email_from_email'] ?? get_option('admin_email')); ?>" autocomplete="off">
                            <p class="description">Default sender email address</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Default Success Message</label>
                            <textarea class="jeforms-global-setting" data-setting="default_success_message" rows="3" autocomplete="off"><?php echo esc_textarea($settings['default_success_message'] ?? 'Thank you! Your submission has been received.'); ?></textarea>
                            <p class="description">Default message shown after successful form submission</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Date Format</label>
                            <select class="jeforms-global-setting" data-setting="date_format">
                                <option value="Y-m-d" <?php selected($settings['date_format'] ?? 'Y-m-d', 'Y-m-d'); ?>>YYYY-MM-DD</option>
                                <option value="m/d/Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'm/d/Y'); ?>>MM/DD/YYYY</option>
                                <option value="d/m/Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'd/m/Y'); ?>>DD/MM/YYYY</option>
                                <option value="F j, Y" <?php selected($settings['date_format'] ?? 'Y-m-d', 'F j, Y'); ?>>Month Day, Year</option>
                            </select>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Time Format</label>
                            <select class="jeforms-global-setting" data-setting="time_format">
                                <option value="H:i" <?php selected($settings['time_format'] ?? 'H:i', 'H:i'); ?>>24-hour (14:30)</option>
                                <option value="g:i A" <?php selected($settings['time_format'] ?? 'H:i', 'g:i A'); ?>>12-hour (2:30 PM)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>File Upload Settings</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <label>Allowed File Types</label>
                            <input type="text" class="jeforms-global-setting" data-setting="allowed_file_types" value="<?php echo esc_attr($settings['allowed_file_types'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'); ?>" autocomplete="off">
                            <p class="description">Comma-separated list of extensions (jpg, png, pdf, etc.)</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Max File Size (MB)</label>
                            <input type="number" class="jeforms-global-setting" data-setting="max_file_size" value="<?php echo esc_attr($settings['max_file_size'] ?? 5); ?>" min="1" max="100" autocomplete="off">
                            <p class="description">Maximum file size in megabytes</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Upload Directory</label>
                            <input type="text" class="jeforms-global-setting" data-setting="upload_path" value="<?php echo esc_attr($settings['upload_path'] ?? 'jeforms-uploads'); ?>" autocomplete="off">
                            <p class="description">Folder name inside wp-content/uploads/</p>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>Signature Settings</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <label>Default Signature Width (px)</label>
                            <input type="number" class="jeforms-global-setting" data-setting="signature_width" value="<?php echo esc_attr($settings['signature_width'] ?? 400); ?>" min="200" max="800" autocomplete="off">
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Default Signature Height (px)</label>
                            <input type="number" class="jeforms-global-setting" data-setting="signature_height" value="<?php echo esc_attr($settings['signature_height'] ?? 200); ?>" min="100" max="400" autocomplete="off">
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Signature Line Color</label>
                            <input type="text" class="jeforms-global-setting jeforms-color-picker" data-setting="signature_line_color" value="<?php echo esc_attr($settings['signature_line_color'] ?? '#000000'); ?>">
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Signature Line Width</label>
                            <input type="number" class="jeforms-global-setting" data-setting="signature_line_width" value="<?php echo esc_attr($settings['signature_line_width'] ?? 3); ?>" min="1" max="10" autocomplete="off">
                            <p class="description">Thickness of signature lines in pixels</p>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>reCAPTCHA Settings</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <div class="jeforms-toggle">
                                <label class="jeforms-toggle-switch">
                                    <input type="checkbox" class="jeforms-global-setting" data-setting="enable_recaptcha" <?php checked($settings['enable_recaptcha'] ?? false); ?>>
                                    <span class="jeforms-toggle-slider"></span>
                                </label>
                                <span>Enable reCAPTCHA v3</span>
                            </div>
                            <p class="description">Adds invisible reCAPTCHA protection to forms</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Site Key</label>
                            <input type="text" class="jeforms-global-setting" data-setting="recaptcha_site_key" value="<?php echo esc_attr($settings['recaptcha_site_key'] ?? ''); ?>" autocomplete="off" placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                            <p class="description">Get keys from <a href="https://www.google.com/recaptcha/admin" target="_blank">Google reCAPTCHA</a></p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Secret Key</label>
                            <input type="password" class="jeforms-global-setting" data-setting="recaptcha_secret_key" value="<?php echo esc_attr($settings['recaptcha_secret_key'] ?? ''); ?>" autocomplete="off" placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                            <p class="description">Keep this key secret</p>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>Default Colors</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <label>Primary Color</label>
                            <input type="text" class="jeforms-global-setting jeforms-color-picker" data-setting="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#667eea'); ?>">
                            <p class="description">Main theme color for buttons and highlights</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Success Color</label>
                            <input type="text" class="jeforms-global-setting jeforms-color-picker" data-setting="success_color" value="<?php echo esc_attr($settings['success_color'] ?? '#48bb78'); ?>">
                            <p class="description">Color for success messages and indicators</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Error Color</label>
                            <input type="text" class="jeforms-global-setting jeforms-color-picker" data-setting="error_color" value="<?php echo esc_attr($settings['error_color'] ?? '#f56565'); ?>">
                            <p class="description">Color for error messages and validation</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Border Color</label>
                            <input type="text" class="jeforms-global-setting jeforms-color-picker" data-setting="border_color" value="<?php echo esc_attr($settings['border_color'] ?? '#e2e8f0'); ?>">
                            <p class="description">Default border color for form elements</p>
                        </div>
                    </div>
                </div>
                
                <div class="jeforms-card">
                    <div class="jeforms-card-header">
                        <h2>Advanced Settings</h2>
                    </div>
                    <div class="jeforms-card-body">
                        <div class="jeforms-setting-group">
                            <div class="jeforms-toggle">
                                <label class="jeforms-toggle-switch">
                                    <input type="checkbox" class="jeforms-global-setting" data-setting="enable_debug" <?php checked($settings['enable_debug'] ?? false); ?>>
                                    <span class="jeforms-toggle-slider"></span>
                                </label>
                                <span>Enable Debug Mode</span>
                            </div>
                            <p class="description">Log form submission errors for debugging</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>Submission Retention (days)</label>
                            <input type="number" class="jeforms-global-setting" data-setting="retention_days" value="<?php echo esc_attr($settings['retention_days'] ?? '365'); ?>" min="1" max="3650" autocomplete="off">
                            <p class="description">How long to keep submissions (1-3650 days)</p>
                        </div>
                        
                        <div class="jeforms-setting-group">
                            <label>AJAX Timeout (seconds)</label>
                            <input type="number" class="jeforms-global-setting" data-setting="ajax_timeout" value="<?php echo esc_attr($settings['ajax_timeout'] ?? '30'); ?>" min="5" max="120" autocomplete="off">
                            <p class="description">Timeout for form submission requests</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Save form
     */
    public function ajax_save_form() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'jeforms_forms';
        
        $this->check_tables_exist();
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : 'Untitled Form';
        $fields = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '[]';
        $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : '{}';
        $styling = isset($_POST['styling']) ? wp_unslash($_POST['styling']) : '{}';
        
        $fields_decoded = json_decode($fields, true);
        $settings_decoded = json_decode($settings, true);
        $styling_decoded = json_decode($styling, true);
        
        if ($fields_decoded === null || $settings_decoded === null || $styling_decoded === null) {
            wp_send_json_error(array('message' => 'Invalid JSON data in form fields'));
        }
        
        $sanitized_fields = $this->sanitize_form_fields($fields_decoded);
        
        $data = array(
            'title' => $title,
            'fields' => wp_json_encode($sanitized_fields),
            'settings' => wp_json_encode($settings_decoded),
            'styling' => wp_json_encode($styling_decoded),
            'status' => 'active',
            'updated_at' => current_time('mysql')
        );
        
        if ($form_id > 0) {
            $result = $wpdb->update($table, $data, array('id' => $form_id));
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($table, $data);
            $form_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            error_log('JE Forms: Database error - ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Database error. Please try again.'));
        }
        
        wp_send_json_success(array(
            'form_id' => $form_id,
            'message' => 'Form saved successfully'
        ));
    }
    
    /**
     * Sanitize form fields
     */
    private function sanitize_form_fields($fields) {
        if (!is_array($fields)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            
            $sanitized_field = array();
            
            $string_fields = array('type', 'id', 'label', 'placeholder', 'default_value', 'css_class', 
                                 'acceptance_text', 'html_content', 'heading_text', 'heading_tag', 
                                 'allowed_types', 'min', 'max', 'step', 'location_accuracy');
            
            foreach ($string_fields as $key) {
                if (isset($field[$key])) {
                    if ($key === 'html_content') {
                        $sanitized_field[$key] = wp_kses_post($field[$key]);
                    } else {
                        $sanitized_field[$key] = sanitize_text_field($field[$key]);
                    }
                }
            }
            
            $numeric_fields = array('width', 'rows', 'max_size', 'spacer_height', 'location_timeout');
            foreach ($numeric_fields as $key) {
                if (isset($field[$key])) {
                    $sanitized_field[$key] = intval($field[$key]);
                }
            }
            
            $boolean_fields = array('required', 'multiple', 'allow_manual_override');
            foreach ($boolean_fields as $key) {
                if (isset($field[$key])) {
                    $sanitized_field[$key] = (bool)$field[$key];
                }
            }
            
            if (isset($field['options']) && is_array($field['options'])) {
                $sanitized_options = array();
                foreach ($field['options'] as $option) {
                    if (is_array($option)) {
                        $sanitized_options[] = array(
                            'label' => isset($option['label']) ? sanitize_text_field($option['label']) : '',
                            'value' => isset($option['value']) ? sanitize_text_field($option['value']) : ''
                        );
                    }
                }
                $sanitized_field['options'] = $sanitized_options;
            }
            
            if (isset($field['special_fields']) && is_array($field['special_fields'])) {
                $sanitized_special_fields = array();
                foreach ($field['special_fields'] as $sf) {
                    if (is_array($sf)) {
                        $sanitized_special_fields[] = array(
                            'name' => isset($sf['name']) ? sanitize_text_field($sf['name']) : '',
                            'type' => isset($sf['type']) ? sanitize_text_field($sf['type']) : 'text',
                            'placeholder' => isset($sf['placeholder']) ? sanitize_text_field($sf['placeholder']) : '',
                            'required' => isset($sf['required']) ? (bool)$sf['required'] : false
                        );
                    }
                }
                $sanitized_field['special_fields'] = $sanitized_special_fields;
            }
            
            $sanitized[] = $sanitized_field;
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX: Get form
     */
    public function ajax_get_form() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'jeforms_forms';
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));
        
        if (!$form) {
            wp_send_json_error(array('message' => 'Form not found'));
        }
        
        wp_send_json_success(array(
            'id' => $form->id,
            'title' => $form->title,
            'fields' => json_decode($form->fields, true) ?: [],
            'settings' => json_decode($form->settings, true) ?: [],
            'styling' => json_decode($form->styling, true) ?: []
        ));
    }
    
    /**
     * AJAX: Delete form
     */
    public function ajax_delete_form() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $wpdb->delete($submissions_table, array('form_id' => $form_id));
        
        $result = $wpdb->delete($forms_table, array('id' => $form_id));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        wp_send_json_success(array('message' => 'Form deleted successfully'));
    }
    
    /**
     * AJAX: Duplicate form
     */
    public function ajax_duplicate_form() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'jeforms_forms';
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));
        
        if (!$form) {
            wp_send_json_error(array('message' => 'Form not found'));
        }
        
        $result = $wpdb->insert($table, array(
            'title' => $form->title . ' (Copy)',
            'fields' => $form->fields,
            'settings' => $form->settings,
            'styling' => $form->styling,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        wp_send_json_success(array('message' => 'Form duplicated successfully'));
    }
    
    /**
     * AJAX: Submit form - FIXED: Now properly handles special select field data in email
     */
    public function ajax_submit_form() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jeforms_submit_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
        }
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d AND status = 'active'", $form_id));
        
        if (!$form) {
            wp_send_json_error(array('message' => 'Form not found or inactive'));
        }
        
        $fields = json_decode($form->fields, true) ?: [];
        $settings = json_decode($form->settings, true) ?: [];
        
        if (!empty($settings['honeypot']) && !empty($_POST['jeforms_website'])) {
            wp_send_json_error(array('message' => 'Spam detected'));
        }
        
        $form_data = array();
        $signatures = array();
        $files = array();
        $special_fields_data = array();
        
        foreach ($fields as $field) {
            $field_name = 'field_' . $field['id'];
            $field_label = $field['label'];
            
            if (!isset($_POST[$field_name]) && $field['type'] !== 'signature' && $field['type'] !== 'file' && $field['type'] !== 'special_select') {
                continue;
            }
            
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'tel':
                case 'url':
                case 'password':
                case 'number':
                case 'textarea':
                case 'date':
                case 'time':
                case 'datetime':
                    $value = sanitize_text_field($_POST[$field_name] ?? '');
                    if ($field['required'] && empty($value)) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    $form_data[$field_label] = $value;
                    break;
                    
                case 'select':
                    $value = sanitize_text_field($_POST[$field_name] ?? '');
                    if ($field['required'] && empty($value)) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    $form_data[$field_label] = $value;
                    break;
                    
                case 'radio':
                    $value = sanitize_text_field($_POST[$field_name] ?? '');
                    if ($field['required'] && empty($value)) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    $form_data[$field_label] = $value;
                    break;
                    
                case 'checkbox':
                    if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
                        $values = array_map('sanitize_text_field', $_POST[$field_name]);
                        if ($field['required'] && empty($values)) {
                            wp_send_json_error(array('message' => $field_label . ' is required'));
                        }
                        $form_data[$field_label] = implode(', ', $values);
                    } elseif ($field['required']) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    break;
                    
                case 'acceptance':
                    $accepted = isset($_POST[$field_name]) && $_POST[$field_name] === '1';
                    if ($field['required'] && !$accepted) {
                        wp_send_json_error(array('message' => 'You must accept the terms'));
                    }
                    $form_data[$field_label] = $accepted ? 'Accepted' : 'Not Accepted';
                    break;
                    
                case 'signature':
                    $signature_data = isset($_POST[$field_name]) ? sanitize_text_field($_POST[$field_name]) : '';
                    
                    if ($field['required'] && empty($signature_data)) {
                        wp_send_json_error(array('message' => $field_label . ' signature is required'));
                    }
                    
                    if ($signature_data) {
                        $signature_url = $this->save_signature_to_media($signature_data, $field_label);
                        if ($signature_url) {
                            $form_data[$field_label] = '<img src="' . esc_url($signature_url) . '" alt="' . esc_attr($field_label) . '" style="max-width: 300px; height: auto;">';
                            $signatures[$field_label] = $signature_url;
                        } else {
                            $form_data[$field_label] = '[Signature could not be saved]';
                        }
                    } else {
                        $form_data[$field_label] = '';
                    }
                    break;
                    
                case 'file':
                    if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                        $upload = $this->handle_file_upload($_FILES[$field_name], $field);
                        if ($upload && !isset($upload['error'])) {
                            $form_data[$field_label] = '<a href="' . esc_url($upload['url']) . '" target="_blank">' . esc_html(basename($upload['url'])) . '</a>';
                            $files[$field_label] = $upload['url'];
                        } elseif ($field['required']) {
                            wp_send_json_error(array('message' => $field_label . ' is required'));
                        }
                    } elseif ($field['required']) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    break;
                    
                case 'special_select':
                    $base_name = $field_name;
                    $selected_index = isset($_POST[$base_name . '_selected_index']) ? intval($_POST[$base_name . '_selected_index']) : -1;
                    
                    if ($field['required'] && $selected_index === -1) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    
                    if ($selected_index >= 0) {
                        $selected_type = sanitize_text_field($_POST[$base_name . '_type'] ?? '');
                        $selected_name = sanitize_text_field($_POST[$base_name . '_name'] ?? '');
                        $selected_value = sanitize_text_field($_POST[$base_name . '_value'] ?? '');
                        
                        if ($selected_type === 'signature' && !empty($selected_value)) {
                            $signature_url = $this->save_signature_to_media($selected_value, $selected_name);
                            if ($signature_url) {
                                $selected_value = '<img src="' . esc_url($signature_url) . '" alt="' . esc_attr($selected_name) . '" style="max-width: 300px; height: auto;">';
                                $signatures[$field_label . ' - ' . $selected_name] = $signature_url;
                            } else {
                                $selected_value = '[Signature could not be saved]';
                            }
                        } elseif ($selected_type === 'textarea') {
                            $selected_value = sanitize_textarea_field($selected_value);
                            $selected_value = nl2br(esc_html($selected_value));
                        }
                        
                        $form_data[$field_label] = '<strong>' . $selected_name . ' (' . $selected_type . '):</strong><br>' . $selected_value;
                        $special_fields_data[$field_label] = array(
                            'name' => $selected_name,
                            'type' => $selected_type,
                            'value' => $selected_value
                        );
                    } else {
                        $form_data[$field_label] = '';
                    }
                    break;
                    
                case 'auto_address':
                    $value = sanitize_text_field($_POST[$field_name] ?? '');
                    if ($field['required'] && empty($value)) {
                        wp_send_json_error(array('message' => $field_label . ' is required'));
                    }
                    $form_data[$field_label] = $value ?: 'Location not detected';
                    break;
                    
                case 'hidden':
                    $value = sanitize_text_field($_POST[$field_name] ?? '');
                    $form_data[$field_label] = $value;
                    break;
            }
        }
        
        if (!empty($settings['store_submissions'])) {
            $wpdb->insert($submissions_table, array(
                'form_id' => $form_id,
                'data' => wp_json_encode($form_data),
                'files' => wp_json_encode($files),
                'signatures' => wp_json_encode($signatures),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'user_id' => get_current_user_id(),
                'status' => 'unread',
                'created_at' => current_time('mysql')
            ));
        }
        
        $email_sent = $this->send_submission_email($form, $form_data, $signatures, $files, $special_fields_data);
        
        if (!$email_sent) {
            error_log('JE Forms: Failed to send email notification for form ID: ' . $form_id);
        }
        
        $response = array(
            'message' => $settings['success_message'] ?? 'Thank you! Your submission has been received.'
        );
        
        if (!empty($settings['redirect_url'])) {
            $response['redirect'] = esc_url($settings['redirect_url']);
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Save signature to media library as JPG
     */
    private function save_signature_to_media($base64_data, $label = 'Signature') {
        if (empty($base64_data) || strpos($base64_data, 'data:image/png;base64,') !== 0) {
            return false;
        }
        
        $base64_data = str_replace('data:image/png;base64,', '', $base64_data);
        $image_data = base64_decode($base64_data);
        
        if (!$image_data) {
            return false;
        }
        
        $image = imagecreatefromstring($image_data);
        if (!$image) {
            return false;
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        $jpg_image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($jpg_image, 255, 255, 255);
        imagefill($jpg_image, 0, 0, $white);
        
        imagecopy($jpg_image, $image, 0, 0, 0, 0, $width, $height);
        
        $temp_file = wp_tempnam('signature');
        $temp_file_jpg = $temp_file . '.jpg';
        imagejpeg($jpg_image, $temp_file_jpg, 90);
        
        imagedestroy($image);
        imagedestroy($jpg_image);
        
        $file_array = array(
            'name' => sanitize_file_name($label . '_' . time() . '.jpg'),
            'tmp_name' => $temp_file_jpg,
            'error' => 0,
            'size' => filesize($temp_file_jpg)
        );
        
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $attachment_id = media_handle_sideload($file_array, 0, 'Signature: ' . $label);
        
        @unlink($temp_file);
        @unlink($temp_file_jpg);
        
        if (is_wp_error($attachment_id)) {
            error_log('JE Forms: Signature upload error: ' . $attachment_id->get_error_message());
            return false;
        }
        
        $attachment_url = wp_get_attachment_url($attachment_id);
        
        return $attachment_url;
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload($file, $field) {
        $allowed_types = explode(',', $field['allowed_types'] ?? 'jpg,jpeg,png,pdf');
        $allowed_types = array_map('trim', $allowed_types);
        $allowed_types = array_map('strtolower', $allowed_types);
        
        $max_size = ($field['max_size'] ?? 5) * 1024 * 1024;
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed_types)) {
                return new WP_Error('invalid_type', 'File type not allowed');
            }
            
            if ($file['size'] > $max_size) {
                return new WP_Error('size_exceeded', 'File size exceeds limit');
            }
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            if ($upload && !isset($upload['error'])) {
                return $upload;
            }
        }
        
        return new WP_Error('upload_failed', 'File upload failed');
    }
    
    /**
     * Send submission email - FIXED: Now properly displays special select field data
     */
    private function send_submission_email($form, $form_data, $signatures, $files, $special_fields_data = array()) {
        $settings = json_decode($form->settings, true) ?: [];
        
        $to = $settings['email_to'] ?? get_option('admin_email');
        $subject = $settings['email_subject'] ?? 'New Form Submission: ' . $form->title;
        $from_name = $settings['email_from_name'] ?? get_bloginfo('name');
        $from_email = $settings['email_from_email'] ?? get_option('admin_email');
        
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>';
        $html .= 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 700px; margin: 0 auto; background: #f7fafc; }';
        $html .= '.container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin: 20px; }';
        $html .= '.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }';
        $html .= '.header h1 { margin: 0; font-size: 24px; font-weight: 600; }';
        $html .= '.header p { margin: 10px 0 0; opacity: 0.9; font-size: 14px; }';
        $html .= '.content { padding: 30px; }';
        $html .= '.submission-info { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #667eea; }';
        $html .= '.submission-info p { margin: 5px 0; font-size: 14px; color: #666; }';
        $html .= '.field-group { margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #e2e8f0; }';
        $html .= '.field-group:last-child { border-bottom: none; }';
        $html .= '.field-label { font-size: 13px; font-weight: 600; color: #4a5568; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }';
        $html .= '.field-value { font-size: 15px; color: #2d3748; word-break: break-word; line-height: 1.6; }';
        $html .= '.field-value img { max-width: 300px; height: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: white; margin-top: 10px; }';
        $html .= '.field-value a { color: #667eea; text-decoration: none; }';
        $html .= '.field-value a:hover { text-decoration: underline; }';
        $html .= '.signature-img { max-width: 300px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; background: white; margin-top: 10px; }';
        $html .= '.file-link { display: inline-block; background: #edf2f7; padding: 8px 15px; border-radius: 6px; color: #4a5568; text-decoration: none; margin: 5px 5px 5px 0; font-size: 13px; border: 1px solid #e2e8f0; }';
        $html .= '.file-link:hover { background: #e2e8f0; }';
        $html .= '.footer { text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #e2e8f0; font-size: 12px; color: #718096; }';
        $html .= '</style></head><body>';
        
        $html .= '<div class="container">';
        $html .= '<div class="header">';
        $html .= '<h1>New Form Submission</h1>';
        $html .= '<p>' . esc_html($form->title) . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="content">';
        
        $html .= '<div class="submission-info">';
        $html .= '<p><strong>Submitted:</strong> ' . date('F j, Y \a\t g:i A') . '</p>';
        $html .= '<p><strong>IP Address:</strong> ' . $this->get_client_ip() . '</p>';
        if (get_current_user_id()) {
            $user = get_userdata(get_current_user_id());
            $html .= '<p><strong>User:</strong> ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</p>';
        }
        $html .= '</div>';
        
        foreach ($form_data as $label => $value) {
            $html .= '<div class="field-group">';
            $html .= '<div class="field-label">' . esc_html($label) . '</div>';
            $html .= '<div class="field-value">';
            
            if (strpos($value, '<img') !== false || strpos($value, '<a') !== false || strpos($value, '<strong>') !== false) {
                $html .= $value;
            } else {
                $html .= nl2br(esc_html($value));
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '<div class="footer">';
        $html .= '<p>This email was sent from ' . esc_html(get_bloginfo('name')) . ' | ' . esc_html(get_site_url()) . '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</body></html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0'
        );
        
        return wp_mail($to, $subject, $html, $headers);
    }
    
    /**
     * AJAX: Delete submission
     */
    public function ajax_delete_submission() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'jeforms_submissions';
        
        $submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
        $result = $wpdb->delete($table, array('id' => $submission_id));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Database error: ' . $wpdb->last_error));
        }
        
        wp_send_json_success(array('message' => 'Submission deleted successfully'));
    }
    
    /**
     * AJAX: Export submissions
     */
    public function ajax_export_submissions() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        global $wpdb;
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        
        $where = $form_id ? $wpdb->prepare("WHERE form_id = %d", $form_id) : '';
        $submissions = $wpdb->get_results("SELECT s.*, f.title as form_title FROM $submissions_table s LEFT JOIN $forms_table f ON s.form_id = f.id $where ORDER BY s.created_at DESC");
        
        $filename = 'jeforms-submissions-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        $all_fields = array('ID', 'Form', 'Date', 'IP Address', 'User ID', 'Status');
        $field_map = array();
        
        foreach ($submissions as $submission) {
            $data = json_decode($submission->data, true);
            if (is_array($data)) {
                foreach (array_keys($data) as $field) {
                    if (!in_array($field, $field_map)) {
                        $field_map[] = $field;
                    }
                }
            }
        }
        
        $all_fields = array_merge($all_fields, $field_map);
        fputcsv($output, $all_fields);
        
        foreach ($submissions as $submission) {
            $data = json_decode($submission->data, true);
            $row = array(
                $submission->id,
                $submission->form_title,
                $submission->created_at,
                $submission->ip_address,
                $submission->user_id ?: 'Guest',
                $submission->status
            );
            
            foreach ($field_map as $field) {
                $value = isset($data[$field]) ? $data[$field] : '';
                $value = strip_tags($value);
                $value = substr($value, 0, 500);
                $row[] = $value;
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('jeforms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $settings = isset($_POST['settings']) ? json_decode(stripslashes($_POST['settings']), true) : array();
        
        if (!is_array($settings)) {
            wp_send_json_error(array('message' => 'Invalid settings data'));
        }
        
        $sanitized = array();
        foreach ($settings as $key => $value) {
            if (is_bool($value) || $value === 'true' || $value === 'false') {
                $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = floatval($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = sanitize_text_field($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        update_option('jeforms_settings', $sanitized, 'no');
        
        wp_send_json_success(array('message' => 'Settings saved successfully'));
    }
    
    /**
     * Render form shortcode - FIXED: Now includes auto-location address field
     */
    public function render_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        $form_id = intval($atts['id']);
        
        if (!$form_id) {
            return '<div class="jeforms-form-wrapper" style="padding:20px;background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;color:#c53030;"><p><strong>Error:</strong> Form ID is required. Example: [jeform id="1"]</p></div>';
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'jeforms_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", $form_id));
        
        if (!$form) {
            return '<div class="jeforms-form-wrapper" style="padding:20px;background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;color:#c53030;"><p><strong>Error:</strong> Form not found or is inactive.</p></div>';
        }
        
        $fields = json_decode($form->fields, true) ?: [];
        $settings = json_decode($form->settings, true) ?: [];
        $styling = json_decode($form->styling, true) ?: [];
        
        $form_style = $this->generate_form_styles($styling);
        
        ob_start();
        ?>
        <div class="jeforms-form-wrapper" id="jeforms-wrapper-<?php echo esc_attr($form_id); ?>">
            <style><?php echo $form_style; ?></style>
            
            <form class="jeforms-form <?php echo esc_attr($settings['form_class'] ?? ''); ?>" data-form-id="<?php echo esc_attr($form_id); ?>" method="post" enctype="multipart/form-data" novalidate>
                <div class="jeforms-form-row">
                    <?php foreach ($fields as $field) : ?>
                        <?php echo $this->render_form_field($field, $form_id); ?>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($settings['honeypot'])) : ?>
                    <div class="jeforms-honeypot">
                        <input type="text" name="jeforms_website" value="" tabindex="-1" autocomplete="off">
                    </div>
                <?php endif; ?>
                
                <div class="jeforms-submit-wrapper align-<?php echo esc_attr($styling['button_alignment'] ?? 'left'); ?>">
                    <button type="submit" class="jeforms-submit-btn <?php echo ($styling['button_width'] ?? 'auto') === 'full' ? 'full-width' : ''; ?>">
                        <?php echo esc_html($settings['submit_button_text'] ?? 'Submit'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate form styles
     */
    private function generate_form_styles($styling) {
        if (!$styling) {
            return '';
        }
        
        $css = '.jeforms-form {';
        $css .= 'background-color: ' . ($styling['form_bg_color'] ?? '#ffffff') . ';';
        $css .= 'padding: ' . ($styling['form_padding'] ?? '40') . 'px;';
        $css .= 'border-radius: ' . ($styling['form_border_radius'] ?? '15') . 'px;';
        $css .= '}';
        
        $css .= '.jeforms-form:before {';
        $css .= 'background: linear-gradient(135deg, ' . ($styling['button_bg_color'] ?? '#667eea') . ' 0%, ' . ($styling['button_hover_color'] ?? '#764ba2') . ' 100%);';
        $css .= '}';
        
        $css .= '.jeforms-form label {';
        $css .= 'color: ' . ($styling['label_color'] ?? '#2d3748') . ';';
        $css .= 'font-size: ' . ($styling['label_font_size'] ?? '15') . 'px;';
        $css .= '}';
        
        $css .= '.jeforms-form input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="submit"]),';
        $css .= '.jeforms-form textarea,';
        $css .= '.jeforms-form select {';
        $css .= 'background-color: ' . ($styling['input_bg_color'] ?? '#ffffff') . ';';
        $css .= 'border-color: ' . ($styling['input_border_color'] ?? '#e2e8f0') . ';';
        $css .= 'color: ' . ($styling['input_text_color'] ?? '#2d3748') . ';';
        $css .= 'padding: ' . ($styling['input_padding'] ?? '14') . 'px;';
        $css .= 'border-radius: ' . ($styling['input_border_radius'] ?? '8') . 'px;';
        $css .= '}';
        
        $css .= '.jeforms-form input:focus,';
        $css .= '.jeforms-form textarea:focus,';
        $css .= '.jeforms-form select:focus {';
        $css .= 'border-color: ' . ($styling['input_focus_color'] ?? '#667eea') . ';';
        $css .= 'box-shadow: 0 0 0 4px ' . $this->hex_to_rgba($styling['input_focus_color'] ?? '#667eea', 0.1) . ';';
        $css .= '}';
        
        $css .= '.jeforms-submit-btn {';
        $css .= 'background: linear-gradient(135deg, ' . ($styling['button_bg_color'] ?? '#667eea') . ' 0%, ' . ($styling['button_hover_color'] ?? '#764ba2') . ' 100%);';
        $css .= 'color: ' . ($styling['button_text_color'] ?? '#ffffff') . ';';
        $css .= 'padding: ' . ($styling['button_padding'] ?? '16') . 'px 40px;';
        $css .= 'border-radius: ' . ($styling['button_border_radius'] ?? '10') . 'px;';
        $css .= '}';
        
        $css .= '.jeforms-submit-btn:hover {';
        $css .= 'transform: translateY(-4px);';
        $css .= 'box-shadow: 0 8px 25px rgba(' . $this->hex_to_rgb($styling['button_bg_color'] ?? '#667eea') . ', 0.4);';
        $css .= '}';
        
        return $css;
    }
    
    /**
     * Convert hex to rgba
     */
    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "rgba($r, $g, $b, $alpha)";
    }
    
    /**
     * Convert hex to rgb
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return "$r, $g, $b";
    }
    
    /**
     * Render form field - FIXED: Now includes auto-location address field
     */
    private function render_form_field($field, $form_id) {
        if (!is_array($field)) {
            return '';
        }
        
        $field_id = 'jeforms_' . $form_id . '_' . ($field['id'] ?? 'field_' . uniqid());
        $field_name = 'field_' . ($field['id'] ?? uniqid());
        $width_class = 'jeforms-width-' . ($field['width'] ?? '100');
        $required = !empty($field['required']);
        $required_attr = $required ? 'required' : '';
        $required_star = $required ? ' <span class="jeforms-required">*</span>' : '';
        $css_class = $field['css_class'] ?? '';
        $field_type = $field['type'] ?? 'text';
        
        $html = '<div class="jeforms-form-field ' . esc_attr($width_class) . ' ' . esc_attr($css_class) . ' jeforms-field-type-' . esc_attr($field_type) . '" data-type="' . esc_attr($field_type) . '" data-required="' . ($required ? '1' : '0') . '">';
        
        switch ($field_type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'url':
            case 'password':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Text Field') . $required_star . '</label>';
                $html .= '<input type="' . esc_attr($field_type) . '" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" value="' . esc_attr($field['default_value'] ?? '') . '" ' . $required_attr . '>';
                break;
                
            case 'number':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Number Field') . $required_star . '</label>';
                $html .= '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" value="' . esc_attr($field['default_value'] ?? '') . '" min="' . esc_attr($field['min'] ?? '') . '" max="' . esc_attr($field['max'] ?? '') . '" step="' . esc_attr($field['step'] ?? '') . '" ' . $required_attr . '>';
                break;
                
            case 'textarea':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Textarea') . $required_star . '</label>';
                $html .= '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" placeholder="' . esc_attr($field['placeholder'] ?? '') . '" rows="' . esc_attr($field['rows'] ?? 4) . '" ' . $required_attr . '>' . esc_textarea($field['default_value'] ?? '') . '</textarea>';
                break;
                
            case 'date':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Date') . $required_star . '</label>';
                $html .= '<input type="date" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field['default_value'] ?? '') . '" ' . $required_attr . '>';
                break;
                
            case 'time':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Time') . $required_star . '</label>';
                $html .= '<input type="time" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field['default_value'] ?? '') . '" ' . $required_attr . '>';
                break;
                
            case 'datetime':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Date & Time') . $required_star . '</label>';
                $html .= '<input type="datetime-local" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field['default_value'] ?? '') . '" ' . $required_attr . '>';
                break;
                
            case 'select':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Select') . $required_star . '</label>';
                $multiple = !empty($field['multiple']) ? 'multiple' : '';
                $html .= '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . ($multiple ? '[]' : '') . '" ' . $multiple . ' ' . $required_attr . '>';
                $html .= '<option value="">Select an option</option>';
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $option_value = $option['value'] ?? '';
                        $option_label = $option['label'] ?? '';
                        $selected = ($option_value === ($field['default_value'] ?? '')) ? ' selected' : '';
                        $html .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
                
            case 'radio':
                $html .= '<label>' . esc_html($field['label'] ?? 'Radio Options') . $required_star . '</label>';
                $html .= '<div class="jeforms-radio-group">';
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $i => $option) {
                        $option_id = $field_id . '_' . $i;
                        $option_value = $option['value'] ?? '';
                        $option_label = $option['label'] ?? '';
                        $checked = ($option_value === ($field['default_value'] ?? '')) ? ' checked' : '';
                        $html .= '<div class="jeforms-radio-item">';
                        $html .= '<input type="radio" id="' . esc_attr($option_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($option_value) . '"' . $checked . ($required && $i === 0 ? ' ' . $required_attr : '') . '>';
                        $html .= '<label for="' . esc_attr($option_id) . '">' . esc_html($option_label) . '</label>';
                        $html .= '</div>';
                    }
                }
                $html .= '</div>';
                break;
                
            case 'checkbox':
                $html .= '<label>' . esc_html($field['label'] ?? 'Checkbox Options') . $required_star . '</label>';
                $html .= '<div class="jeforms-checkbox-group">';
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $i => $option) {
                        $option_id = $field_id . '_' . $i;
                        $option_value = $option['value'] ?? '';
                        $option_label = $option['label'] ?? '';
                        $html .= '<div class="jeforms-checkbox-item">';
                        $html .= '<input type="checkbox" id="' . esc_attr($option_id) . '" name="' . esc_attr($field_name) . '[]" value="' . esc_attr($option_value) . '">';
                        $html .= '<label for="' . esc_attr($option_id) . '">' . esc_html($option_label) . '</label>';
                        $html .= '</div>';
                    }
                }
                $html .= '</div>';
                break;
                
            case 'acceptance':
                $html .= '<div class="jeforms-acceptance">';
                $html .= '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="1" ' . $required_attr . '>';
                $html .= '<label for="' . esc_attr($field_id) . '">' . wp_kses_post($field['acceptance_text'] ?? 'I accept the terms and conditions') . $required_star . '</label>';
                $html .= '</div>';
                break;
                
            case 'file':
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'File Upload') . $required_star . '</label>';
                $html .= '<div class="jeforms-file-upload">';
                $multiple = !empty($field['multiple']) ? 'multiple' : '';
                $accept = '.' . str_replace(',', ',.', $field['allowed_types'] ?? 'jpg,jpeg,png,pdf');
                $html .= '<input type="file" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '[]" class="jeforms-file-input" ' . $multiple . ' accept="' . esc_attr($accept) . '" ' . $required_attr . '>';
                $html .= '<label for="' . esc_attr($field_id) . '" class="jeforms-file-label">';
                $html .= '<span class="dashicons dashicons-upload"></span>';
                $html .= '<span>Click to upload or drag files here</span>';
                $html .= '<div style="font-size:12px;color:#a0aec0;margin-top:8px;">Max size: ' . esc_html($field['max_size'] ?? 5) . 'MB • Allowed: ' . esc_html($field['allowed_types'] ?? 'jpg,jpeg,png,pdf') . '</div>';
                $html .= '</label>';
                $html .= '<div class="jeforms-file-list"></div>';
                $html .= '</div>';
                break;
                
            case 'hidden':
                $html .= '<input type="hidden" name="' . esc_attr($field_name) . '" value="' . esc_attr($field['default_value'] ?? '') . '">';
                break;
                
            case 'html':
                $html .= '<div class="jeforms-html-content">' . wp_kses_post($field['html_content'] ?? '') . '</div>';
                break;
                
            case 'signature':
                $html .= '<label>' . esc_html($field['label'] ?? 'Signature') . $required_star . '</label>';
                $html .= '<div class="jeforms-signature-wrapper">';
                $html .= '<canvas class="jeforms-signature-canvas" data-field-id="' . esc_attr($field_id) . '" data-field-name="' . esc_attr($field_name) . '" width="400" height="200"></canvas>';
                $html .= '<div class="jeforms-signature-actions">';
                $html .= '<div class="jeforms-signature-hint">Sign in the box above</div>';
                $html .= '<button type="button" class="jeforms-signature-clear" data-field-id="' . esc_attr($field_id) . '">Clear Signature</button>';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'special_select':
                $html .= '<label>' . esc_html($field['label'] ?? 'Special Select') . $required_star . '</label>';
                $html .= '<div class="jeforms-special-select-wrapper">';
                $html .= '<select class="jeforms-special-select" name="' . esc_attr($field_name) . '_selector" ' . $required_attr . '>';
                $html .= '<option value="">Select an option</option>';
                
                if (!empty($field['special_fields']) && is_array($field['special_fields'])) {
                    foreach ($field['special_fields'] as $i => $sf) {
                        $sf_name = $sf['name'] ?? 'Field ' . ($i + 1);
                        $html .= '<option value="' . esc_attr($i) . '">' . esc_html($sf_name) . '</option>';
                    }
                }
                
                $html .= '</select>';
                $html .= '<div class="jeforms-dynamic-field" style="display:none;">';
                
                if (!empty($field['special_fields']) && is_array($field['special_fields'])) {
                    foreach ($field['special_fields'] as $i => $sf) {
                        $sf_required = !empty($sf['required']) ? '1' : '0';
                        $sf_required_attr = !empty($sf['required']) ? 'required' : '';
                        $sf_type = $sf['type'] ?? 'text';
                        $sf_name = $sf['name'] ?? 'Field ' . ($i + 1);
                        $sf_placeholder = $sf['placeholder'] ?? '';
                        
                        $html .= '<div class="jeforms-special-field-content" data-index="' . esc_attr($i) . '" data-type="' . esc_attr($sf_type) . '" data-name="' . esc_attr($sf_name) . '" data-required="' . $sf_required . '" style="display:none;">';
                        
                        if ($sf_type === 'signature') {
                            $sf_field_id = $field_id . '_special_' . $i;
                            $html .= '<div class="jeforms-signature-wrapper">';
                            $html .= '<canvas class="jeforms-signature-canvas" data-field-id="' . esc_attr($sf_field_id) . '" data-field-name="' . esc_attr($field_name) . '_special_' . $i . '" width="400" height="150"></canvas>';
                            $html .= '<div class="jeforms-signature-actions">';
                            $html .= '<div class="jeforms-signature-hint">Sign in the box above</div>';
                            $html .= '<button type="button" class="jeforms-signature-clear" data-field-id="' . esc_attr($sf_field_id) . '">Clear Signature</button>';
                            $html .= '</div>';
                            $html .= '</div>';
                        } elseif ($sf_type === 'textarea') {
                            $html .= '<textarea name="' . esc_attr($field_name) . '_special_' . $i . '" placeholder="' . esc_attr($sf_placeholder) . '" ' . $sf_required_attr . '></textarea>';
                        } elseif ($sf_type === 'date') {
                            $html .= '<input type="date" name="' . esc_attr($field_name) . '_special_' . $i . '" ' . $sf_required_attr . '>';
                        } elseif ($sf_type === 'time') {
                            $html .= '<input type="time" name="' . esc_attr($field_name) . '_special_' . $i . '" ' . $sf_required_attr . '>';
                        } elseif ($sf_type === 'datetime') {
                            $html .= '<input type="datetime-local" name="' . esc_attr($field_name) . '_special_' . $i . '" ' . $sf_required_attr . '>';
                        } elseif ($sf_type === 'number') {
                            $html .= '<input type="number" name="' . esc_attr($field_name) . '_special_' . $i . '" placeholder="' . esc_attr($sf_placeholder) . '" ' . $sf_required_attr . '>';
                        } else {
                            $input_type = in_array($sf_type, array('email', 'tel', 'url')) ? $sf_type : 'text';
                            $html .= '<input type="' . esc_attr($input_type) . '" name="' . esc_attr($field_name) . '_special_' . $i . '" placeholder="' . esc_attr($sf_placeholder) . '" ' . $sf_required_attr . '>';
                        }
                        
                        $html .= '</div>';
                    }
                }
                
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'auto_address':
                $allow_manual = !empty($field['allow_manual_override']);
                $readonly = $allow_manual ? '' : 'readonly';
                $accuracy = $field['location_accuracy'] ?? 'high';
                $timeout = $field['location_timeout'] ?? 10000;
                
                $html .= '<label for="' . esc_attr($field_id) . '">' . esc_html($field['label'] ?? 'Auto-Location Address') . $required_star . '</label>';
                $html .= '<div class="jeforms-auto-address-field" data-accuracy="' . esc_attr($accuracy) . '" data-timeout="' . esc_attr($timeout) . '" data-readonly="' . ($allow_manual ? 'false' : 'true') . '">';
                $html .= '<div style="position:relative;">';
                $html .= '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($field['default_value'] ?? 'Detecting your location...') . '" ' . $readonly . ' ' . $required_attr . ' style="padding-right:40px;' . ($allow_manual ? '' : 'background:#f7fafc;color:#666;cursor:not-allowed;') . '">';
                $html .= '<span class="dashicons dashicons-location" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#666;"></span>';
                $html .= '</div>';
                $html .= '<div class="jeforms-location-status" style="margin-top:8px;font-size:11px;color:#666;"></div>';
                if (!$allow_manual) {
                    $html .= '<div style="margin-top:8px;font-size:11px;color:#666;display:flex;align-items:center;gap:5px;">';
                    $html .= '<span class="dashicons dashicons-info" style="font-size:12px;"></span>';
                    $html .= 'Address will be auto-filled using your device location';
                    $html .= '</div>';
                }
                $html .= '</div>';
                break;
                
            case 'heading':
                $tag = $field['heading_tag'] ?? 'h3';
                $html .= '<' . $tag . ' class="jeforms-heading">' . esc_html($field['heading_text'] ?? 'Section Heading') . '</' . $tag . '>';
                break;
                
            case 'divider':
                $html .= '<hr class="jeforms-divider">';
                break;
                
            case 'spacer':
                $html .= '<div class="jeforms-spacer" style="height:' . esc_attr($field['spacer_height'] ?? 20) . 'px;"></div>';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Handle form preview
     */
    public function handle_form_preview() {
        if (isset($_GET['jeforms_preview']) && current_user_can('manage_options')) {
            $form_id = intval($_GET['jeforms_preview']);
            
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Form Preview - JE Forms</title>
                <?php wp_head(); ?>
            </head>
            <body style="background:linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);padding:40px 20px;min-height:100vh;">
                <div style="max-width:900px;margin:0 auto;">
                    <div style="background:white;padding:25px;margin-bottom:25px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">
                        <h2 style="margin:0;color:#2d3748;display:flex;align-items:center;gap:10px;">
                            <span class="dashicons dashicons-visibility" style="color:#667eea;"></span>
                            Form Preview
                        </h2>
                        <p style="margin:10px 0 0;color:#718096;font-size:14px;">This is a preview of how the form will appear on your site. Test the form functionality.</p>
                    </div>
                    <?php echo do_shortcode('[jeform id="' . $form_id . '"]'); ?>
                    <div style="text-align:center;margin-top:30px;">
                        <a href="javascript:window.close();" style="display:inline-block;padding:12px 30px;background:#667eea;color:white;text-decoration:none;border-radius:8px;font-weight:500;transition:all 0.3s ease;">
                            Close Preview
                        </a>
                    </div>
                </div>
                <?php wp_footer(); ?>
            </body>
            </html>
            <?php
            exit;
        }
    }
    
    /**
     * Check if tables exist and create if not
     */
    private function check_tables_exist() {
        global $wpdb;
        
        $forms_table = $wpdb->prefix . 'jeforms_forms';
        $submissions_table = $wpdb->prefix . 'jeforms_submissions';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$forms_table'") != $forms_table) {
            $this->activate();
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$submissions_table'") != $submissions_table) {
            $this->activate();
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    JE_Forms_Pro::get_instance();
});