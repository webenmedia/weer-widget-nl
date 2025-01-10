<?php
/**
 * Plugin Name: Weer Widget NL
 * Description: Gratis Nederlandse weer widget voor het huidige weer en de weersverwachting
 * Version: 1.1
 * Author: Bestereistijd.nl
 * Author URI: https://www.bestereistijd.nl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class weatherwidgetnl_WeatherDisplayWidget {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_locations_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('weatherwidgetnl_weather_display', [$this, 'display_weather']);
    }

    public function add_locations_page() {
        add_menu_page(
            'Weerlocaties',
            'Weerlocaties',
            'manage_options',
            'weatherwidgetnl_weather_locations',
            [$this, 'render_locations_page'],
            'dashicons-cloud',
            100
        );
    }

    public function render_locations_page() {
        global $wpdb, $weatherwidgetnl_languages;
        $table_name = esc_sql($wpdb->prefix . 'weatherwidgetnl_weather_locations');

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('weatherwidgetnl_weather_locations_action', 'weatherwidgetnl_weather_locations_nonce');

            if (isset($_POST['action']) && $_POST['action'] === 'add_location') {
                if (
                    isset($_POST['display_title'], $_POST['location_name'], $_POST['iso_code'], $_POST['language'], $_POST['unit'], $_POST['forecast_days'])
                ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $wpdb->insert(
                        $table_name,
                        [
                            'display_title' => sanitize_text_field(wp_unslash($_POST['display_title'])),
                            'location_name' => sanitize_text_field(wp_unslash($_POST['location_name'])),
                            'iso_code' => sanitize_text_field(wp_unslash($_POST['iso_code'])),
                            'language' => sanitize_text_field(wp_unslash($_POST['language'])),
                            'unit' => sanitize_text_field(wp_unslash($_POST['unit'])),
                            'forecast_days' => intval(wp_unslash($_POST['forecast_days'])),
                        ]
                    );
                } else {
                    wp_die(esc_html__('Vereiste velden ontbreken.', 'weer-widget-nl'));
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_location') {
                if (
                    isset($_POST['location_id'], $_POST['display_title'], $_POST['location_name'], $_POST['iso_code'], $_POST['language'], $_POST['unit'], $_POST['forecast_days'])
                ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $updated = $wpdb->update(
                        $table_name,
                        [
                            'display_title' => sanitize_text_field(wp_unslash($_POST['display_title'])),
                            'location_name' => sanitize_text_field(wp_unslash($_POST['location_name'])),
                            'iso_code' => sanitize_text_field(wp_unslash($_POST['iso_code'])),
                            'language' => sanitize_text_field(wp_unslash($_POST['language'])),
                            'unit' => sanitize_text_field(wp_unslash($_POST['unit'])),
                            'forecast_days' => intval(wp_unslash($_POST['forecast_days'])),
                        ],
                        ['id' => intval($_POST['location_id'])],
                        ['%s', '%s', '%s', '%s', '%s', '%d'],
                        ['%d']
                    );

                    if ($updated !== false) {
                        wp_cache_delete('weatherwidgetnl_weather_location_' . intval($_POST['location_id']), 'weatherwidgetnl_weather_locations');
                    }
                } else {
                    wp_die(esc_html__('Vereiste velden ontbreken.', 'weer-widget-nl'));
                }
            } elseif (isset($_POST['location_id'], $_POST['action']) && $_POST['action'] === 'delete_location') {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->delete(
                    $table_name,
                    ['id' => intval($_POST['location_id'])]
                );
                wp_cache_delete('weatherwidgetnl_weather_location_' . intval($_POST['location_id']), 'weatherwidgetnl_weather_locations');
            }
            if (!headers_sent()) {
                wp_safe_redirect(admin_url('admin.php?page=weatherwidgetnl_weather_locations'));
                exit;
            } else {
                echo '<meta http-equiv="refresh" content="0;url=' . esc_url(admin_url('admin.php?page=weatherwidgetnl_weather_locations')) . '">';
                exit;
            }
        }

        $countries_json = file_get_contents(plugin_dir_path(__FILE__) . 'assets/json/countries-nl.json');
        $countries = json_decode($countries_json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $countries = [];
        }

        // Edit location form submission
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['location_id'])) {
            $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
            $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations'; // Dynamische tabelnaam

            $cache_key = 'weatherwidgetnl_weather_location_' . intval($location_id);
            $cache_group = 'weatherwidgetnl_weather_locations';
            $location = wp_cache_get($cache_key, $cache_group);

            if ($location === false) {
                $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations';
                if ($table_name !== $wpdb->prefix . 'weatherwidgetnl_weather_locations') {
                    return;
                }

                $safe_table_name = esc_sql($table_name);
                $query = $wpdb->prepare(
                    "SELECT * FROM %i WHERE id = %d",
                    $safe_table_name,
                    $location_id
                );
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $location = $wpdb->get_row($query);

                if ($location) {
                    wp_cache_set($cache_key, $location, $cache_group, 3600);
                }
            }

            if ($location) {
                echo '<div class="wrap">';
                echo '<h1>Weerlocatie aanpassen</h1>';
                echo '<form method="post">';
                wp_nonce_field('weatherwidgetnl_weather_locations_action', 'weatherwidgetnl_weather_locations_nonce');
                echo '<input type="hidden" name="action" value="edit_location">';
                echo '<input type="hidden" name="location_id" value="' . esc_attr($location->id) . '">';
                echo '<table class="form-table">';
                echo '<tr><th>Titel:</th><td><input type="text" name="display_title" value="' . esc_attr($location->display_title) . '" required></td></tr>';
                echo '<tr><th>Weerlocatie naam:</th><td><input type="text" name="location_name" value="' . esc_attr($location->location_name) . '" required></td></tr>';
                echo '<tr><th>Land van locatie:</th><td><select name="iso_code" required><option>Selecteer een land</option>';
                foreach ($countries as $country) {
                    echo '<option value="' . esc_attr($country['short_name']) . '"' . selected($location->iso_code, $country['short_name'], false) . '>' . esc_html($country['name']) . '</option>';
                }
                echo '</select></td></tr>';
                echo '<tr><th>Taal:</th><td><select name="language">';
                foreach ($weatherwidgetnl_languages as $locale => $name) {
                    echo '<option value="' . esc_attr($locale) . '"' . selected($location->language, $locale, false) . '>' . esc_html($name) . '</option>';
                }
                echo '</select></td></tr>';
                echo '<tr><th>Eenheden:</th><td><select name="unit"><option value="metric"' . selected($location->temp_unit, 'metric', false) . '>Metric (°C, mm, km/u)</option><option value="imperial"' . selected($location->temp_unit, 'imperial', false) . '>Imperial (°F, inch, mph)</option></select></td></tr>';
                echo '<tr><th>Weersverwachting:</th><td><select name="forecast_days"><option value="0"' . selected($location->forecast_days, '0', false) . '>Niet weergeven</option><option value="2"' . selected($location->forecast_days, '2', false) . '>2 dagen</option><option value="3"' . selected($location->forecast_days, '3', false) . '>3 dagen</option><option value="4"' . selected($location->forecast_days, '4', false) . '>4 dagen</option><option value="6"' . selected($location->forecast_days, '6', false) . '>6 dagen</option></select></td></tr>';
                echo '</table>';
                echo '<br><input type="submit" value="Wijzigingen opslaan" class="button button-primary">';
                echo '</form>';
                echo '</div>';
                return;
            }
        }


        $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations';
        if ($table_name !== $wpdb->prefix . 'weatherwidgetnl_weather_locations') {
            return;
        }

        $safe_table_name = esc_sql($table_name);
        $query = $wpdb->prepare(
            "SELECT * FROM %i",
            $table_name
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $locations = $wpdb->get_results($query);

        // Render the admin page
        echo '<div class="wrap">';
        echo '<h1>Weerlocaties</h1>';
        echo '<form method="post" style="margin-bottom: 20px;">';
        wp_nonce_field('weatherwidgetnl_weather_locations_action', 'weatherwidgetnl_weather_locations_nonce');
        echo '<input type="hidden" name="action" value="add_location">';
        echo '<table class="form-table">';
        echo '<tr><th>Titel:</th><td><input type="text" name="display_title" value="Weer in Plaatsnaam" required></td></tr>';
        echo '<tr><th>Weerlocatie plaatsnaam:</th><td><input type="text" name="location_name" required></td></tr>';
        echo '<tr><th>Land van weerlocatie:</th><td><select name="iso_code" required><option>Selecteer een land</option>';
        foreach ($countries as $country) {
            echo '<option value="' . esc_attr($country['short_name']) . '"' . selected($country['short_name'], 'NL', false) . '>' . esc_html($country['name']) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>Taal:</th><td><select name="language">';
        foreach ($weatherwidgetnl_languages as $locale => $name) {
            echo '<option value="' . esc_attr($locale) . '"' . selected($locale, 'nl_NL', false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>Eenheden:</th><td><select name="unit"><option value="metric">Metric (°C, mm, km/u)</option><option value="imperial">Imperial (°F, inch, mph)</option></select></td></tr>';
        echo '<tr><th>Weersverwachting:</th><td><select name="forecast_days"><option value="0">Niet weergeven</option><option value="2">2 dagen</option><option value="3">3 dagen</option><option value="4">4 dagen</option><option value="6" selected>6 dagen</option></select></td></tr>';
        echo '</table>';
        echo '<br><input type="submit" value="Weerlocatie toevoegen" class="button button-primary">';
        echo '</form>';

        echo '<br><h2>Toegevoegde weerlocaties</h2>';
        echo '<p>Kopieer en plak de shortcode in je bericht of pagina om het weer weer te geven.</p>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>ID</th><th>Plaatsnaam</th><th>Land</th><th>Shortcode</th><th></th></tr></thead>';
        echo '<tbody>';
        foreach ($locations as $location) {
            foreach ($countries as $country) {
                if ($country['short_name'] === $location->iso_code) {
                    $country_name = esc_html($country['name']);
                    break;
                }
            }
            echo '<tr>';
            echo '<td>' . esc_html($location->id) . '</td>';
            echo '<td>' . esc_html($location->location_name) . '</td>';
            echo '<td>' . esc_html($country_name) . '</td>';
            echo '<td>[weatherwidgetnl_weather_display location_id="' . esc_html($location->id) . '"]</td>';
            echo '<td>';
            echo '<form method="get" style="display:inline-block;">';
            wp_nonce_field('weatherwidgetnl_weather_locations_action', 'weatherwidgetnl_weather_locations_nonce');
            echo '<input type="hidden" name="page" value="weatherwidgetnl_weather_locations">';
            echo '<input type="hidden" name="action" value="edit">';
            echo '<input type="hidden" name="location_id" value="' . esc_html($location->id) . '">';
            echo '<input type="submit" value="Aanpassen" class="button button-primary">';
            echo '</form> ';
            echo '<form method="post" style="display:inline-block;">';
            wp_nonce_field('weatherwidgetnl_weather_locations_action', 'weatherwidgetnl_weather_locations_nonce');
            echo '<input type="hidden" name="action" value="delete_location">';
            echo '<input type="hidden" name="location_id" value="' . esc_html($location->id) . '">';
            echo '<input type="submit" value="Verwijderen" class="button button-secondary">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'weatherwidgetnl_weather_widget_style',
            plugins_url('assets/css/style.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css')
        );
        wp_enqueue_script(
            'weatherwidgetnl_weather_widget_script',
            plugins_url('assets/js/script.js', __FILE__),
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/script.js'),
            true
        );
        wp_localize_script('weatherwidgetnl_weather_widget_script', 'weatherwidgetnlWeatherWidgetData', [
            'apiUrl' => 'https://www.bestereistijd.nl/api/weather/weather.php',
            'imgBaseUrl' => 'https://www.bestereistijd.nl/img/weather/'
        ]);
    }

    public function display_weather($atts) {
        global $wpdb;
        $atts = shortcode_atts([
            'location_id' => '',
        ], $atts);

        if (empty($atts['location_id'])) {
            return '<p>Error: No location specified.</p>';
        }

        $cache_key = 'weatherwidgetnl_weather_location_' . intval($atts['location_id']);
        $cache_group = 'weatherwidgetnl_weather_locations';
        $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations';

        $location = wp_cache_get($cache_key, $cache_group);

        if ($location === false) {
            $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations';
            if ($table_name !== $wpdb->prefix . 'weatherwidgetnl_weather_locations') {
                return;
            }

            $safe_table_name = esc_sql($table_name);
            $query = $wpdb->prepare(
                "SELECT * FROM %i WHERE id = %d",
                $safe_table_name,
                intval($atts['location_id'])
            );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $location = $wpdb->get_row($query);

            if ($location) {
                wp_cache_set($cache_key, $location, $cache_group, 3600);
            }
        }

        if (!$location) {
            return '<p>Error: Invalid location.</p>';
        }

        return '<div id="weatherwidgetnl-weather-container">
                    <div id="weatherwidgetnl-weather-display" 
                         data-title="' . esc_attr($location->display_title) . '" 
                         data-location="' . esc_attr($location->location_name) . '" 
                         data-iso="' . esc_attr($location->iso_code) . '" 
                         data-language="' . esc_attr($location->language) . '" 
                         data-unit="' . esc_attr($location->unit) . '" 
                         data-days="' . esc_attr($location->forecast_days) . '">
                        <div class="weatherwidgetnl-weather-loading">Loading weather data for ' . esc_html($location->location_name) . '...</div>
                    </div>
                    <div class="weatherwidgetnl-weather-footer">Bron: <a href="https://www.bestereistijd.nl">Bestereistijd.nl</a></div>
                </div>';
    }
}

new weatherwidgetnl_WeatherDisplayWidget();

register_activation_hook(__FILE__, 'weatherwidgetnl_weather_display_create_table');
function weatherwidgetnl_weather_display_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'weatherwidgetnl_weather_locations';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        display_title VARCHAR(255) NOT NULL,
        location_name VARCHAR(255) NOT NULL,
        iso_code VARCHAR(10) NOT NULL,
        language VARCHAR(5) DEFAULT 'nl' NOT NULL,
        unit VARCHAR(10) DEFAULT 'metric',
        forecast_days TINYINT(1) DEFAULT 6,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

$GLOBALS['weatherwidgetnl_languages'] = [
    'af_ZA' => 'Afrikaans',
    'ar_SA' => 'العربية',
    'az_AZ' => 'Azərbaycanca',
    'be_BY' => 'Беларуская',
    'bg_BG' => 'Български',
    'bn_BD' => 'বাংলা',
    'ca_ES' => 'Català',
    'cs_CZ' => 'Čeština',
    'cy_GB' => 'Cymraeg',
    'da_DK' => 'Dansk',
    'de_DE' => 'Deutsch',
    'el_GR' => 'Ελληνικά',
    'en_US' => 'English',
    'es_ES' => 'Español',
    'et_EE' => 'Eesti',
    'eu_ES' => 'Euskara',
    'fa_IR' => 'فارسی',
    'fi_FI' => 'Suomi',
    'fr_FR' => 'Français',
    'gl_ES' => 'Galego',
    'gu_IN' => 'ગુજરાતી',
    'he_IL' => 'עברית',
    'hi_IN' => 'हिन्दी',
    'hr_HR' => 'Hrvatski',
    'hu_HU' => 'Magyar',
    'id_ID' => 'Bahasa Indonesia',
    'is_IS' => 'Íslenska',
    'it_IT' => 'Italiano',
    'ja_JP' => '日本語',
    'ka_GE' => 'ქართული',
    'kk_KZ' => 'Қазақ',
    'km_KH' => 'ខ្មែរ',
    'kn_IN' => 'ಕನ್ನಡ',
    'ko_KR' => '한국어',
    'lt_LT' => 'Lietuvių',
    'lv_LV' => 'Latviešu',
    'mk_MK' => 'Македонски',
    'ml_IN' => 'മലയാളം',
    'mn_MN' => 'Монгол',
    'mr_IN' => 'मराठी',
    'ms_MY' => 'Bahasa Melayu',
    'nb_NO' => 'Norsk Bokmål',
    'ne_NP' => 'नेपाली',
    'nl_NL' => 'Nederlands',
    'pa_IN' => 'ਪੰਜਾਬੀ',
    'pl_PL' => 'Polski',
    'pt_BR' => 'Português',
    'ro_RO' => 'Română',
    'ru_RU' => 'Русский',
    'si_LK' => 'සිංහල',
    'sk_SK' => 'Slovenčina',
    'sl_SI' => 'Slovenščina',
    'sq_AL' => 'Shqip',
    'sr_RS' => 'Српски',
    'sv_SE' => 'Svenska',
    'ta_IN' => 'தமிழ்',
    'te_IN' => 'తెలుగు',
    'th_TH' => 'ไทย',
    'tr_TR' => 'Türkçe',
    'uk_UA' => 'Українська',
    'ur_PK' => 'اردو',
    'uz_UZ' => 'O‘zbek',
    'vi_VN' => 'Tiếng Việt',
    'zh_CN' => '简体中文',
    'zh_TW' => '繁體中文',
];