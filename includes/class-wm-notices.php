<?php

/**
 * Register a global dasboard notice.
 *
 * @since 3.0.0
 *
 * @see WM_Notices::add_notice
 *
 * @param string $message Notice message.
 * @param string $type Optional. Notice type.
 *                     Default 'info'.
 *                     Accepts 'info', 'updated', 'warning', 'error'.
 * @param string $title Optional. Notice title (plugin or theme name).
 * @param boolean|integer $backtrace Optional. Wether to dislplay stack trace or not, or the index of backtrace to display.
 */
function wm_add_notice( $message, $type = 'info', $title = null, $backtrace = false )
{
    return WM_Notices::add_notice( $message, $type, $title, $backtrace );
}

/**
 * Collect and display user registered notices.
 *
 * @since 3.0.0
 */
class WM_Notices {


    // USER METHODS

    /**
     * @see wm_add_notice
     */
    public static function add_notice( $message, $type = 'info', $title = null, $backtrace = false )
    {
        $notices = self::get_global_notices();
        $notices[] = self::get_notice( $message, $type, $title, $backtrace );
        set_transient( 'wm_notices', array_unique( $notices ) );
    }

    public static function get_notice( $message, $type = 'info', $title = null, $backtrace = false )
    {
        $message = rtrim( ucfirst( trim( (string) $message ) ), '.' ) . '.';
        $content = wpautop( $title ? "<strong class=\"wm-notice-title\">{$title}</strong><br />{$message}" : $message );
        if ( false !== $backtrace ) {
            if ( is_array( $backtrace ) ) {
                $content .= self::get_backtrace( $backtrace );
            } else if ( $stack = array_slice( debug_backtrace(), 2 ) ) {
                if ( true === $backtrace ) {
                    $content .= "<ol start=\"0\" class=\"wm-notice-backtrace\">";
                    foreach ( $stack as $i => $backtrace ) {
                        $content .= "<li>" . self::get_backtrace( $backtrace ) . "</li>";
                    }
                    $content .= "</ol>";
                } else if ( isset( $stack[$backtrace] ) ) {
                    $content .= self::get_backtrace( $stack[$backtrace] );
                }
            }
        }
        return "<div class=\"wm-notice notice {$type}\">{$content}</div>";
    }

    public static function get_global_notices()
    {
        return array_filter( (array) get_transient( 'wm_notices' ) );
    }


    // PRIVATE METHODS

    // Format backtrace informations
    private static function get_backtrace( $backtrace )
    {
        ob_start();
        if ( ! empty( $backtrace['class'] ) ) {
            echo "<strong>{$backtrace['class']}</strong>";
            if ( ! empty( $backtrace['type'] ) ) {
                echo $backtrace['type'];
            }
        }
        if ( ! empty( $backtrace['function'] ) ) {
            echo "<strong>{$backtrace['function']}</strong>(";
            if ( ! empty( $backtrace['args'] ) ) {
                $args = implode( ', ', array_map( function ( $arg ) {
                    if ( is_scalar( $arg ) ) {
                        return var_export( $arg, true );
                    }
                    $type = gettype( $arg );
                    return "<em>{$type}</em>";
                }, $backtrace['args'] ) );
                echo " {$args} ";
            }
            echo ");\n";
        }
        if ( ! empty( $output ) ) {
            $output = "<pre>{$output}</pre>";
        }
        if ( ! empty( $backtrace['file'] ) ) {
            $file = preg_replace( '/^' . preg_quote( ABSPATH, '/' ) . '/', '', $backtrace['file'] );
            echo "<p>In <strong>/{$file}</strong>";
            if ( ! empty( $backtrace['line'] ) ) {
                echo " on line <strong>{$backtrace['line']}</strong>.";
            }
            echo "</p>";
        }
        return ob_get_clean();
    }


    // WORDPRESS ACTIONS

    // Display global notices
    public static function admin_notices()
    {
        array_map( 'echo', self::get_global_notices() );
        // Delete cached alerts
        delete_transient( 'wm_notices' );
    }

    public static function public_notices()
    {
        $css_uri = plugin_dir_path( __FILE__ ) . '/css/wm-notices.css';
        echo "<div class=\"wm-notices\"><style scoped>@import \"{$css_uri}\"</style>";
        self::admin_notices();
        echo "</div>";
    }


    // PHP ACTIONS

    public static function log_error( $e, $message, $file, $line )
    {
        $backtrace = compact( 'file', 'line' );
        foreach ( array(
            'error'   => array(
                E_ERROR             => __( 'Error', 'wm-notices' ),
                E_CORE_ERROR        => __( 'Core Error', 'wm-notices' ),
                E_COMPILE_ERROR     => __( 'Compile Error', 'wm-notices' ),
                E_USER_ERROR        => __( 'User Error', 'wm-notices' ),
                E_RECOVERABLE_ERROR => __( 'Recoverable Error', 'wm-notices' ),
                E_PARSE             => __( 'Parse Error', 'wm-notices' )
            ),
            'warning' => array(
                E_WARNING           => __( 'Warning', 'wm-notices' ),
                E_CORE_WARNING      => __( 'Core Warning', 'wm-notices' ),
                E_COMPILE_WARNING   => __( 'Compile Warning', 'wm-notices' ),
                E_USER_WARNING      => __( 'User Warning', 'wm-notices' ),
                E_DEPRECATED        => __( 'Deprecated', 'wm-notices' ),
                E_USER_DEPRECATED   => __( 'User Deprecated', 'wm-notices' )
            ),
            'info'    => array(
                E_NOTICE            => __( 'Notice', 'wm-notices' ),
                E_USER_NOTICE       => __( 'User Notice', 'wm-notices' ),
                E_STRICT            => __( 'Strict Standard', 'wm-notices' )
            )
        ) as $type => $errors ) {
            if ( isset( $errors[$e] ) ) {
                // return self::add_notice( $message, $type, $errors[$e], true );
                return self::add_notice( $message, $type, $errors[$e], $backtrace );
            }
        }
        return self::add_notice( $message, 'error', __( 'Unknown Error', 'wm-notices' ), $backtrace );
    }

    public static function log_exception( Exception $e )
    {
        return self::add_notice( $e->getMessage(), 'error', get_class( $e ), array(
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ) );
    }
}


// ACTIONS

add_action( 'admin_notices', array( 'WM_Notices', 'admin_notices' ) );

if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WM_DEBUG_NOTICES' ) && WM_DEBUG_NOTICES ) {
    // add_action( 'wp_footer', array( 'WM_Notices', 'public_notices' ) );
    set_error_handler( array( 'WM_Notices', 'log_error' ) );
    set_exception_handler( array( 'WM_Notices', 'log_exception' ) );
    error_reporting( E_ALL );
    ini_set( 'display_errors', 'off' );
}


?>
