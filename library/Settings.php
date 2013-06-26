<?php
/**
 * Class Socialer_Settings
 *
 * TODO: move html to templates
 */
class Socialer_Settings {

    const GENERAL_SETTINGS_KEY      = 'socialer_options_general';
    const OPTIONS_KEY               = 'socialer_options';

    const OPT_SOCIALER_IS_ACTIVE    = 1;
    const OPT_SOCIALER_ISNT_ACTIVE  = 0;

    const SCHEDULE_DEFAULT_HOURS    = 1;

    public $tabs                    = array();

    public function init() {
        add_action( 'admin_menu', array( $this, 'addSettings' ) );
        add_action( 'admin_init', array( $this, 'registerSettings' ), 1 );
    }

    public function addSettings() {
        add_options_page( 'Socialer', 'Socialer', 'manage_options', self::OPTIONS_KEY, array( $this, 'displaySettings' ) );
    }

    public function registerSettings() {
        register_setting( self::GENERAL_SETTINGS_KEY, self::GENERAL_SETTINGS_KEY, array( $this, 'validate' ) );

        add_settings_section( 'main_section', 'Socialer Plugin Activation', array(), self::GENERAL_SETTINGS_KEY );
        add_settings_field( 'socialer_is_active', 'Is Socialer Plugin Active?', array( $this, 'settingsSocialerActiveOrNot' ), self::GENERAL_SETTINGS_KEY, 'main_section' );

        add_settings_section( 'schedule_section', 'Scheduling', array(), self::GENERAL_SETTINGS_KEY );
        add_settings_field( 'schedule_default_hours', 'Default Hours:', array( $this, 'settingsSocialerScheduling' ), self::GENERAL_SETTINGS_KEY, 'schedule_section' );

        add_settings_section( 'others_section', 'Other', array(), self::GENERAL_SETTINGS_KEY );
        add_settings_field( 'show_dashboard_button', 'Show Dashboard Button:', array( $this, 'settingsSocialerShowDashboardButton' ), self::GENERAL_SETTINGS_KEY, 'others_section' );
        add_settings_field( 'api_key', 'API Key:', array( $this, 'settingsSocialerApiKey' ), self::GENERAL_SETTINGS_KEY, 'others_section' );

        $this->tabs[ self::GENERAL_SETTINGS_KEY ] = __( 'Socialer Settings' );
    }

    public function validate( $input ) {
        return $input;
    }

    /**
     * @return bool
     */
    public static function isSocialerActive() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );

        if ( !isset($options['socialer_is_active']) ) {
            return true;
        }

        return (Boolean)$options['socialer_is_active'];
    }

    /**
     * @return bool
     */
    public static function isShowDashboardButton() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );

        if ( !isset($options['show_dashboard_button']) ) {
            return true;
        }

        return (Boolean)$options['show_dashboard_button'];
    }

    /**
     * @return int
     */
    public static function getDefaultScheduleHours() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );

        if ( !isset($options['socialer_schedule_default_hours']) ) {
            return self::SCHEDULE_DEFAULT_HOURS;
        }

        return intval($options['socialer_schedule_default_hours']);
    }

    public function displaySettings() {
        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::GENERAL_SETTINGS_KEY;
        ?>
        <div class="wrap">
            <?php $this->displaySettingsTabs(); ?>

            <form action="options.php" method="post">
                    <?php settings_fields( $tab ); ?>
                    <?php do_settings_sections( $tab ); ?>
                    <?php submit_button(); ?>
            </form>
            <?php $options = get_option( self::GENERAL_SETTINGS_KEY );
            var_dump($options); ?>
        </div>
    <?php
    }

    public function displaySettingsTabs() {
        $current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : self::GENERAL_SETTINGS_KEY;

        screen_icon();
        echo '<h2 class="nav-tab-wrapper">';
        foreach ( $this->tabs as $tab_key => $tab_caption ) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . self::OPTIONS_KEY . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
        echo '</h2>';
    }

    public function settingsSocialerActiveOrNot() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );
        ?>
        <select id="socialer_is_active" name="<?php echo self::GENERAL_SETTINGS_KEY ?>[socialer_is_active]">
            <option value="<?php echo self::OPT_SOCIALER_IS_ACTIVE ?>"
                    <?php if ($options['socialer_is_active'] == self::OPT_SOCIALER_IS_ACTIVE): ?>
                    selected="selected"
                    <?php endif ?>
            >Yes</option>
            <option value="<?php echo self::OPT_SOCIALER_ISNT_ACTIVE ?>"
                <?php if ($options['socialer_is_active'] == self::OPT_SOCIALER_ISNT_ACTIVE): ?>
                    selected="selected"
                <?php endif ?>
            >No</option>
        </select>
        <?php
    }

    public function settingsSocialerScheduling() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );
        ?>
        <select id="socialer_schedule_default_hours" name="<?php echo self::GENERAL_SETTINGS_KEY ?>[schedule_default_hours]">
            <?php for ( $i = 1; $i < 13; $i++ ): ?>
            <option
                value="<?php echo $i ?>"
                <?php if ($options['schedule_default_hours'] == $i): ?>
                    selected="selected"
                <?php endif ?>
                >
                <?php echo $i ?>hr
                </option>
            <?php endfor ?>
        </select>
        <?php
    }

    public function settingsSocialerShowDashboardButton() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );
        ?>
        <select id="socialer_show_dashboard_button" name="<?php echo self::GENERAL_SETTINGS_KEY ?>[show_dashboard_button]">
            <option value="<?php echo self::OPT_SOCIALER_IS_ACTIVE ?>"
                <?php if ($options['show_dashboard_button'] == self::OPT_SOCIALER_IS_ACTIVE): ?>
                    selected="selected"
                <?php endif ?>
                >Yes</option>
            <option value="<?php echo self::OPT_SOCIALER_ISNT_ACTIVE ?>"
                <?php if ($options['show_dashboard_button'] == self::OPT_SOCIALER_ISNT_ACTIVE): ?>
                    selected="selected"
                <?php endif ?>
                >No</option>
        </select>
        <?php
    }

    public function settingsSocialerApiKey() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );
        ?>
        <input
            type="text"
            id="socialer-api-key"
            value="<?php echo $options['api_key'] ?>"
            name="<?php echo self::GENERAL_SETTINGS_KEY ?>[api_key]"
            style="width: 300px;"
        >
        <?php
    }

    /**
     * @return string
     */
    public static function getApiKey() {
        $options = get_option( self::GENERAL_SETTINGS_KEY );
        return @$options['api_key'];
    }
}