<?php
    /*
     Plugin Name: DeMomentSomTres okko
     Plugin URI: http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-okko/
     Description: Displays service status information.
     Version: 2.1
     Author: marcqueralt
     Author URI: http://demomentsomtres.com
     License: GPLv2 or later
     */

    require_once (dirname(__FILE__) . '/lib/class-tgm-plugin-activation.php');

    define('DMS3_OKKO_TEXT_DOMAIN', 'DeMomentSomTres-OkKo');

    // Make sure we don't expose any info if called directly
    if (!function_exists('add_action')) {
        echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
        exit ;
    }

    $dms3OkKo = new DeMomentSomTresOkKo();

    class DeMomentSomTresOkKo {

        const TEXT_DOMAIN = DMS3_OKKO_TEXT_DOMAIN;
        const VERSION = "2.0";
        const OPTIONS = 'dms3OKKO';
        const OPTIONS_TITAN = 'dms3OKKO_options';
        const OPTION_SLUGS = 'slugs';
        const OPTION_STATUS = 'status';
        const OPTION_IS_CUSTOM_MESSAGE = 'isCustom';
        const OPTION_CUSTOM_MESSAGE = 'customMessage';
        const OPTION_URL = 'url';
        const OPTION_SERVICE_NAME = "serviceName";
        const OPTION_OK_MESSAGE = "okMessage";
        const OPTION_OKKO_MESSAGE = "okkoMessage";
        const OPTION_KO_MESSAGE = "koMessage";
        const OPTION_FAWESOME = "fawsome";
        const OPTION_OK_ICON = "okIcon";
        const OPTION_OKKO_ICON = "okkoIcon";
        const OPTION_KO_ICON = "koIcon";
        const OPTION_LOADER = "loaderIcon";
        const OK = "ok";
        const KO = "ko";
        const OKKO = "okko";

        private $pluginURL;
        private $pluginPath;
        private $langDir;
        private $printScripts=false;

        /**
         * @since 1.0
         */
        function __construct() {
            $this -> pluginURL = plugin_dir_url(__FILE__);
            $this -> pluginPath = plugin_dir_path(__FILE__);
            $this -> langDir = dirname(plugin_basename(__FILE__)) . '/languages';

            add_action('plugins_loaded', array(
                $this,
                'plugin_init'
            ));
            add_action('tgmpa_register', array(
                $this,
                'required_plugins'
            ));
            add_action('tf_create_options', array(
                $this,
                'administracio'
            ));
            add_shortcode('dms3okko', array(
                $this,
                'singleShortcode'
            ));
            add_shortcode('dms3okkoall', array(
                $this,
                'allShortcode'
            ));
            add_action("wp_enqueue_scripts",array($this,"enqueue_scripts"));
            add_action("wp_footer",array($this,"wp_footer"));
            add_action("wp_ajax_dms3okko",array($this,"webserviceSingle"));
            add_action("wp_ajax_nopriv_dms3okko",array($this,"webserviceSingle"));
            add_action("wp_ajax_dms3okkoall",array($this,"webserviceAll"));
            add_action("wp_ajax_nopriv_dms3okkoall",array($this,"webserviceAll"));
        }

        /**
         * @since 1.0
         */
        function plugin_init() {
            load_plugin_textdomain(DMS3_OKKO_TEXT_DOMAIN, false, $this -> langDir);
        }

        /**
         * @since 1.0
         */
        function required_plugins() {
            $plugins = array(
                array(
                    'name' => 'Titan Framework',
                    'slug' => 'titan-framework',
                    'required' => true
                ),
            );
            $config = array();
            tgmpa($plugins, $config);
        }

        function administracio() {
            $titan = TitanFramework::getInstance(self::OPTIONS);
            $panel = $titan -> createAdminPanel(array(
                'name' => __('OK KO', self::TEXT_DOMAIN),
                'title' => __('DeMomentSomTres OK KO', self::TEXT_DOMAIN),
                'desc' => __("Publica l'estat dels serveis des d'una única pantalla", self::TEXT_DOMAIN),
                'icon' => 'dashicons-dashboard',
                'position' => 5,
            ));

            $tabStatus = $panel -> createTab(array(
                'name' => __('Estat', self::TEXT_DOMAIN),
                'title' => __('Estats', self::TEXT_DOMAIN),
                'desc' => __('Gestiona els estats', self::TEXT_DOMAIN),
                'id' => 'status'
            ));
            $tabConfig = $panel -> createTab(array(
                'name' => __('Configuració', self::TEXT_DOMAIN),
                'title' => __('Configuració general', self::TEXT_DOMAIN),
                'desc' => __('Configura les diferents especificitats del plugin', self::TEXT_DOMAIN),
                'id' => 'services'
            ));
            $tabURLs = $panel -> createTab(array(
                'name' => __('URLs', self::TEXT_DOMAIN),
                'title' => __('URLs dels diferents serveis', self::TEXT_DOMAIN),
                'desc' => __('Permet gestionar les URL que mostren continguts dels serveis', self::TEXT_DOMAIN),
                'id' => 'urls'
            ));

            $tabConfig -> createOption(array(
                'name' => __("Serveis", self::TEXT_DOMAIN),
                'id' => self::OPTION_SLUGS,
                'type' => 'textarea',
                'desc' => __("Afegeix la llista, tal com vulguis que aparegui ordenada, dels codis interns que s'usaran per cridar els serveis als shortcodes.", self::TEXT_DOMAIN) . "<br/>" . __("Si vols eliminar algun dels continguts, n'hi ha prou amb que el suprimeixis", self::TEXT_DOMAIN) . "<br/>" . __("Pots incorporar-ne en qualsevol moment i pots canviar-ne l'ordre sempre que conservis el codi", self::TEXT_DOMAIN),
                'placeholder' => __("Valors separats per comes", self::TEXT_DOMAIN),
                'is_code' => true
            ));
            $tabConfig -> createOption(array(
                "name" => __("Missatge OK", self::TEXT_DOMAIN),
                "id" => self::OPTION_OK_MESSAGE,
                "desc" => __("Missatge que es mostrarà quan el servei funciona correctament", self::TEXT_DOMAIN),
                "default" => __("El servei està operatiu", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                "name" => __("Missatge estat incert", self::TEXT_DOMAIN),
                "id" => self::OPTION_OKKO_MESSAGE,
                "desc" => __("Missatge que es mostrarà quan el servei es trobi en un estat incert", self::TEXT_DOMAIN),
                "default" => __("El servei té incidències parcials", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                "name" => __("Missatge KO", self::TEXT_DOMAIN),
                "id" => self::OPTION_KO_MESSAGE,
                "desc" => __("Missatge que es mostrarà quan el servei no funciona correctament", self::TEXT_DOMAIN),
                "default" => __("El servei pateix interrupcions", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                'name' => __("Integració amb Font Awesome"),
                'id' => self::OPTION_FAWESOME,
                'desc' => __("Usa el Font Awesome per presentar els resultats"),
                'type' => "checkbox",
                'default' => true
            ));
            $tabConfig -> createOption(array(
                "name" => __("Icona OK", self::TEXT_DOMAIN),
                "id" => self::OPTION_OK_ICON,
                "desc" => __("Denominació de la icona Fontawesome a utilitzar quan l'estat sigui correcte", self::TEXT_DOMAIN),
                "default" => __("fa-check-circle-o", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                "name" => __("Icona estat incert", self::TEXT_DOMAIN),
                "id" => self::OPTION_OKKO_ICON,
                "desc" => __("Denominació de la icona Fontawesome a utilitzar quan l'estat sigui incert", self::TEXT_DOMAIN),
                "default" => __("fa-eye", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                "name" => __("Icona KO", self::TEXT_DOMAIN),
                "id" => self::OPTION_KO_ICON,
                "desc" => __("Denominació de la icona Fontawesome a utilitzar quan l'estat sigui incorrecte", self::TEXT_DOMAIN),
                "default" => __("fa-exclamation-triangle", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                "name" => __("Icona Spinner", self::TEXT_DOMAIN),
                "id" => self::OPTION_LOADER,
                "desc" => __("Denominació de la icona Fontawesome a utilitzar mentre no responguin els Web Services", self::TEXT_DOMAIN),
                "default" => __("fa-spinner fa-spin", self::TEXT_DOMAIN),
            ));
            $tabConfig -> createOption(array(
                'type' => "save",
                'save' => __("Desa els canvis", self::TEXT_DOMAIN),
                'use_reset' => false
            ));
            $slugs = $this -> getSlugs();
            if ("" === $slugs) :
                $tabStatus -> createOption(array(
                    "type" => "note",
                    "name" => __("Error", self::TEXT_DOMAIN),
                    "desc" => __("El component no està configurat", self::TEXT_DOMAIN),
                    "color" => "red",
                ));
                $tabURLs -> createOption(array(
                    "type" => "note",
                    "name" => __("Error", self::TEXT_DOMAIN),
                    "desc" => __("El component no està configurat", self::TEXT_DOMAIN),
                    "color" => "red",
                ));
            else :
                $slugs = explode(",", $slugs);
                foreach ($slugs as $s) :
                    $serviceName = $this -> getServiceName($s);
                    $tabStatus -> createOption(array(
                        "name" => $serviceName,
                        "type" => "heading"
                    ));
                    $tabStatus -> createOption(array(
                        "name" => __("Estat", self::TEXT_DOMAIN),
                        "id" => self::OPTION_STATUS . "-$s",
                        "type" => "radio",
                        "default" => self::OK,
                        "options" => array(
                            self::OK => __("Correcte", self::TEXT_DOMAIN),
                            self::OKKO => __("Estat incert",self::TEXT_DOMAIN),
                            self::KO => __("Amb incidències", self::TEXT_DOMAIN)
                        ),
                    ));
                    $tabStatus -> createOption(array(
                        "name" => __("Missatge específic?", self::TEXT_DOMAIN),
                        "desc" => __("Mostra un missatge específic per aquest servei", self::TEXT_DOMAIN),
                        "id" => self::OPTION_IS_CUSTOM_MESSAGE . "-$s",
                        "type" => "checkbox",
                        "default" => false,
                    ));
                    $tabStatus -> createOption(array(
                        "name" => __("Missatge", self::TEXT_DOMAIN),
                        "id" => self::OPTION_CUSTOM_MESSAGE . "-$s",
                        "default" => "",
                    ));
                    $tabURLs -> createOption(array(
                        "name" => $serviceName,
                        "type" => "heading"
                    ));
                    $tabURLs -> createOption(array(
                        "name" => __("Nom", self::TEXT_DOMAIN),
                        "id" => self::OPTION_SERVICE_NAME . "-$s",
                        "default" => "",
                    ));
                    $tabURLs -> createOption(array(
                        "name" => __("URL", self::TEXT_DOMAIN),
                        "id" => self::OPTION_URL . "-$s",
                        "default" => "",
                    ));
                endforeach;
                $tabStatus -> createOption(array(
                    'type' => "save",
                    'save' => __("Desa els canvis", self::TEXT_DOMAIN),
                    'use_reset' => false
                ));
                $tabURLs -> createOption(array(
                    'type' => "save",
                    'save' => __("Desa els canvis", self::TEXT_DOMAIN),
                    'use_reset' => false
                ));
            endif;
        }

        function getSlugs() {
            $oldoptions = unserialize(get_option(self::OPTIONS_TITAN));
            $slugs = !empty($oldoptions[self::OPTION_SLUGS]) ? $oldoptions[self::OPTION_SLUGS] : "";
            return $slugs;
        }

        function getServiceName($slug) {
            $oldoptions = unserialize(get_option(self::OPTIONS_TITAN));
            $name = !empty($oldoptions[self::OPTION_SERVICE_NAME . "-$slug"]) ? $oldoptions[self::OPTION_SERVICE_NAME . "-$slug"] : $slug;
            return $name;
        }

        function getServiceStatus($slug) {
            $titan = TitanFramework::getInstance(self::OPTIONS);
            $slugs = explode(",", $titan -> getOption(self::OPTION_SLUGS));
            if (!in_array($slug, $slugs)) :
                return "";
            endif;
            $url = $titan -> getOption(self::OPTION_URL . "-$slug");
            $isCustom = $titan -> getOption(self::OPTION_IS_CUSTOM_MESSAGE . "-$slug");
            $customMessage = $titan -> getOption(self::OPTION_CUSTOM_MESSAGE . "-$slug");
            $status = $titan -> getOption(self::OPTION_STATUS . "-$slug");
            if ($status == self::KO) :
                if ($isCustom) :
                    $message = $customMessage;
                else :
                    $message = $titan -> getOption(self::OPTION_KO_MESSAGE);
                endif;
                $icon = $titan -> getOption(self::OPTION_KO_ICON);
                $class = self::KO;
            elseif($status==self::OK):
                if ($isCustom) :
                    $message = $customMessage;
                else :
                    $message = $titan -> getOption(self::OPTION_OK_MESSAGE);
                endif;
                $icon = $titan -> getOption(self::OPTION_OK_ICON);
                $class = self::OK;
            else:
                $status=self::OKKO;
                if ($isCustom) :
                    $message = $customMessage;
                else :
                    $message = $titan -> getOption(self::OPTION_OKKO_MESSAGE);
                endif;
                $icon = $titan -> getOption(self::OPTION_OKKO_ICON);
                $class = self::OK;
            endif;
            $fai = $titan -> getOption(self::OPTION_FAWESOME);
            return array(
                "slug" => $slug,
                "status" => $status,
                "icon" => $icon,
                "message" => $message,
                "url" => $url,
                "fawesome" => $fai
            );
        }

        /**
         * @param mixed $atts
         * @return string
         * @since 1.0
         */
        function singleShortcode($atts) {
            extract(shortcode_atts(array("slug" => ""), $atts));
            echo "<div class='dms3okko' data='$slug'>";
            echo $this -> getLoaderIcon();
            echo "</div>";
            $this->printScripts=true;
        }

        /**
        * @since 2.0
        **/
        function webserviceSingle() {
          if(isset($_REQUEST['dms3okkoslug'])):
            $slug=$_REQUEST['dms3okkoslug'];
            $data = $this -> getServiceStatus($slug);
            $output = "<div class='dms3okko'>";
            $output .= "<div class='" . $data['status'] . "'>";
            $output .= "<div class='message'>";
            $output .= $data['message'];
            if ($data["url"]) :
                $output .= "<br/><a href='" . $data["url"] . "'>" . __("Més detalls", self::TEXT_DOMAIN) . "</a>";
            endif;
            $output .= "</div>";
            if ($data['fawesome']) :
                $output .= "<div class='icon'>";
                $output .= "<i class='fa fa-3x {$data["icon"]}'></i>";
                $output .= "</div>";
            endif;
            $output .= "</div>";
            $output .= "</div>";
            wp_send_json_success($output);
          else:
            wp_send_json_error(__("No s'indica cap servei"));
          endif;
        }
        /**
         * @param mixed $atts
         * @return string
         * @since 1.0
         */
        function allShortcode($atts) {
            extract(shortcode_atts(array(), $atts));
            echo "<div class='dms3okkoall'>";
            echo $this -> getLoaderIcon();
            echo "</div>";
            $this->printScripts=true;
        }
      
        /**
        * @since 2.0
        **/
        function webserviceAll() {
            $titan = TitanFramework::getInstance(self::OPTIONS);
            $slugs = $titan -> getOption(self::OPTION_SLUGS);
            $slugs = explode(",", $slugs);
            $output = "<div class='dms3okkoAll'>";
            foreach ($slugs as $s) :
                $status = $titan->getOption(self::OPTION_STATUS . "-$s");
                $url = $titan->getOption(self::OPTION_URL . "-$s");
                $name = $titan->getOption(self::OPTION_SERVICE_NAME . "-$s");
                if ($titan->getOption(self::OPTION_IS_CUSTOM_MESSAGE . "-$s")) :
                    $message = $titan->getOption(self::OPTION_CUSTOM_MESSAGE . "-$s");
                else :
                    if ($status == self::KO) :
                        $message = $titan->getOption(self::OPTION_KO_MESSAGE);
                    elseif($status==self::OK) :
                        $message = $titan->getOption(self::OPTION_OK_MESSAGE);
                    else:
                        $message = $titan->getOption(self::OPTION_OKKO_MESSAGE);
                    endif;
                endif;
                if ($status == self::KO) :
                    $icon = $titan->getOption(self::OPTION_KO_ICON);
                elseif ($status==self::OK) :
                    $icon = $titan->getOption(self::OPTION_OK_ICON);
                else:
                    $icon = $titan->getOption(self::OPTION_OKKO_ICON);
                endif;
                $output .= "<div class='service " . $status . "'>";
                $output .= "<div class='name'>";
                $output .= $name;
                $output .= "</div>";
                $output .= "<div class='message'>";
                $output .= $message;
                if ($url != "") :
                    $output .= "<br/><a href='$url'>" . __("Més detalls", self::TEXT_DOMAIN) . "</a>";
                endif;
                $output .= "</div>";
                if ($titan->getOption(self::OPTION_FAWESOME)) :
                    $output .= "<div class='icon'>";
                    $output .= "<i class='fa fa-3x $icon'></i>";
                    $output .= "</div>";
                endif;
                $output .= "</div>";
            endforeach;
            $output .= "</div>";
            // $output .= '<pre>' . print_r($slugs, true) . "</pre>";
            // $output .= '<pre>' . print_r($options, true) . "</pre>";
            wp_send_json_success($output);
        }
      
        /**
        * @since 2.0
        */
        function enqueue_scripts() {
          wp_register_script("dms3okko",$this->pluginURL."js/dms3-okko.js","jquery",self::VERSION,true);
          wp_localize_script("dms3okko","dms3okko",array(
            'ajaxurl' => admin_url('admin-ajax.php'),
          ));
        }
      
        /**
        * @since 2.0
        */
        function wp_footer() {
          if($this->printScripts):
            wp_enqueue_script("dms3okko");
          endif;
        }
      
        /**
        * @since 2.0
        **/
        function getLoaderIcon() {
          $titan = TitanFramework::getInstance(self::OPTIONS);
          $fawesome = $titan -> getOption(self::OPTION_FAWESOME);
          $loader = $titan -> getOption(self::OPTION_LOADER);
          if($fawesome):
            return "<div class='okkoloader'><i class='fa fa-3x $loader'></i></div>";
          else:
            return "";
          endif;
        }
    }
