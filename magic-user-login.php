<?php

/**
 * Plugin Name:       Magic User Login
 * Description:       User can login with access token by email
 * Plugin URI:        #
 * Version:           1.0.0
 * Author:            Robiul Islam
 * Author URI:        https://robiul.net/about
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path:       /languages
 * Text Domain:       magic-user-login
 */

if ( !defined( 'ABSPATH' ) ) {
    exit();
}

class Magic_User_Login {

    public function __construct() {
        add_shortcode( 'magic-user-login', array( $this, 'mul_login_shortcode' ) );

        add_action( 'plugins_loaded', array( $this, 'mul_init_plugin' ) );

        add_action( 'wp_ajax_mul-user-login', array( $this, 'mul_user_ajax_call' ) );
        add_action( 'wp_ajax_nopriv_mul-user-login', array( $this, 'mul_user_ajax_call' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'mul_user_login_scripts' ) );

        // check token
        add_action( 'init', array( $this, 'mul_user_login_token_handle' ) );
    }

    /**
     *
     * Initialize Code
     *
     * @return void
     */
    public function mul_init_plugin() {

    }

    /**
     * Add login shortcode
     *
     * @return mixed
     */
    public function mul_login_shortcode() {
        ob_start();
        ?>

            <form action="" method="post" id="mul-user-magic-login">
                <input type="email" name="mul_user_email" id="mul_user_email" placeholder="email address">
                <br/>
                <br/>
                <input type="submit" value="Send Message" name="mul-send">
            </form>

        <?php

        return ob_get_clean();
    }

    /**
     *
     * Enqueue frontend script and localize
     * @return void
     */
    public function mul_user_login_scripts() {
        wp_enqueue_script( 'mul-user-script', plugin_dir_url( __FILE__ ) . 'assets/main.js', ['jquery'] );

        wp_localize_script( 'mul-user-script', 'mulSCript', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            '_ajax_nonce' => wp_create_nonce( 'mul-user-login' ),
        ) );
    }

    /**
     *
     * Ajax Call
     *
     * @return mixed
     */
    public function mul_user_ajax_call() {
        check_ajax_referer( 'mul-user-login' );

        $user_email = isset( $_POST['mul_user_email'] ) ? sanitize_email( wp_unslash( $_POST['mul_user_email'] ) ) : '';

        $user = get_user_by( 'email', $user_email );

        if ( !$user ) {
            wp_send_json( ['status' => false, 'message' => 'User  Unauthorized!'], 403 );
        }

        $token = bin2hex( random_bytes( 32 ) );
        $expiration = time() + 10; // 10 seconds

        // store token
        update_user_meta( $user->ID, '_login_token', $token );
        update_user_meta( $user->ID, '_login_token_expiration', $expiration );

        // login url
        $login_url = add_query_arg( ['token' => $token], home_url( '/login/' ) );

        $to = $user_email;
        $subject = get_bloginfo( 'name' ) . ' Login';
        $body = "Click the link to login: $login_url";
        $headers = array(
            'From: Admin < therobiulislam12@gmail.com >',
            'Content-Type: text/plain; charset=UTF-8',
        );

        $result = wp_mail( $to, $subject, $body, $headers );

        wp_send_json( ['status' => $result, 'message' => $result ? 'success' : 'error', 'data' => $login_url], 200 );
    }

    /**
     * User Token Login Handler
     *
     * @return void
     */
    public function mul_user_login_token_handle() {
        if ( isset( $_GET['token'] ) ) {
            $token = sanitize_text_field( $_GET['token'] );

            // Check the token
            $user_query = new WP_User_Query( [
                'meta_key'   => '_login_token',
                'meta_value' => $token,
                'number'     => 1,
            ] );

            if ( !empty( $user_query->results ) ) {

                $user = $user_query->results[0];
                $token_expiration = get_user_meta( $user->ID, '_login_token_expiration', true );

                if ( $token_expiration > time() ) {
                    wp_set_current_user( $user->ID );
                    wp_set_auth_cookie( $user->ID );

                    wp_redirect( admin_url() );
                    exit;
                } else {
                    // Token has expired
                    wp_die( 'Your login link has expired. Please request a new one.' );
                }
            } else {
                wp_die( 'Invalid login token.' );
            }
        }
    }
}

new Magic_User_Login();