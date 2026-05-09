namespace OmniContentAI\Admin;

use OmniContentAI\Core\Logger;

class Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Ação para limpar logs
        if (isset($_GET['ocai_clear_logs']) && check_admin_referer('ocai_clear_logs_action')) {
            Logger::clear_all();
            wp_redirect(admin_url('admin.php?page=ocai-settings&tab=logs'));
            exit;
        }
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ocai-') === false) return;

        wp_enqueue_style('ocai-admin-style', OCAI_URL . 'assets/css/admin.css', [], OCAI_VERSION);
        wp_enqueue_script('ocai-admin-script', OCAI_URL . 'assets/js/admin.js', ['jquery'], OCAI_VERSION, true);

        wp_localize_script('ocai-admin-script', 'ocaiData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ocai_queue_nonce'),
        ]);
    }

    public function add_menu() {
        add_menu_page('OmniContent AI', 'OmniContent AI', 'manage_options', 'ocai-main', '', 'dashicons-superhero', 80);
        add_submenu_page('ocai-main', 'Fila de Produção', 'Fila de Produção 🛰️', 'manage_options', 'ocai-main', [new \OmniContentAI\Admin\Keywords(), 'render_page']);
        add_submenu_page('ocai-main', 'Configurações', 'Configurações ⚙️', 'manage_options', 'ocai-settings', [$this, 'render_page']);
    }

    public function register_settings() {
        register_setting('ocai_settings_group', 'ocai_api_openai', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('ocai_settings_group', 'ocai_api_gemini', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('ocai_settings_group', 'ocai_posts_per_day', ['sanitize_callback' => 'intval']);
        register_setting('ocai_settings_group', 'ocai_post_status', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('ocai_settings_group', 'ocai_target_language', ['sanitize_callback' => 'sanitize_text_field']);
    }

    public function render_page() {
        $posts_per_day = (int) get_option('ocai_posts_per_day', 1);
        $target_lang = get_option('ocai_target_language', 'pt_BR');
        $active_tab = $_GET['tab'] ?? 'api';
        $logs = Logger::get_logs(50);
        ?>
        <div class="wrap gan-wrap">
            <div class="gan-card" style="background:#fff; padding:40px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                <div class="gan-header" style="margin-bottom:30px;">
                    <h1 style="font-weight:800;">🚀 OmniContent AI <span style="font-size:12px; background:#eee; padding:2px 8px; border-radius:10px;"><?php echo OCAI_VERSION; ?></span></h1>
                    <p style="color:#666;">Configurações Globais e Inteligência GEO/SEO.</p>
                </div>

                <div class="gan-tabs" style="display:flex; gap:10px; margin-bottom:30px; border-bottom:1px solid #eee; padding-bottom:10px;">
                    <a href="?page=ocai-settings&tab=api" class="gan-tab <?php echo $active_tab === 'api' ? 'active' : ''; ?>" style="text-decoration:none; padding:10px 20px; font-weight:700; color:<?php echo $active_tab === 'api' ? '#ec5b13' : '#666'; ?>;">🔑 APIs</a>
                    <a href="?page=ocai-settings&tab=automation" class="gan-tab <?php echo $active_tab === 'automation' ? 'active' : ''; ?>" style="text-decoration:none; padding:10px 20px; font-weight:700; color:<?php echo $active_tab === 'automation' ? '#ec5b13' : '#666'; ?>;">🤖 Automação</a>
                    <a href="?page=ocai-settings&tab=logs" class="gan-tab <?php echo $active_tab === 'logs' ? 'active' : ''; ?>" style="text-decoration:none; padding:10px 20px; font-weight:700; color:<?php echo $active_tab === 'logs' ? '#ec5b13' : '#666'; ?>;">📋 Logs</a>
                </div>

                <?php if ($active_tab === 'logs') : ?>
                    <div id="tab-logs">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <h2 style="margin:0;">Auditoria de Erros</h2>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ocai-settings&ocai_clear_logs=1'), 'ocai_clear_logs_action'); ?>" class="button button-link-delete" style="color:#d63638;">Limpar Logs</a>
                        </div>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width:150px;">Data/Hora</th>
                                    <th style="width:100px;">Contexto</th>
                                    <th>Mensagem de Erro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)) : ?>
                                    <tr><td colspan="3">Nenhum erro registrado. Tudo operando normalmente! 🏛️✨</td></tr>
                                <?php else : ?>
                                    <?php foreach ($logs as $log) : ?>
                                        <tr>
                                            <td style="font-size:11px;"><?php echo esc_html($log->created_at); ?></td>
                                            <td><span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700;"><?php echo esc_html($log->context); ?></span></td>
                                            <td style="color:#d63638; font-family:monospace; font-size:12px;"><?php echo esc_html($log->message); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <form method="post" action="options.php">
                        <?php settings_fields('ocai_settings_group'); ?>
                        
                        <?php if ($active_tab === 'api') : ?>
                            <div id="tab-api">
                                <label style="font-weight:700; display:block; margin-bottom:10px;">OpenAI API Key (ChatGPT/DALL-E)</label>
                                <input type="password" name="ocai_api_openai" class="regular-text" style="width:100%; height:45px; border-radius:8px; margin-bottom:20px;" value="<?php echo esc_attr(get_option('ocai_api_openai')); ?>">

                                <label style="font-weight:700; display:block; margin-bottom:10px;">Google Gemini API Key</label>
                                <input type="password" name="ocai_api_gemini" class="regular-text" style="width:100%; height:45px; border-radius:8px;" value="<?php echo esc_attr(get_option('ocai_api_gemini')); ?>">
                            </div>
                        <?php endif; ?>

                        <?php if ($active_tab === 'automation') : ?>
                            <div id="tab-automation">
                                <label style="font-weight:700; display:block; margin-bottom:10px;">Frequência (Posts/Dia)</label>
                                <input type="number" name="ocai_posts_per_day" class="regular-text" style="width:100px; height:45px; border-radius:8px; margin-bottom:20px;" value="<?php echo esc_attr($posts_per_day); ?>" min="1" max="24">

                                <label style="font-weight:700; display:block; margin-bottom:10px;">Status Padrão</label>
                                <select name="ocai_post_status" style="width:100%; height:45px; border-radius:8px;">
                                    <option value="draft" <?php selected(get_option('ocai_post_status'), 'draft'); ?>>Rascunho</option>
                                    <option value="publish" <?php selected(get_option('ocai_post_status'), 'publish'); ?>>Publicado</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top:40px; border-top:1px solid #eee; padding-top:20px;">
                            <?php submit_button('Salvar Configurações', 'button-primary button-large', 'submit', true, ['style' => 'background:#ec5b13; border:none; height:50px; padding:0 40px; border-radius:30px; font-weight:700;']); ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
