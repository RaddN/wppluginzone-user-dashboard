<?php

/**
 * Plugin Name: WPPluginZone User Dashboard
 * Plugin URI: https://wppluginzone.com
 * Description: A comprehensive user dashboard plugin with WooCommerce integration
 * Version: 1.0.9
 * Author: WPPluginZone
 * Author URI: https://wppluginzone.com
 * License: GPL v2 or later
 * Text Domain: wppluginzone-user-dashboard
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WPPluginZoneUserDashboard
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        add_shortcode('wppluginzone_user_dashboard', array($this, 'render_dashboard'));
    }

    public function activate()
    {
        // Plugin activation code
    }

    public function deactivate()
    {
        // Plugin deactivation code
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            'wppluginzone-dashboard-styles',
            plugin_dir_url(__FILE__) . 'assets/dashboard-styles.css',
            array(),
            '1.0.9'
        );
    }

    public function render_dashboard($atts)
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your dashboard.</p>';
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<p>WooCommerce is required for this dashboard to work properly.</p>';
        }

        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        // Get user orders
        $customer_orders = wc_get_orders(array(
            'customer' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        // Get total orders count
        $total_orders = wc_get_orders(array(
            'customer' => $user_id,
            'return' => 'ids',
            'limit' => -1,
        ));
        $total_orders_count = count($total_orders);

        // Get customer data
        $customer = new WC_Customer($user_id);
        $billing_first_name = $customer->get_billing_first_name();
        $billing_last_name = $customer->get_billing_last_name();
        $billing_email = $customer->get_billing_email();

        // Get shipping address
        $shipping_first_name = $customer->get_shipping_first_name();
        $shipping_last_name = $customer->get_shipping_last_name();
        $shipping_address_1 = $customer->get_shipping_address_1();
        $shipping_address_2 = $customer->get_shipping_address_2();
        $shipping_city = $customer->get_shipping_city();
        $shipping_state = $customer->get_shipping_state();
        $shipping_postcode = $customer->get_shipping_postcode();
        $shipping_country = $customer->get_shipping_country();
        $shipping_phone = $customer->get_shipping_phone() ?: $customer->get_billing_phone();

        // Get store credit balance (if available)
        $store_credit = 0;
        if (function_exists('wc_get_customer_store_credit')) {
            $store_credit = wc_get_customer_store_credit($user_id);
        }

        ob_start();
?>

        <div class="wpplug-dashboard-container">
            <div class="wpplug-mb-3">
                <p class="wpplug-section-heading">Dashboard</p>
                <p class="wpplug-dashboard-subheading">Your User ID is <b><?php echo esc_html($user_id); ?></b></p>
            </div>

            <div class="wpplug-dashboard-broker-updates">
                <div class="wpplug-container">
                    <div class="wpplug-row">
                        <div class="wpplug-col wpplug-col-xs-12 wpplug-mb-5 wpplug-p-2">
                            <p class="wpplug-dashboard-heading">
                                Recent Orders
                                <span class="wpplug-acc-header-links wpplug-fr wpplug-font-weight-bold">
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                                        View All Orders (<?php echo $total_orders_count; ?>)
                                    </a>
                                </span>
                            </p>

                            <div style="overflow-x: auto;">
                                <table class="wpplug-table wpplug-acc-table wpplug-acc-order-table" style="min-width: 60rem;">
                                    <thead>
                                        <tr class="wpplug-acc-table-head">
                                            <th scope="col" class="wpplug-acc-table-head-td">Order No.</th>
                                            <th scope="col" class="wpplug-acc-table-head-td">Status</th>
                                            <th scope="col" class="wpplug-acc-table-head-td">Total</th>
                                            <th scope="col" class="wpplug-acc-table-head-td">Billing</th>
                                            <th scope="col" class="wpplug-acc-table-head-td">Order Date</th>
                                            <th scope="col" class="wpplug-acc-table-head-td" style="width: 6rem !important;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($customer_orders)) : ?>
                                            <?php foreach ($customer_orders as $order) : ?>
                                                <tr class="wpplug-acc-table-body">
                                                    <td class="wpplug-acc-table-body-td wpplug-position-relative">
                                                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="wpplug-acc-table-body-td-link">
                                                            <?php echo esc_html($order->get_order_number()); ?>
                                                        </a>
                                                    </td>
                                                    <td class="wpplug-acc-table-body-td">
                                                        <span><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span>
                                                    </td>
                                                    <td class="wpplug-acc-table-body-td wpplug-position-relative">
                                                        <?php echo $order->get_formatted_order_total(); ?>
                                                    </td>
                                                    <td class="wpplug-acc-table-body-td">
                                                        <span><?php echo $order->is_paid() ? 'Paid' : 'Unpaid'; ?></span>
                                                    </td>
                                                    <td class="wpplug-acc-table-body-td" title="<?php echo esc_attr($order->get_date_created()->format('M j, Y g:i:s A')); ?>">
                                                        <?php echo esc_html($order->get_date_created()->format('M j, Y')); ?>
                                                    </td>
                                                    <td class="wpplug-acc-table-body-td" style="overflow: unset !important; width: 4rem !important;"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr class="wpplug-acc-table-body">
                                                <td colspan="6" class="wpplug-acc-table-body-td" style="text-align: center;">No orders found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wpplug-container" style=" margin-top: 30px !important; ">
                    <div class="wpplug-row">
                        <div class="wpplug-col-md-6 wpplug-col-sm-12 wpplug-mb-5 wpplug-p-2">
                            <div class="wpplug-acc-profile-box wpplug-overflow-hidden">
                                <p class="wpplug-dashboard-heading">
                                    Account Information
                                    <span class="wpplug-acc-header-links wpplug-fr wpplug-font-weight-bold">
                                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">
                                            View Profile
                                        </a>
                                    </span>
                                </p>

                                <div class="wpplug-pt-2 wpplug-pb-3">
                                    <span class="wpplug-acc-profile-subheading" style="font-size: 1rem;text-transform: uppercase;font-weight: 600;">
                                        <?php echo esc_html($billing_first_name . ' ' . $billing_last_name); ?><br>
                                    </span>
                                    <span class="wpplug-acc-profile-box-span">
                                        <?php echo esc_html($billing_email); ?><br>
                                    </span>
                                </div>

                                <div class="wpplug-row">
                                    <?php if ($shipping_first_name || $shipping_address_1) : ?>
                                        <div class="wpplug-col-sm-12 wpplug-col-md-6 wpplug-mt-4">
                                            <h3 class="wpplug-dashboard-heading" style=" font-size: 1.2rem; ">Shipping Address</h3>
                                            <div class="wpplug-pt-2 wpplug-pb-3">
                                                <span class="wpplug-acc-profile-subheading" style="font-size: 1rem;text-transform: uppercase;font-weight: 600;">
                                                    <?php echo esc_html($shipping_first_name . ' ' . $shipping_last_name); ?><br>
                                                </span>

                                                <div class="wpplug-list-address1" style="width: auto; padding: 0.2rem 0.6rem; background: rgb(140, 198, 63); border-radius: 2rem; color: rgb(255, 255, 255); font-size: 1.1rem; pointer-events: none; display: inline-block;">
                                                    <i class="fal fa-badge-check" style="font-weight: bold;"></i> Verified
                                                </div><br>

                                                <?php if ($shipping_address_1) : ?>
                                                    <span class="wpplug-acc-profile-box-span">
                                                        <?php echo esc_html($shipping_address_1); ?><?php echo $shipping_address_2 ? ', ' . esc_html($shipping_address_2) : ''; ?><br>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($shipping_city || $shipping_state || $shipping_postcode) : ?>
                                                    <span class="wpplug-acc-profile-box-span">
                                                        <?php echo esc_html($shipping_city); ?><?php echo $shipping_state ? ', ' . esc_html($shipping_state) : ''; ?><?php echo $shipping_postcode ? ', ' . esc_html($shipping_postcode) : ''; ?><br>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($shipping_country) : ?>
                                                    <span class="wpplug-acc-profile-box-span">
                                                        <?php echo esc_html(WC()->countries->countries[$shipping_country]); ?><br>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($shipping_phone) : ?>
                                                    <span class="wpplug-acc-profile-box-span">
                                                        <?php echo esc_html($shipping_phone); ?><br>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="wpplug-col-sm-12 wpplug-col-md-12 wpplug-mt-4">
                                        <div style="border-top: 3px solid rgb(245, 245, 245);padding-top: 2rem;display: flex;width: 100%;flex-wrap: nowrap;justify-content: space-between;align-items: center;">
                                            <div class="icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48px" height="48px" viewBox="0 0 48 48" version="1.1">
                                                    <title>Group 11</title>
                                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                        <g id="My-Account-Update-|-Dashboard-2" transform="translate(-991.000000, -1248.000000)" fill="#8BC63E">
                                                            <g id="Group-3-Copy" transform="translate(971.000000, 909.000000)">
                                                                <g id="Group-5" transform="translate(20.000000, 139.000000)">
                                                                    <g id="Group-6" transform="translate(0.000000, 200.000000)">
                                                                        <g id="Group-11" transform="translate(0.000000, 0.000000)">
                                                                            <path d="M9.29040155,10.0646781 L9.29040155,13.9356294 C9.29040155,15.6432027 10.680073,17.0323903 12.3871625,17.0323903 C14.094252,17.0323903 15.4839235,15.6432027 15.4839235,13.9356294 L15.4839235,10.0646781 L9.29040155,10.0646781 Z M12.3871625,18.5807708 C9.82556056,18.5807708 7.74202107,16.496941 7.74202107,13.9356294 L7.74202107,9.2904879 C7.74202107,8.86294134 8.0884712,8.51629766 8.51621131,8.51629766 L16.2581137,8.51629766 C16.6858538,8.51629766 17.032304,8.86294134 17.032304,9.2904879 L17.032304,13.9356294 C17.032304,16.496941 14.9487645,18.5807708 12.3871625,18.5807708 L12.3871625,18.5807708 Z" id="Fill-80" />
                                                                            <path d="M17.032304,10.0646781 L17.032304,13.9356294 C17.032304,15.6432027 18.4219755,17.0323903 20.1290649,17.0323903 C21.8361544,17.0323903 23.2258259,15.6432027 23.2258259,13.9356294 L23.2258259,10.0646781 L17.032304,10.0646781 Z M20.1290649,18.5807708 C17.5673662,18.5807708 15.4839235,16.496941 15.4839235,13.9356294 L15.4839235,9.2904879 C15.4839235,8.86294134 15.8303736,8.51629766 16.2581137,8.51629766 L24.0000161,8.51629766 C24.4276595,8.51629766 24.7742064,8.86294134 24.7742064,9.2904879 L24.7742064,13.9356294 C24.7742064,16.496941 22.6906669,18.5807708 20.1290649,18.5807708 L20.1290649,18.5807708 Z" id="Fill-81" />
                                                                            <path d="M24.7742064,10.0646781 L24.7742064,13.9356294 C24.7742064,15.6432027 26.1637811,17.0323903 27.8709674,17.0323903 C29.5780568,17.0323903 30.9677283,15.6432027 30.9677283,13.9356294 L30.9677283,10.0646781 L24.7742064,10.0646781 Z M27.8709674,18.5807708 C25.3093654,18.5807708 23.2258259,16.496941 23.2258259,13.9356294 L23.2258259,9.2904879 C23.2258259,8.86294134 23.572276,8.51629766 24.0000161,8.51629766 L31.7419186,8.51629766 C32.1696587,8.51629766 32.5161088,8.86294134 32.5161088,9.2904879 L32.5161088,13.9356294 C32.5161088,16.496941 30.4325693,18.5807708 27.8709674,18.5807708 L27.8709674,18.5807708 Z" id="Fill-82" />
                                                                            <path d="M32.5161088,10.0646781 L32.5161088,13.9356294 C32.5161088,15.6432027 33.9057803,17.0323903 35.6128698,17.0323903 C37.3199593,17.0323903 38.7096307,15.6432027 38.7096307,13.9356294 L38.7096307,10.0646781 L32.5161088,10.0646781 Z M35.6128698,18.5807708 C33.0512678,18.5807708 30.9677283,16.496941 30.9677283,13.9356294 L30.9677283,9.2904879 C30.9677283,8.86294134 31.3141785,8.51629766 31.7419186,8.51629766 L39.483821,8.51629766 C39.9115611,8.51629766 40.2580112,8.86294134 40.2580112,9.2904879 L40.2580112,13.9356294 C40.2580112,16.496941 38.1744717,18.5807708 35.6128698,18.5807708 L35.6128698,18.5807708 Z" id="Fill-83" />
                                                                            <path d="M40.2580112,10.0646781 L40.2580112,13.9356294 C40.2580112,15.6432027 41.6476827,17.0323903 43.3547722,17.0323903 C45.0618617,17.0323903 46.4515332,15.6432027 46.4515332,13.9356294 L46.4515332,10.0646781 L40.2580112,10.0646781 Z M43.3547722,18.5807708 C40.7930735,18.5807708 38.7096307,16.496941 38.7096307,13.9356294 L38.7096307,9.2904879 C38.7096307,8.86294134 39.0560809,8.51629766 39.483821,8.51629766 L47.2257234,8.51629766 C47.6534635,8.51629766 47.9999136,8.86294134 47.9999136,9.2904879 L47.9999136,13.9356294 C47.9999136,16.496941 45.9163742,18.5807708 43.3547722,18.5807708 L43.3547722,18.5807708 Z" id="Fill-84" />
                                                                            <path d="M39.4847887,10.0647749 C39.2099512,10.0647749 38.944791,9.91884006 38.8044691,9.66122826 L34.1593276,1.14513559 C33.9541672,0.769750101 34.0925537,0.299526303 34.468036,0.0947529835 C34.8435182,-0.110020335 35.3138388,0.0282693965 35.5180315,0.40365489 L40.1631729,8.91974755 C40.3683333,9.29513304 40.2299468,9.76535684 39.8544646,9.97013016 C39.7363038,10.034388 39.6096269,10.0647749 39.4847887,10.0647749" id="Fill-85" />
                                                                            <path d="M31.7409508,10.0647749 C31.3741782,10.0647749 31.0480506,9.8028083 30.9803089,9.42897118 L29.4319284,0.912878522 C29.3554771,0.492202899 29.6341856,0.0891401043 30.0551516,0.0126888179 C30.4751498,-0.0637624685 30.8786964,0.21523634 30.9551477,0.635911963 L32.5035282,9.15200462 C32.5799795,9.57268025 32.3011742,9.97574304 31.8803051,10.0521943 C31.8338537,10.0607104 31.7874022,10.0647749 31.7409508,10.0647749" id="Fill-86" />
                                                                            <path d="M24.0009839,10.0647749 C23.9544357,10.0647749 23.9080811,10.0607104 23.8615329,10.0521943 C23.4406637,9.97574304 23.1619552,9.57268025 23.2384065,9.15200462 L24.786787,0.635911963 C24.8631415,0.21523634 25.2658172,-0.0640527899 25.6867831,0.0126888179 C26.1077491,0.0891401043 26.3864576,0.492202899 26.3100063,0.912878522 L24.7616258,9.42897118 C24.6938842,9.8028083 24.3677565,10.0647749 24.0009839,10.0647749" id="Fill-87" />
                                                                            <path d="M16.257146,10.0647749 C16.1323078,10.0647749 16.0055342,10.034388 15.8874702,9.97013016 C15.5119879,9.76535684 15.3736014,9.29513304 15.5787618,8.91974755 L20.2239032,0.40365489 C20.4280959,0.0282693965 20.8984165,-0.110117109 21.2738988,0.0947529835 C21.649381,0.299526303 21.7877675,0.769750101 21.5826071,1.14513559 L16.9374657,9.66122826 C16.7971437,9.91884006 16.5319835,10.0647749 16.257146,10.0647749" id="Fill-88" />
                                                                            <path d="M47.2257234,10.0646781 C47.0157243,10.0646781 46.8057252,9.97932367 46.6528226,9.81122761 L39.1412418,1.54858548 L16.6006929,1.54858548 L9.08911209,9.81122761 C8.80169396,10.1276779 8.31201863,10.1508068 7.99556837,9.86329191 C7.67911811,9.57568023 7.65579563,9.08610168 7.94331053,8.76974819 L15.685213,0.253655531 C15.8323091,0.0922368652 16.0403727,0.000205000218 16.2581137,0.000205000218 L39.483821,0.000205000218 C39.701562,0.000205000218 39.9095288,0.0922368652 40.0567218,0.253655531 L47.7986242,8.76974819 C48.0860423,9.08610168 48.0627198,9.57568023 47.7463663,9.86329191 C47.5983025,9.99819456 47.4115291,10.0646781 47.2257234,10.0646781" id="Fill-89" />
                                                                            <path d="M1.62978911,46.4516195 L16.951014,46.4516195 L15.5574716,32.5161952 L3.02333154,32.5161952 L1.62978911,46.4516195 Z M17.8064942,48 L0.774308891,48 C0.555600148,48 0.346568783,47.9070972 0.200343601,47.745485 C0.0533442286,47.582905 -0.0182683688,47.3661317 0.0039896007,47.1483907 L1.55237008,31.6645859 C1.59204733,31.2687811 1.92494914,30.9678147 2.32268938,30.9678147 L16.2581137,30.9678147 C16.6557572,30.9678147 16.9887558,31.2687811 17.028433,31.6645859 L18.5768135,47.1483907 C18.5990715,47.3661317 18.5274589,47.582905 18.3803627,47.745485 C18.2342343,47.9070972 18.025203,48 17.8064942,48 L17.8064942,48 Z" id="Fill-90" />
                                                                            <path d="M12.3871625,36.3871464 C11.9594224,36.3871464 11.6129723,36.0406962 11.6129723,35.6129561 L11.6129723,27.8710537 C11.6129723,26.5907366 10.5707187,25.548483 9.29040155,25.548483 C8.01008444,25.548483 6.96783083,26.5907366 6.96783083,27.8710537 L6.96783083,35.6129561 C6.96783083,36.0406962 6.62138069,36.3871464 6.19364058,36.3871464 C5.76590048,36.3871464 5.41945034,36.0406962 5.41945034,35.6129561 L5.41945034,27.8710537 C5.41945034,25.7362241 7.15547519,24.0001025 9.29040155,24.0001025 C11.4252311,24.0001025 13.1613528,25.7362241 13.1613528,27.8710537 L13.1613528,35.6129561 C13.1613528,36.0406962 12.8148059,36.3871464 12.3871625,36.3871464" id="Fill-91" />
                                                                            <path d="M13.1613528,36.3871464 L11.6129723,36.3871464 C11.1852322,36.3871464 10.838782,36.0406962 10.838782,35.6129561 C10.838782,35.185216 11.1852322,34.8387659 11.6129723,34.8387659 L13.1613528,34.8387659 C13.5890929,34.8387659 13.935543,35.185216 13.935543,35.6129561 C13.935543,36.0406962 13.5890929,36.3871464 13.1613528,36.3871464" id="Fill-92" />
                                                                            <path d="M6.96783083,36.3871464 L5.41945034,36.3871464 C4.99171023,36.3871464 4.6452601,36.0406962 4.6452601,35.6129561 C4.6452601,35.185216 4.99171023,34.8387659 5.41945034,34.8387659 L6.96783083,34.8387659 C7.39557094,34.8387659 7.74202107,35.185216 7.74202107,35.6129561 C7.74202107,36.0406962 7.39557094,36.3871464 6.96783083,36.3871464" id="Fill-93" />
                                                                            <path d="M12.3871625,23.2259123 C11.9594224,23.2259123 11.6129723,22.8794621 11.6129723,22.451722 L11.6129723,17.8065806 C11.6129723,17.3788405 11.9594224,17.0323903 12.3871625,17.0323903 C12.8148059,17.0323903 13.1613528,17.3788405 13.1613528,17.8065806 L13.1613528,22.451722 C13.1613528,22.8794621 12.8148059,23.2259123 12.3871625,23.2259123" id="Fill-94" />
                                                                            <path d="M43.3547722,39.4839073 L20.1290649,39.4839073 C19.7013248,39.4839073 19.3548747,39.1374572 19.3548747,38.7097171 C19.3548747,38.281977 19.7013248,37.9355269 20.1290649,37.9355269 L42.580582,37.9355269 L42.580582,17.8065806 C42.580582,17.3788405 42.9270321,17.0323903 43.3547722,17.0323903 C43.7825123,17.0323903 44.1289624,17.3788405 44.1289624,17.8065806 L44.1289624,38.7097171 C44.1289624,39.1374572 43.7825123,39.4839073 43.3547722,39.4839073" id="Fill-95" />
                                                                            <path d="M39.483821,39.4839073 C39.0560809,39.4839073 38.7096307,39.1374572 38.7096307,38.7097171 L38.7096307,22.451722 L17.032304,22.451722 L17.032304,28.645244 C17.032304,29.0729841 16.6858538,29.4194342 16.2581137,29.4194342 C15.8303736,29.4194342 15.4839235,29.0729841 15.4839235,28.645244 L15.4839235,21.6775318 C15.4839235,21.2497917 15.8303736,20.9033415 16.2581137,20.9033415 L39.483821,20.9033415 C39.9115611,20.9033415 40.2580112,21.2497917 40.2580112,21.6775318 L40.2580112,38.7097171 C40.2580112,39.1374572 39.9115611,39.4839073 39.483821,39.4839073" id="Fill-96" />
                                                                            <path d="M27.8709674,39.4839073 C27.4432273,39.4839073 27.0967771,39.1374572 27.0967771,38.7097171 L27.0967771,21.6775318 C27.0967771,21.2497917 27.4432273,20.9033415 27.8709674,20.9033415 C28.2987075,20.9033415 28.6451576,21.2497917 28.6451576,21.6775318 L28.6451576,38.7097171 C28.6451576,39.1374572 28.2987075,39.4839073 27.8709674,39.4839073" id="Fill-97" />
                                                                            <path d="M30.9677283,32.5161952 C30.5399882,32.5161952 30.1935381,32.169745 30.1935381,31.7420049 L30.1935381,28.645244 C30.1935381,28.2175038 30.5399882,27.8710537 30.9677283,27.8710537 C31.3953717,27.8710537 31.7419186,28.2175038 31.7419186,28.645244 L31.7419186,31.7420049 C31.7419186,32.169745 31.3953717,32.5161952 30.9677283,32.5161952" id="Fill-98" />
                                                                            <path d="M24.7742064,32.5161952 C24.3464663,32.5161952 24.0000161,32.169745 24.0000161,31.7420049 L24.0000161,28.645244 C24.0000161,28.2175038 24.3464663,27.8710537 24.7742064,27.8710537 C25.2019465,27.8710537 25.5483966,28.2175038 25.5483966,28.645244 L25.5483966,31.7420049 C25.5483966,32.169745 25.2019465,32.5161952 24.7742064,32.5161952" id="Fill-99" />
                                                                        </g>
                                                                    </g>
                                                                </g>
                                                            </g>
                                                        </g>
                                                    </g>
                                                </svg>
                                            </div>
                                            <div style="padding-left: 1rem;width: 100%;">
                                                <p class="wpplug-dashboard-heading" style=" display: flex; justify-content: space-between; gap: 65px; ">
                                                    Store Credit Balance
                                                    <span class="wpplug-acc-header-links wpplug-fr wpplug-font-weight-bold">
                                                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('store-credit')); ?>">
                                                            View Store Credit
                                                        </a>
                                                    </span>
                                                </p>
                                                <p class="wpplug-dashboard-heading wpplug-font-weight-normal">
                                                    $<?php echo number_format($store_credit, 2); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new WPPluginZoneUserDashboard();

// CSS Styles - You can save this as assets/dashboard-styles.css
function wppluginzone_dashboard_inline_styles()
{
    ?>
    <style>
        .wpplug-dashboard-container * {
            margin: 0 !important;
        }

        .wpplug-section-heading {
            color: #484848;
            font-size: 2.2rem;
            font-weight: bold;
            line-height: 3.8rem;
            margin: 0;
        }

        .wpplug-dashboard-subheading {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 20px;
        }

        .wpplug-dashboard-heading {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .wpplug-container {
            width: 100%;
        }

        .wpplug-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }

        .wpplug-col,
        .wpplug-col-xs-12,
        .wpplug-col-sm-12,
        .wpplug-col-md-6,
        .wpplug-col-md-12 {
            position: relative;
            width: 100%;
        }

        @media (min-width: 768px) {
            .wpplug-col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        .wpplug-mb-3 {
            margin-bottom: 1rem;
        }

        .wpplug-mb-5 {
            margin-bottom: 3rem;
        }

        .wpplug-mt-4 {
            margin-top: 1.5rem;
        }

        .wpplug-p-2 {
            padding: 0.5rem 0;
        }

        .wpplug-pt-2 {
            padding-top: 0.5rem;
        }

        .wpplug-pb-3 {
            padding-bottom: 1rem;
        }

        .wpplug-fr {
            float: right;
        }

        .wpplug-acc-header-links {
            font-size: 1rem;
        }

        .wpplug-acc-header-links a {
            color: #007cba;
            text-decoration: none;
        }

        .wpplug-acc-header-links a:hover {
            text-decoration: underline;
        }

        .wpplug-table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .wpplug-acc-table {
            border: none;
        }

        .wpplug-acc-table-head {
            background-color: #f8f9fa;
        }

        .wpplug-acc-table-head-td,
        .wpplug-acc-table-body-td {
            padding: 12px;
            text-align: left;
            border: none;
        }

        .wpplug-acc-table-head-td {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .wpplug-acc-table-body-td-link {
            color: #007cba;
            text-decoration: none;
            font-weight: bold;
        }

        .wpplug-acc-table-body-td-link:hover {
            text-decoration: underline;
        }

        .wpplug-acc-profile-box {
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 5px;
            background-color: #fff;
        }

        .wpplug-acc-profile-subheading {
            font-weight: bold;
            color: #333;
        }

        .wpplug-acc-profile-box-span {
            color: #666;
            font-size: 0.9rem;
        }

        .wpplug-font-weight-bold {
            font-weight: bold;
        }

        .wpplug-font-weight-normal {
            font-weight: normal;
        }

        .wpplug-position-relative {
            position: relative;
        }

        .wpplug-overflow-hidden {
            overflow: hidden;
        }

        @media (max-width: 767px) {
            .wpplug-fr {
                float: none;
                display: block;
                margin-top: 10px;
            }

            .wpplug-dashboard-heading {
                font-size: 1.2rem;
            }

            .wpplug-section-heading {
                font-size: 2rem;
            }
        }
    </style>
<?php
}
add_action('wp_head', 'wppluginzone_dashboard_inline_styles');
?>

<?php
/**
 * WooCommerce User Dashboard Profile Shortcode with Inline Editing
 * Shortcode: [wppluginzone_user_dashboard_profile]
 */

// Add the shortcode
add_shortcode('wppluginzone_user_dashboard_profile', 'wppluginzone_user_dashboard_profile_callback');

// Handle AJAX requests
add_action('wp_ajax_wpz_update_profile', 'wpz_handle_profile_update');
add_action('wp_ajax_wpz_update_address', 'wpz_handle_address_update');
add_action('wp_ajax_wpz_change_password', 'wpz_handle_password_change');

function wppluginzone_user_dashboard_profile_callback($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="wppluginzone-login-required">
                    <p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your profile.</p>
                </div>';
    }
    
    // Get current user data
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Get WooCommerce customer data
    $customer = new WC_Customer($user_id);
    
    // Get user meta data
    $first_name = $current_user->first_name ?: $customer->get_first_name();
    $last_name = $current_user->last_name ?: $customer->get_last_name();
    $email = $current_user->user_email;
    $display_name = $current_user->display_name;
    
    // Get billing and shipping addresses
    $billing_address = array(
        'first_name' => $customer->get_billing_first_name(),
        'last_name' => $customer->get_billing_last_name(),
        'company' => $customer->get_billing_company(),
        'address_1' => $customer->get_billing_address_1(),
        'address_2' => $customer->get_billing_address_2(),
        'city' => $customer->get_billing_city(),
        'state' => $customer->get_billing_state(),
        'postcode' => $customer->get_billing_postcode(),
        'country' => $customer->get_billing_country(),
        'phone' => $customer->get_billing_phone()
    );
    
    $shipping_address = array(
        'first_name' => $customer->get_shipping_first_name(),
        'last_name' => $customer->get_shipping_last_name(),
        'company' => $customer->get_shipping_company(),
        'address_1' => $customer->get_shipping_address_1(),
        'address_2' => $customer->get_shipping_address_2(),
        'city' => $customer->get_shipping_city(),
        'state' => $customer->get_shipping_state(),
        'postcode' => $customer->get_shipping_postcode(),
        'country' => $customer->get_shipping_country()
    );
    
    // Check if email is verified
    $email_verified = true; // Default to true, implement your verification logic
    
    // Get country names
    $countries = WC()->countries->get_countries();
    $billing_country_name = isset($countries[$billing_address['country']]) ? $countries[$billing_address['country']] : $billing_address['country'];
    $shipping_country_name = isset($countries[$shipping_address['country']]) ? $countries[$shipping_address['country']] : $shipping_address['country'];
    
    // Get state names
    $states = WC()->countries->get_states($billing_address['country']);
    $billing_state_name = isset($states[$billing_address['state']]) ? $states[$billing_address['state']] : $billing_address['state'];
    
    $shipping_states = WC()->countries->get_states($shipping_address['country']);
    $shipping_state_name = isset($shipping_states[$shipping_address['state']]) ? $shipping_states[$shipping_address['state']] : $shipping_address['state'];
    
    // Start output buffering
    ob_start();
    ?>
    
    <div class="wppluginzone-user-dashboard">
        <style>
            .wppluginzone-user-dashboard {
                font-family: 'Open Sans', Arial, sans-serif;
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .wpz-breadcrumbs {
                margin-bottom: 20px;
                font-size: 14px;
            }
            .wpz-breadcrumbs a {
                color: #007cba;
                text-decoration: none;
                margin-right: 5px;
            }
            .wpz-breadcrumbs a:hover {
                text-decoration: underline;
            }
            .wpz-section-heading {
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #333;
            }
            .wpz-dashboard-subheading {
                font-size: 18px;
                color: #666;
                margin-bottom: 10px;
            }
            .wpz-update-text {
                font-size: 14px;
                color: #666;
                margin-bottom: 30px;
            }
            .wpz-profile-box {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 25px;
                margin-bottom: 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                position: relative;
            }
            .wpz-profile-heading {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 20px;
                color: #333;
                border-bottom: 2px solid #007cba;
                padding-bottom: 10px;
            }
            .wpz-profile-subheading {
                font-size: 16px;
                font-weight: bold;
                text-transform: uppercase;
                color: #333;
                margin-bottom: 8px;
            }
            .wpz-profile-info {
                font-size: 14px;
                color: #666;
                margin-bottom: 5px;
                line-height: 1.5;
            }
            .wpz-verified-badge {
                display: inline-block;
                background: #8cc63f;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: bold;
                margin-left: 8px;
            }
            .wpz-edit-link {
                color: #007cba;
                text-decoration: none;
                font-size: 14px;
                margin-top: 15px;
                display: inline-block;
                cursor: pointer;
                background: none;
                border: none;
                padding: 0;
            }
            .wpz-edit-link:hover {
                text-decoration: underline;
            }
            .wpz-edit-link i {
                margin-right: 5px;
            }
            .wpz-address-container {
                display: flex;
                gap: 30px;
                flex-wrap: wrap;
            }
            .wpz-address-box {
                flex: 1;
                min-width: 300px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                min-height: 300px;
                position: relative;
            }
            .wpz-no-address {
                color: #999;
                font-style: italic;
                text-align: center;
                padding: 40px 20px;
            }
            
            /* Modal Styles */
            .wpz-modal {
                display: none;
                position: fixed;
                z-index: 10000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                animation: fadeIn 0.3s;
            }
            .wpz-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 0;
                border: none;
                border-radius: 8px;
                width: 90%;
                max-width: 600px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                animation: slideIn 0.3s;
            }
            .wpz-modal-header {
                padding: 20px 25px;
                background: #007cba;
                color: white;
                border-radius: 8px 8px 0 0;
                position: relative;
            }
            .wpz-modal-title {
                margin: 0;
                font-size: 20px;
                font-weight: bold;
            }
            .wpz-close {
                position: absolute;
                right: 20px;
                top: 50%;
                transform: translateY(-50%);
                color: white;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background-color 0.3s;
            }
            .wpz-close:hover {
                background-color: rgba(255,255,255,0.2);
            }
            .wpz-modal-body {
                padding: 25px;
            }
            .wpz-form-group {
                margin-bottom: 20px;
            }
            .wpz-form-row {
                display: flex;
                gap: 15px;
            }
            .wpz-form-row .wpz-form-group {
                flex: 1;
            }
            .wpz-form-label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            .wpz-form-input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                transition: border-color 0.3s;
                box-sizing: border-box;
            }
            .wpz-form-input:focus {
                outline: none;
                border-color: #007cba;
                box-shadow: 0 0 0 2px rgba(0,124,186,0.2);
            }
            .wpz-form-select {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                background: white;
                box-sizing: border-box;
            }
            .wpz-btn {
                padding: 12px 24px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
                margin-right: 10px;
            }
            .wpz-btn-primary {
                background: #007cba;
                color: white;
            }
            .wpz-btn-primary:hover {
                background: #005a87;
            }
            .wpz-btn-secondary {
                background: #6c757d;
                color: white;
            }
            .wpz-btn-secondary:hover {
                background: #545b62;
            }
            .wpz-loading {
                display: none;
                text-align: center;
                padding: 20px;
            }
            .wpz-spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #007cba;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                animation: spin 1s linear infinite;
                margin: 0 auto 10px;
            }
            .wpz-success-message {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 15px;
                display: none;
            }
            .wpz-error-message {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 12px;
                border-radius: 4px;
                margin-bottom: 15px;
                display: none;
            }
            
            @keyframes fadeIn {
                from {opacity: 0;}
                to {opacity: 1;}
            }
            @keyframes slideIn {
                from {transform: translateY(-50px); opacity: 0;}
                to {transform: translateY(0); opacity: 1;}
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @media (max-width: 768px) {
                .wpz-address-container {
                    flex-direction: column;
                }
                .wpz-address-box {
                    min-width: 100%;
                }
                .wpz-modal-content {
                    width: 95%;
                    margin: 10% auto;
                }
                .wpz-form-row {
                    flex-direction: column;
                    gap: 0;
                }
            }
        </style>
        
        <!-- Breadcrumbs -->
        <div class="wpz-breadcrumbs">
            <a href="<?php echo home_url(); ?>">Home</a> &raquo;
            <a href="<?php echo wc_get_page_permalink('myaccount'); ?>">My Account</a> &raquo;
            <span>Profile</span>
        </div>
        
        <!-- Profile Header -->
        <p class="wpz-section-heading">Profile</p>
        <p class="wpz-dashboard-subheading">Your User ID is <strong><?php echo $user_id; ?></strong></p>
        <p class="wpz-update-text">Update your account information, password and notification options.</p>
        
        <!-- Account Information -->
        <div class="wpz-profile-box">
            <h3 class="wpz-profile-heading">Account Information</h3>
            <div class="wpz-profile-subheading" id="display-name"><?php echo esc_html($display_name); ?></div>
            <div class="wpz-profile-info" id="user-email">
                <?php echo esc_html($email); ?>
                <?php if ($email_verified): ?>
                    <span class="wpz-verified-badge">✓ Validated</span>
                <?php endif; ?>
            </div>
            <div class="wpz-profile-info">Password: ******</div>
            
            <button class="wpz-edit-link" onclick="openAccountModal()">
                <i class="fas fa-pencil"></i> Edit Account Information
            </button>
            <button class="wpz-edit-link" onclick="openPasswordModal()" style="margin-left: 20px;">
                <i class="fas fa-key"></i> Change Password
            </button>
        </div>
        
        <!-- Default Account Address -->
        <h3 class="wpz-section-heading" style="font-size: 20px; margin-bottom: 20px;">Default Account Address</h3>
        
        <div class="wpz-address-container">
            <!-- Shipping Address -->
            <div class="wpz-address-box">
                <h4 class="wpz-profile-heading">
                    Shipping Address
                    <span style="font-size: 12px; color: #666; font-weight: normal; margin-left: 10px;" title="Used for default Shipping Address at checkout">ⓘ</span>
                </h4>
                
                <div id="shipping-address-display">
                    <?php if (!empty($shipping_address['first_name']) || !empty($shipping_address['last_name'])): ?>
                        <div class="wpz-profile-subheading">
                            <?php echo esc_html(trim($shipping_address['first_name'] . ' ' . $shipping_address['last_name'])); ?>
                        </div>
                        
                        <?php if (!empty($shipping_address['company'])): ?>
                            <div class="wpz-profile-info"><?php echo esc_html($shipping_address['company']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($shipping_address['address_1'])): ?>
                            <div class="wpz-profile-info">
                                <?php echo esc_html($shipping_address['address_1']); ?>
                                <?php if (!empty($shipping_address['address_2'])): ?>
                                    , <?php echo esc_html($shipping_address['address_2']); ?>
                                <?php endif; ?>
                                <span class="wpz-verified-badge">✓ Verified</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($shipping_address['city']) || !empty($shipping_state_name) || !empty($shipping_address['postcode'])): ?>
                            <div class="wpz-profile-info">
                                <?php 
                                $address_parts = array_filter([
                                    $shipping_address['city'],
                                    $shipping_state_name,
                                    $shipping_address['postcode']
                                ]);
                                echo esc_html(implode(', ', $address_parts));
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($shipping_country_name)): ?>
                            <div class="wpz-profile-info"><?php echo esc_html($shipping_country_name); ?></div>
                        <?php endif; ?>
                        
                        <button class="wpz-edit-link" onclick="openAddressModal('shipping')">
                            <i class="fas fa-pencil"></i> Edit Address
                        </button>
                    <?php else: ?>
                        <div class="wpz-no-address">
                            No shipping address set
                            <br><br>
                            <button class="wpz-edit-link" onclick="openAddressModal('shipping')">
                                <i class="fas fa-plus"></i> Add Address
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Billing Address -->
            <div class="wpz-address-box">
                <h4 class="wpz-profile-heading">
                    Billing Address
                    <span style="font-size: 12px; color: #666; font-weight: normal; margin-left: 10px;" title="Used for default Billing Address at checkout">ⓘ</span>
                </h4>
                
                <div id="billing-address-display">
                    <?php if (!empty($billing_address['first_name']) || !empty($billing_address['last_name'])): ?>
                        <div class="wpz-profile-subheading">
                            <?php echo esc_html(trim($billing_address['first_name'] . ' ' . $billing_address['last_name'])); ?>
                        </div>
                        
                        <?php if (!empty($billing_address['company'])): ?>
                            <div class="wpz-profile-info"><?php echo esc_html($billing_address['company']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($billing_address['address_1'])): ?>
                            <div class="wpz-profile-info">
                                <?php echo esc_html($billing_address['address_1']); ?>
                                <?php if (!empty($billing_address['address_2'])): ?>
                                    , <?php echo esc_html($billing_address['address_2']); ?>
                                <?php endif; ?>
                                <span class="wpz-verified-badge">✓ Verified</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($billing_address['city']) || !empty($billing_state_name) || !empty($billing_address['postcode'])): ?>
                            <div class="wpz-profile-info">
                                <?php 
                                $billing_parts = array_filter([
                                    $billing_address['city'],
                                    $billing_state_name,
                                    $billing_address['postcode']
                                ]);
                                echo esc_html(implode(', ', $billing_parts));
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($billing_country_name)): ?>
                            <div class="wpz-profile-info"><?php echo esc_html($billing_country_name); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($billing_address['phone'])): ?>
                            <div class="wpz-profile-info"><?php echo esc_html($billing_address['phone']); ?></div>
                        <?php endif; ?>
                        
                        <button class="wpz-edit-link" onclick="openAddressModal('billing')">
                            <i class="fas fa-pencil"></i> Edit Address
                        </button>
                    <?php else: ?>
                        <div class="wpz-no-address">
                            No billing address set
                            <br><br>
                            <button class="wpz-edit-link" onclick="openAddressModal('billing')">
                                <i class="fas fa-plus"></i> Add Address
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Information Modal -->
    <div id="accountModal" class="wpz-modal">
        <div class="wpz-modal-content">
            <div class="wpz-modal-header">
                <h3 class="wpz-modal-title">Edit Account Information</h3>
                <span class="wpz-close" onclick="closeModal('accountModal')">&times;</span>
            </div>
            <div class="wpz-modal-body">
                <div class="wpz-success-message" id="account-success"></div>
                <div class="wpz-error-message" id="account-error"></div>
                <div class="wpz-loading" id="account-loading">
                    <div class="wpz-spinner"></div>
                    <p>Updating account information...</p>
                </div>
                <form id="accountForm">
                    <div class="wpz-form-row">
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">First Name</label>
                            <input type="text" class="wpz-form-input" name="first_name" value="<?php echo esc_attr($first_name); ?>">
                        </div>
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">Last Name</label>
                            <input type="text" class="wpz-form-input" name="last_name" value="<?php echo esc_attr($last_name); ?>">
                        </div>
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Display Name</label>
                        <input type="text" class="wpz-form-input" name="display_name" value="<?php echo esc_attr($display_name); ?>">
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Email Address</label>
                        <input type="email" class="wpz-form-input" name="email" value="<?php echo esc_attr($email); ?>">
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="wpz-btn wpz-btn-secondary" onclick="closeModal('accountModal')">Cancel</button>
                        <button type="submit" class="wpz-btn wpz-btn-primary">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="wpz-modal">
        <div class="wpz-modal-content">
            <div class="wpz-modal-header">
                <h3 class="wpz-modal-title">Change Password</h3>
                <span class="wpz-close" onclick="closeModal('passwordModal')">&times;</span>
            </div>
            <div class="wpz-modal-body">
                <div class="wpz-success-message" id="password-success"></div>
                <div class="wpz-error-message" id="password-error"></div>
                <div class="wpz-loading" id="password-loading">
                    <div class="wpz-spinner"></div>
                    <p>Changing password...</p>
                </div>
                <form id="passwordForm">
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Current Password</label>
                        <input type="password" class="wpz-form-input" name="current_password" required>
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">New Password</label>
                        <input type="password" class="wpz-form-input" name="new_password" required>
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Confirm New Password</label>
                        <input type="password" class="wpz-form-input" name="confirm_password" required>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="wpz-btn wpz-btn-secondary" onclick="closeModal('passwordModal')">Cancel</button>
                        <button type="submit" class="wpz-btn wpz-btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Address Modal -->
    <div id="addressModal" class="wpz-modal">
        <div class="wpz-modal-content">
            <div class="wpz-modal-header">
                <h3 class="wpz-modal-title" id="address-modal-title">Edit Address</h3>
                <span class="wpz-close" onclick="closeModal('addressModal')">&times;</span>
            </div>
            <div class="wpz-modal-body">
                <div class="wpz-success-message" id="address-success"></div>
                <div class="wpz-error-message" id="address-error"></div>
                <div class="wpz-loading" id="address-loading">
                    <div class="wpz-spinner"></div>
                    <p>Updating address...</p>
                </div>
                <form id="addressForm">
                    <input type="hidden" id="address-type" name="address_type">
                    <div class="wpz-form-row">
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">First Name</label>
                            <input type="text" class="wpz-form-input" name="first_name">
                        </div>
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">Last Name</label>
                            <input type="text" class="wpz-form-input" name="last_name">
                        </div>
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Company (Optional)</label>
                        <input type="text" class="wpz-form-input" name="company">
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Country</label>
                        <select class="wpz-form-select" name="country" id="address-country">
                            <?php foreach ($countries as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Address Line 1</label>
                        <input type="text" class="wpz-form-input" name="address_1">
                    </div>
                    <div class="wpz-form-group">
                        <label class="wpz-form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="wpz-form-input" name="address_2">
                    </div>
                    <div class="wpz-form-row">
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">City</label>
                            <input type="text" class="wpz-form-input" name="city">
                        </div>
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">State/Province</label>
                            <input type="text" class="wpz-form-input" name="state" id="address-state">
                        </div>
                    </div>
                    <div class="wpz-form-row">
                        <div class="wpz-form-group">
                            <label class="wpz-form-label">Postal Code</label>
                            <input type="text" class="wpz-form-input" name="postcode">
                        </div>
                        <div class="wpz-form-group" id="phone-group" style="display: none;">
                            <label class="wpz-form-label">Phone</label>
                            <input type="tel" class="wpz-form-input" name="phone">
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="wpz-btn wpz-btn-secondary" onclick="closeModal('addressModal')">Cancel</button>
                        <button type="submit" class="wpz-btn wpz-btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAccountModal() {
            document.getElementById('accountModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function openAddressModal(type) {
            const modal = document.getElementById('addressModal');
            const title = document.getElementById('address-modal-title');
            const phoneGroup = document.getElementById('phone-group');
            const form = document.getElementById('addressForm');
            
            // Reset form
            form.reset();
            
            // Set address type
            document.getElementById('address-type').value = type;
            
            // Set modal title and show/hide phone field
            if (type === 'billing') {
                title.textContent = 'Edit Billing Address';
                phoneGroup.style.display = 'block';
                
                // Pre-fill billing data
                <?php if (!empty($billing_address['first_name'])): ?>
                form.first_name.value = '<?php echo esc_js($billing_address['first_name']); ?>';
                form.last_name.value = '<?php echo esc_js($billing_address['last_name']); ?>';
                form.company.value = '<?php echo esc_js($billing_address['company']); ?>';
                form.address_1.value = '<?php echo esc_js($billing_address['address_1']); ?>';
                form.address_2.value = '<?php echo esc_js($billing_address['address_2']); ?>';
                form.city.value = '<?php echo esc_js($billing_address['city']); ?>';
                form.state.value = '<?php echo esc_js($billing_address['state']); ?>';
                form.postcode.value = '<?php echo esc_js($billing_address['postcode']); ?>';
                form.country.value = '<?php echo esc_js($billing_address['country']); ?>';
                form.phone.value = '<?php echo esc_js($billing_address['phone']); ?>';
                <?php endif; ?>
            } else {
                title.textContent = 'Edit Shipping Address';
                phoneGroup.style.display = 'none';
                
                // Pre-fill shipping data
                <?php if (!empty($shipping_address['first_name'])): ?>
                form.first_name.value = '<?php echo esc_js($shipping_address['first_name']); ?>';
                form.last_name.value = '<?php echo esc_js($shipping_address['last_name']); ?>';
                form.company.value = '<?php echo esc_js($shipping_address['company']); ?>';
                form.address_1.value = '<?php echo esc_js($shipping_address['address_1']); ?>';
                form.address_2.value = '<?php echo esc_js($shipping_address['address_2']); ?>';
                form.city.value = '<?php echo esc_js($shipping_address['city']); ?>';
                form.state.value = '<?php echo esc_js($shipping_address['state']); ?>';
                form.postcode.value = '<?php echo esc_js($shipping_address['postcode']); ?>';
                form.country.value = '<?php echo esc_js($shipping_address['country']); ?>';
                <?php endif; ?>
            }
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Clear messages and hide loading
            const modal = document.getElementById(modalId);
            const successMsg = modal.querySelector('.wpz-success-message');
            const errorMsg = modal.querySelector('.wpz-error-message');
            const loading = modal.querySelector('.wpz-loading');
            
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
            if (loading) loading.style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.wpz-modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        }

        // Form submissions
        document.getElementById('accountForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const loading = document.getElementById('account-loading');
            const successMsg = document.getElementById('account-success');
            const errorMsg = document.getElementById('account-error');
            
            // Show loading
            loading.style.display = 'block';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            const formData = new FormData(this);
            formData.append('action', 'wpz_update_profile');
            formData.append('nonce', '<?php echo wp_create_nonce('wpz_update_profile'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success) {
                    successMsg.textContent = data.data.message;
                    successMsg.style.display = 'block';
                    
                    // Update display
                    document.getElementById('display-name').textContent = formData.get('display_name');
                    document.getElementById('user-email').innerHTML = formData.get('email') + 
                        <?php if ($email_verified): ?>'<span class="wpz-verified-badge">✓ Validated</span>'<?php else: ?>''<?php endif; ?>;
                    
                    setTimeout(() => closeModal('accountModal'), 2000);
                } else {
                    errorMsg.textContent = data.data.message;
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.style.display = 'block';
            });
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const loading = document.getElementById('password-loading');
            const successMsg = document.getElementById('password-success');
            const errorMsg = document.getElementById('password-error');
            
            // Validate passwords match
            const newPass = this.new_password.value;
            const confirmPass = this.confirm_password.value;
            
            if (newPass !== confirmPass) {
                errorMsg.textContent = 'New passwords do not match.';
                errorMsg.style.display = 'block';
                return;
            }
            
            if (newPass.length < 6) {
                errorMsg.textContent = 'Password must be at least 6 characters long.';
                errorMsg.style.display = 'block';
                return;
            }
            
            // Show loading
            loading.style.display = 'block';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            const formData = new FormData(this);
            formData.append('action', 'wpz_change_password');
            formData.append('nonce', '<?php echo wp_create_nonce('wpz_change_password'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success) {
                    successMsg.textContent = data.data.message;
                    successMsg.style.display = 'block';
                    this.reset();
                    setTimeout(() => closeModal('passwordModal'), 2000);
                } else {
                    errorMsg.textContent = data.data.message;
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.style.display = 'block';
            });
        });

        document.getElementById('addressForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const loading = document.getElementById('address-loading');
            const successMsg = document.getElementById('address-success');
            const errorMsg = document.getElementById('address-error');
            
            // Show loading
            loading.style.display = 'block';
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            const formData = new FormData(this);
            formData.append('action', 'wpz_update_address');
            formData.append('nonce', '<?php echo wp_create_nonce('wpz_update_address'); ?>');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                
                if (data.success) {
                    successMsg.textContent = data.data.message;
                    successMsg.style.display = 'block';
                    setTimeout(() => {
                        closeModal('addressModal');
                        location.reload(); // Reload to show updated address
                    }, 2000);
                } else {
                    errorMsg.textContent = data.data.message;
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.style.display = 'block';
            });
        });
    </script>
    
    <?php
    return ob_get_clean();
}

// AJAX Handler for Profile Update
function wpz_handle_profile_update() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'wpz_update_profile')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Security check failed.']
        ]));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'You must be logged in to update your profile.']
        ]));
    }
    
    $user_id = get_current_user_id();
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $display_name = sanitize_text_field($_POST['display_name']);
    $email = sanitize_email($_POST['email']);
    
    // Validate email
    if (!is_email($email)) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Please enter a valid email address.']
        ]));
    }
    
    // Check if email is already used by another user
    $email_exists = email_exists($email);
    if ($email_exists && $email_exists != $user_id) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'This email address is already registered to another account.']
        ]));
    }
    
    // Update user data
    $user_data = [
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $display_name,
        'user_email' => $email
    ];
    
    $result = wp_update_user($user_data);
    
    if (is_wp_error($result)) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Failed to update profile: ' . $result->get_error_message()]
        ]));
    }
    
    // Update WooCommerce customer data
    $customer = new WC_Customer($user_id);
    $customer->set_first_name($first_name);
    $customer->set_last_name($last_name);
    $customer->set_email($email);
    $customer->save();
    
    wp_die(json_encode([
        'success' => true,
        'data' => ['message' => 'Profile updated successfully!']
    ]));
}

// AJAX Handler for Password Change
function wpz_handle_password_change() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'wpz_change_password')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Security check failed.']
        ]));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'You must be logged in to change your password.']
        ]));
    }
    
    $user_id = get_current_user_id();
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $user = get_user_by('ID', $user_id);
    if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Current password is incorrect.']
        ]));
    }
    
    // Validate new password
    if (strlen($new_password) < 6) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'New password must be at least 6 characters long.']
        ]));
    }
    
    // Update password
    wp_set_password($new_password, $user_id);
    
    wp_die(json_encode([
        'success' => true,
        'data' => ['message' => 'Password changed successfully!']
    ]));
}

// AJAX Handler for Address Update
function wpz_handle_address_update() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'wpz_update_address')) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Security check failed.']
        ]));
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'You must be logged in to update your address.']
        ]));
    }
    
    $user_id = get_current_user_id();
    $address_type = sanitize_text_field($_POST['address_type']);
    
    if (!in_array($address_type, ['billing', 'shipping'])) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Invalid address type.']
        ]));
    }
    
    // Sanitize address data
    $address_data = [
        'first_name' => sanitize_text_field($_POST['first_name']),
        'last_name' => sanitize_text_field($_POST['last_name']),
        'company' => sanitize_text_field($_POST['company']),
        'address_1' => sanitize_text_field($_POST['address_1']),
        'address_2' => sanitize_text_field($_POST['address_2']),
        'city' => sanitize_text_field($_POST['city']),
        'state' => sanitize_text_field($_POST['state']),
        'postcode' => sanitize_text_field($_POST['postcode']),
        'country' => sanitize_text_field($_POST['country'])
    ];
    
    // Add phone for billing address
    if ($address_type === 'billing') {
        $address_data['phone'] = sanitize_text_field($_POST['phone']);
    }
    
    // Validation
    if (empty($address_data['first_name']) || empty($address_data['last_name'])) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'First name and last name are required.']
        ]));
    }
    
    if (empty($address_data['address_1']) || empty($address_data['city']) || empty($address_data['country'])) {
        wp_die(json_encode([
            'success' => false,
            'data' => ['message' => 'Address, city, and country are required.']
        ]));
    }
    
    // Update customer address
    $customer = new WC_Customer($user_id);
    
    foreach ($address_data as $key => $value) {
        $method = "set_{$address_type}_{$key}";
        if (method_exists($customer, $method)) {
            $customer->$method($value);
        }
    }
    
    $customer->save();
    
    wp_die(json_encode([
        'success' => true,
        'data' => ['message' => ucfirst($address_type) . ' address updated successfully!']
    ]));
}

/**
 * Add custom CSS to wp_head (optional - you can also include this in your theme's CSS file)
 */
add_action('wp_head', 'wppluginzone_profile_custom_css');
function wppluginzone_profile_custom_css() {
    if (has_shortcode(get_post()->post_content ?? '', 'wppluginzone_user_dashboard_profile')) {
        ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <?php
    }
}

/**
 * Enqueue Font Awesome (if not already loaded by your theme)
 */
add_action('wp_enqueue_scripts', 'wppluginzone_enqueue_fontawesome');
function wppluginzone_enqueue_fontawesome() {
    if (has_shortcode(get_post()->post_content ?? '', 'wppluginzone_user_dashboard_profile')) {
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    }
}
?>

<?php
/**
 * WooCommerce User Dashboard Orders Shortcode
 * Shortcode: [wppluginzone_user_dashboard_orders]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function wppluginzone_user_dashboard_orders_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="wc-user-orders-error"><p>Please log in to view your orders.</p></div>';
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return '<div class="wc-user-orders-error"><p>WooCommerce is not active.</p></div>';
    }

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'limit' => 10,
        'status' => 'any',
        'show_images' => 'yes',
        'collapse_details' => 'yes'
    ), $atts, 'wppluginzone_user_dashboard_orders');

    // Get current user ID
    $user_id = get_current_user_id();

    // Get user orders
    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'limit' => intval($atts['limit']),
        'status' => $atts['status'],
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    if (empty($customer_orders)) {
        return '<div class="wc-user-orders-empty" style="text-align:center;"><p>No orders found.</p></div>';
    }

    // Start output buffering
    ob_start();
    ?>
    
    <div class="col-md col-xs-12 accContiner">
        <div class="wc-user-orders-wrapper">
            <div class="mb-3">
                <div class="breadcrumbs">
                    <a href="<?php echo wc_get_page_permalink('myaccount'); ?>" class="">
                        <span class="acc-header-links">Dashboard <i class="fas fa-angle-double-right"></i></span>
                    </a>
                    <a href="<?php echo wc_get_page_permalink('myaccount'); ?>" class="">
                        <span class="acc-header-links">My Account <i class="fas fa-angle-double-right"></i></span>
                    </a>
                    <span class="acc-header-links ctaColor lastChild">Orders</span>
                </div>
                
                <div class="wc-orders-section">
                    <p class="section-heading">Orders</p>
                    
                    <div style="overflow-x: auto;">
                        <table class="table acc-table acc-order-table">
                            <thead>
                                <tr class="acc-table-head">
                                    <th scope="col" class="acc-table-head-td sortLink" style="width: 6rem !important;">
                                        <span>Order No.</span>
                                    </th>
                                    <th scope="col" class="acc-table-head-td" style="width: 6rem !important;">Total</th>
                                    <th scope="col" class="acc-table-head-td sortLink" style="width: 6rem !important;">
                                        <span>Status</span>
                                    </th>
                                    <th scope="col" class="acc-table-head-td sortLink" style="width: 6rem !important;">
                                        <span>Payment</span>
                                    </th>
                                    <th scope="col" class="acc-table-head-td sortLink" style="width: 6rem !important;">
                                        <span>Order Date</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_orders as $order): ?>
                                    <?php
                                    $order_id = $order->get_id();
                                    $order_status = $order->get_status();
                                    $order_total = $order->get_formatted_order_total();
                                    $order_date = $order->get_date_created();
                                    $payment_status = $order->is_paid() ? 'Paid' : 'Pending';
                                    $status_class = wppluginzone_get_status_class($order_status);
                                    $payment_class = $order->is_paid() ? 'green' : 'orange';
                                    ?>
                                    
                                    <tr class="acc-table-body">
                                        <td class="acc-table-body-td position-relative">
                                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="acc-table-body-td-link" target="_self">
                                                <?php echo esc_html($order_id); ?>
                                            </a>
                                        </td>
                                        <td class="acc-table-body-td position-relative" style="width: 11rem !important;">
                                            <?php echo $order_total; ?>
                                        </td>
                                        <td class="acc-table-body-td" title="<?php echo esc_attr(ucfirst($order_status)); ?>" style="padding-right: 0px; padding-left: 0px;">
                                            <span class="visual-indicator <?php echo esc_attr($status_class); ?>"></span>
                                            <span><?php echo esc_html(ucfirst($order_status)); ?></span>
                                        </td>
                                        <td class="acc-table-body-td" title="<?php echo esc_attr($payment_status); ?>">
                                            <span class="visual-indicator <?php echo esc_attr($payment_class); ?>"></span>
                                            <span><?php echo esc_html($payment_status); ?></span>
                                        </td>
                                        <td class="acc-table-body-td" title="<?php echo esc_attr($order_date->format('M j, Y g:i:s A')); ?>" style="width: 10rem !important;">
                                            <?php echo esc_html($order_date->format('M j, Y')); ?>
                                        </td>
                                    </tr>      
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .wc-user-orders-wrapper {
        font-family: Arial, sans-serif;
    }
    
    .breadcrumbs {
        margin-bottom: 1rem;
    }
    
    .acc-header-links {
        color: #333;
        text-decoration: none;
        margin-right: 0.5rem;
    }
    
    .acc-header-links.ctaColor {
        color: #007cba;
        font-weight: bold;
    }
    
    .section-heading {
        font-size: 2rem;
        margin-bottom: 1rem;
        color: #333;
    }
    
    .acc-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .acc-table-head {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    
    .acc-table-head-td {
        padding: 12px 8px;
        font-weight: bold;
        text-align: left;
        border-right: 1px solid #dee2e6;
    }
    
    .acc-table-body-td {
        padding: 12px 8px;
        border-bottom: 1px solid #dee2e6;
        border-right: 1px solid #dee2e6;
        vertical-align: top;
    }
    
    .acc-table-body-td-link {
        color: #007cba;
        text-decoration: none;
        font-weight: bold;
    }
    
    .acc-table-body-td-link:hover {
        text-decoration: underline;
    }
    
    .visual-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .visual-indicator.green {
        background-color: #28a745;
    }
    
    .visual-indicator.orange {
        background-color: #ffc107;
    }
    
    .visual-indicator.red {
        background-color: #dc3545;
    }
    
    .visual-indicator.blue {
        background-color: #007bff;
    }
    
    .listHover {
        background-color: #f8f9fa;
    }
    
    .order-items-details {
        padding: 1rem;
    }
    
    .rowList {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
    }
    
    .columnListLeft {
        flex: 1;
        min-width: 200px;
    }
    
    .columnListRight {
        flex: 2;
        min-width: 300px;
    }
    
    .order-items-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .product-image-thumb img {
        max-width: 65px;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .copy-filename {
        margin-bottom: 0.5rem;
    }
    
    .wc-user-orders-error,
    .wc-user-orders-empty {
        padding: 2rem;
        text-align: center;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin: 1rem 0;
    }
    
    .colExpAccordian {
        margin-bottom: 1rem;
    }
    
    .accordion-wrapper {
        cursor: pointer;
        padding: 0.5rem 0;
    }
    
    .accordion {
        display: inline-block;
        margin-left: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .acc-table {
            font-size: 0.9rem;
        }
        
        .acc-table-head-td,
        .acc-table-body-td {
            padding: 8px 4px;
        }
        
        .rowList {
            flex-direction: column;
        }
        
        .order-items-gallery {
            justify-content: center;
        }
    }
    </style>

    <?php
    return ob_get_clean();
}

// Helper function to get status CSS class
function wppluginzone_get_status_class($status) {
    switch ($status) {
        case 'completed':
        case 'processing':
            return 'green';
        case 'on-hold':
        case 'pending':
            return 'orange';
        case 'cancelled':
        case 'refunded':
        case 'failed':
            return 'red';
        default:
            return 'blue';
    }
}

// Register the shortcode
add_shortcode('wppluginzone_user_dashboard_orders', 'wppluginzone_user_dashboard_orders_shortcode');

?>

<?php
/**
 * WordPress Shortcode: [wppluginzone_user_dashboard_files]
 * Professional File Management Dashboard for logged-in users
 */

class WPPluginZone_User_Dashboard_Files {
    
    private $upload_dir;
    
    public function __construct() {
        add_shortcode('wppluginzone_user_dashboard_files', array($this, 'render_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_upload_user_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_delete_user_file', array($this, 'handle_file_delete'));
        add_action('wp_ajax_create_user_folder', array($this, 'handle_folder_create'));
        add_action('wp_ajax_move_user_file', array($this, 'handle_file_move'));
        add_action('wp_ajax_rename_folder', array($this, 'handle_folder_rename'));
        add_action('wp_ajax_delete_folder', array($this, 'handle_folder_delete'));
        add_action('wp_ajax_load_folder_files', array($this, 'handle_load_folder_files'));
        
        // Set upload directory
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/user-files/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }
    
    public function enqueue_scripts() {
        if (is_user_logged_in()) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            wp_enqueue_style('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css');
            wp_enqueue_script('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js', array('jquery'));
        }
    }
    
    public function render_dashboard($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">Please log in to access your files.</div>';
        }
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Get user files and folders
        $files = $this->get_user_files($user_id);
        $folders = $this->get_user_folders($user_id);
        $storage_used = $this->calculate_storage_usage($user_id);
        $storage_limit = $this->get_storage_limit($user_id); // in MB
        
        ob_start();
        ?>
        
        <div class="wppz-file-dashboard">
            <!-- Breadcrumbs -->
            <div class="breadcrumbs mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo home_url('/dashboard'); ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo home_url('/my-account'); ?>">My Account</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Files</li>
                    </ol>
                </nav>
            </div>
            
            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="loading-overlay d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <!-- Modals -->
            <?php echo $this->render_modals(); ?>
            
            <!-- Main Content -->
            <div id="dropImages">
                <div class="container" id="dvImageTop">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="section-heading mb-0">My Files</h2>
                        <div class="storage-info d-none d-md-block">
                            <small class="text-muted">Storage: <?php echo number_format($storage_used, 1); ?> MB / <?php echo $storage_limit; ?> MB</small>
                            <div class="progress mt-1" style="width: 150px; height: 6px;">
                                <div class="progress-bar <?php echo ($storage_used/$storage_limit > 0.8) ? 'bg-danger' : 'bg-success'; ?>" 
                                     style="width: <?php echo min(100, ($storage_used/$storage_limit)*100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Upload Section -->
                    <div class="container d-block d-sm-none text-center mt-3 mb-3">
                        <div class="upload-section">
                            <label class="btn btn-primary upload-btn">
                                <i class="fas fa-upload me-2"></i>Upload Files
                                <input type="file" id="fileInput" class="d-none" multiple accept=".jpg,.jpeg,.eps,.png,.tif,.tiff,.ai,.psd,.gif,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                            </label>
                            <button class="btn btn-secondary ms-2" id="addFolderBtn">
                                <i class="fas fa-folder-plus me-2"></i>New Folder
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <div class="row">
                        <!-- Left Panel - Folders -->
                        <div class="col-md-4" id="dvImageLeft">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><i class="fas fa-folder me-2"></i>Folders</h6>
                                    <button class="btn btn-sm btn-outline-primary d-none d-md-block" id="addFolderBtnDesktop">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="folder-list">
                                        <div class="folder-item border-bottom p-3 active" data-folder-id="-1">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-folder-open text-warning me-3" style="font-size: 1.2rem;"></i>
                                                <span class="folder-name">All Files</span>
                                                <small class="text-muted ms-auto"><?php echo count($files); ?></small>
                                            </div>
                                        </div>
                                        <?php foreach ($folders as $folder): ?>
                                        <div class="folder-item border-bottom p-3" data-folder-id="<?php echo esc_attr($folder->id); ?>">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center flex-grow-1">
                                                    <i class="fas fa-folder text-primary me-3" style="font-size: 1.2rem;"></i>
                                                    <span class="folder-name"><?php echo esc_html($folder->name); ?></span>
                                                    <small class="text-muted ms-auto me-2"><?php echo $this->get_folder_file_count($folder->id); ?></small>
                                                </div>
                                                <div class="folder-actions">
                                                    <i class="fas fa-edit text-secondary me-2 folder-edit" 
                                                       data-folder-id="<?php echo esc_attr($folder->id); ?>" 
                                                       data-folder-name="<?php echo esc_attr($folder->name); ?>"
                                                       title="Rename" style="cursor: pointer;"></i>
                                                    <i class="fas fa-trash text-danger folder-delete" 
                                                       data-folder-id="<?php echo esc_attr($folder->id); ?>" 
                                                       title="Delete" style="cursor: pointer;"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Desktop Upload Section -->
                            <div class="d-none d-md-block mt-4">
                                <div class="card">
                                    <div>
                                        <div class="upload-area border border-dashed p-4 rounded" id="uploadDropArea">
                                            <i class="fas fa-cloud-upload-alt text-muted mb-3" style="font-size: 3rem;"></i>
                                            <p class="drag-text mb-3">Drag & drop files here</p>
                                            <label class="btn btn-primary upload-btn">
                                                <i class="fas fa-plus me-2"></i>Choose Files
                                                <input type="file" id="fileInputDesktop" class="d-none" multiple 
                                                       accept=".jpg,.jpeg,.eps,.png,.tif,.tiff,.ai,.psd,.gif,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                                            </label>
                                            <p class="text-muted mt-3 small">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Max file size: 10MB per file
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Panel - Files -->
                        <div class="col-md-8" id="dvImageRight">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0" id="currentFolderTitle">
                                        <i class="fas fa-file me-2"></i>All Files
                                    </h6>
                                    <div class="file-actions">
                                        <button class="btn btn-sm btn-outline-danger" id="deleteSelectedBtn" disabled>
                                            <i class="fas fa-trash me-1"></i>Delete Selected
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <!-- Files Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="selectAllFiles">
                                                        </div>
                                                    </th>
                                                    <th>Name</th>
                                                    <th class="d-none d-md-table-cell" style="width: 100px;">Size</th>
                                                    <th class="d-none d-lg-table-cell text-center" style="width: 120px;">Date</th>
                                                    <th style="width: 80px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="filesTableBody">
                                                <?php if (empty($files)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-5">
                                                        <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                                        <p class="mb-0">No files uploaded yet</p>
                                                        <small>Start by uploading your first file!</small>
                                                    </td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($files as $file): ?>
                                                <tr class="file-row" data-file-id="<?php echo esc_attr($file->id); ?>" data-folder-id="<?php echo esc_attr($file->folder_id); ?>">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input file-checkbox" type="checkbox" value="<?php echo esc_attr($file->id); ?>">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="file-thumbnail me-3">
                                                                <?php echo $this->get_file_thumbnail($file); ?>
                                                            </div>
                                                            <div>
                                                                <div class="file-name fw-medium" title="<?php echo esc_attr($file->original_name); ?>">
                                                                    <?php echo esc_html($file->original_name); ?>
                                                                </div>
                                                                <small class="text-muted d-md-none"><?php echo $this->format_file_size($file->file_size); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="d-none d-md-table-cell">
                                                        <span class="badge bg-light text-dark"><?php echo $this->format_file_size($file->file_size); ?></span>
                                                    </td>
                                                    <td class="d-none d-lg-table-cell text-center">
                                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($file->upload_date)); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="<?php echo esc_url($file->file_url); ?>" target="_blank" 
                                                               class="btn btn-outline-primary btn-sm" title="View/Download">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button class="btn btn-outline-danger btn-sm delete-file" 
                                                                    data-file-id="<?php echo esc_attr($file->id); ?>" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Info Section -->
                <div class="container mt-4">
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>File Manager Features</h6>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Organize files in custom folders
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Drag & drop file uploads
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php echo $storage_limit; ?>MB storage space included
                                        </div>
                                        <div class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Multiple file format support
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="storage-usage-circle">
                                        <div class="progress-circle" data-percentage="<?php echo min(100, ($storage_used/$storage_limit)*100); ?>">
                                            <span><?php echo number_format(($storage_used/$storage_limit)*100, 1); ?>%</span>
                                        </div>
                                        <small class="text-muted d-block mt-2">Storage Used</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="fw-bold text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Note: Files remain in "All Files" until moved to a specific folder.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .wppz-file-dashboard {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-heading {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .folder-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 0;
        }
        
        .folder-item:hover {
            background-color: #f8f9fa;
        }
        
        .folder-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #007bff;
        }
        
        .upload-area {
            background: #f8f9fa;
            transition: all 0.3s ease;
            min-height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: #007bff !important;
            background-color: #e3f2fd;
        }
        
        .file-thumbnail {
            width: 45px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .file-name {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .folder-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        
        .folder-item:hover .folder-actions {
            opacity: 1;
        }
        
        .drag-text {
            color: #666;
            font-size: 1rem;
        }
        
        .upload-btn {
            font-weight: 500;
        }
        
        .delete-file:hover,
        .folder-edit:hover,
        .folder-delete:hover {
            opacity: 0.7;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .card {
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            font-weight: 600;
        }
        
        .storage-info {
            text-align: right;
        }
        
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#007bff var(--percentage, 0%), #e9ecef 0%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
        }
        
        .progress-circle::before {
            content: '';
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }
        
        .progress-circle span {
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .file-name {
                max-width: 150px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let currentFolderId = -1;
            
            // Initialize progress circle
            initProgressCircle();
            
            // File upload handling
            $('#fileInput, #fileInputDesktop').on('change', function() {
                if (this.files.length > 0) {
                    handleFileUpload(this.files);
                }
            });
            
            // Drag and drop functionality
            const uploadArea = $('#uploadDropArea, #dropImages');
            
            uploadArea.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#uploadDropArea').addClass('dragover');
            });
            
            uploadArea.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#uploadDropArea').removeClass('dragover');
            });
            
            uploadArea.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#uploadDropArea').removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileUpload(files);
                }
            });
            
            // Folder selection
            $(document).on('click', '.folder-item', function() {
                $('.folder-item').removeClass('active');
                $(this).addClass('active');
                currentFolderId = $(this).data('folder-id');
                
                const folderName = $(this).find('.folder-name').text();
                $('#currentFolderTitle').html('<i class="fas fa-folder me-2"></i>' + folderName);
                
                loadFolderFiles(currentFolderId);
            });
            
            // Select all files
            $('#selectAllFiles').on('change', function() {
                $('.file-checkbox:visible').prop('checked', this.checked);
                updateDeleteButton();
            });
            
            // Individual file selection
            $(document).on('change', '.file-checkbox', function() {
                updateDeleteButton();
                
                // Update select all checkbox
                const total = $('.file-checkbox:visible').length;
                const checked = $('.file-checkbox:visible:checked').length;
                $('#selectAllFiles').prop('indeterminate', checked > 0 && checked < total);
                $('#selectAllFiles').prop('checked', checked === total);
            });
            
            // Delete selected files
            $('#deleteSelectedBtn').on('click', function() {
                const selectedFiles = $('.file-checkbox:checked').map(function() {
                    return this.value;
                }).get();
                
                if (selectedFiles.length > 0 && confirm(`Are you sure you want to delete ${selectedFiles.length} file(s)?`)) {
                    deleteFiles(selectedFiles);
                }
            });
            
            // Delete individual file
            $(document).on('click', '.delete-file', function(e) {
                e.stopPropagation();
                const fileId = $(this).data('file-id');
                if (confirm('Are you sure you want to delete this file?')) {
                    deleteFiles([fileId]);
                }
            });
            
            // Add folder buttons
            $('#addFolderBtn, #addFolderBtnDesktop').on('click', function() {
                const folderName = prompt('Enter folder name:');
                if (folderName && folderName.trim()) {
                    createFolder(folderName.trim());
                }
            });
            
            // Folder edit
            $(document).on('click', '.folder-edit', function(e) {
                e.stopPropagation();
                const folderId = $(this).data('folder-id');
                const currentName = $(this).data('folder-name');
                const newName = prompt('Enter new folder name:', currentName);
                
                if (newName && newName.trim() && newName !== currentName) {
                    renameFolder(folderId, newName.trim());
                }
            });
            
            // Folder delete
            $(document).on('click', '.folder-delete', function(e) {
                e.stopPropagation();
                const folderId = $(this).data('folder-id');
                if (confirm('Are you sure you want to delete this folder and all its files?')) {
                    deleteFolder(folderId);
                }
            });
            
            function showLoading() {
                $('#loadingOverlay').removeClass('d-none');
            }
            
            function hideLoading() {
                $('#loadingOverlay').addClass('d-none');
            }
            
            function handleFileUpload(files) {
                if (files.length === 0) return;
                
                // Validate files
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedTypes = ['image/', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument', 'application/zip', 'application/x-rar'];
                
                for (let i = 0; i < files.length; i++) {
                    if (files[i].size > maxSize) {
                        alert(`File "${files[i].name}" is too large. Maximum size is 10MB.`);
                        return;
                    }
                    
                    const isValidType = allowedTypes.some(type => files[i].type.startsWith(type)) || 
                                       files[i].type === 'application/x-photoshop' ||
                                       files[i].type === 'application/postscript';
                    
                    if (!isValidType) {
                        alert(`File type "${files[i].type}" is not allowed for file "${files[i].name}".`);
                        return;
                    }
                }
                
                showLoading();
                
                const formData = new FormData();
                formData.append('action', 'upload_user_file');
                formData.append('folder_id', currentFolderId);
                formData.append('nonce', '<?php echo wp_create_nonce("upload_user_file"); ?>');
                
                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Upload failed: ' + response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Upload failed. Please try again.');
                    }
                });
                
                // Reset file inputs
                $('#fileInput, #fileInputDesktop').val('');
            }
            
            function deleteFiles(fileIds) {
                showLoading();
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'delete_user_file',
                        file_ids: fileIds,
                        nonce: '<?php echo wp_create_nonce("delete_user_file"); ?>'
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            // Remove deleted files from UI
                            fileIds.forEach(function(fileId) {
                                $(`tr[data-file-id="${fileId}"]`).fadeOut(300, function() {
                                    $(this).remove();
                                    checkEmptyState();
                                });
                            });
                            updateDeleteButton();
                        } else {
                            alert('Delete failed: ' + response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Delete failed. Please try again.');
                    }
                });
            }
            
            function createFolder(name) {
                showLoading();
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'create_user_folder',
                        folder_name: name,
                        nonce: '<?php echo wp_create_nonce("create_user_folder"); ?>'
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Folder creation failed: ' + response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Folder creation failed. Please try again.');
                    }
                });
            }
            
            function renameFolder(folderId, newName) {
                showLoading();
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'rename_folder',
                        folder_id: folderId,
                        folder_name: newName,
                        nonce: '<?php echo wp_create_nonce("rename_folder"); ?>'
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Folder rename failed: ' + response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Folder rename failed. Please try again.');
                    }
                });
            }
            
            function deleteFolder(folderId) {
                showLoading();
                
                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'delete_folder',
                        folder_id: folderId,
                        nonce: '<?php echo wp_create_nonce("delete_folder"); ?>'
                    },
                    success: function(response) {
                        hideLoading();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Folder deletion failed: ' + response.data);
                        }
                    },
                    error: function() {
                        hideLoading();
                        alert('Folder deletion failed. Please try again.');
                    }
                });
            }
            
            function loadFolderFiles(folderId) {
                // Filter files based on folder
                if (folderId == -1) {
                    // Show all files
                    $('.file-row').show();
                } else {
                    // Show only files in selected folder
                    $('.file-row').hide();
                    $(`.file-row[data-folder-id="${folderId}"]`).show();
                }
                
                checkEmptyState();
                updateDeleteButton();
            }
            
            function updateDeleteButton() {
                const selectedCount = $('.file-checkbox:visible:checked').length;
                $('#deleteSelectedBtn').prop('disabled', selectedCount === 0);
                
                if (selectedCount > 0) {
                    $('#deleteSelectedBtn').html(`<i class="fas fa-trash me-1"></i>Delete (${selectedCount})`);
                } else {
                    $('#deleteSelectedBtn').html('<i class="fas fa-trash me-1"></i>Delete Selected');
                }
            }
            
            function checkEmptyState() {
                const visibleFiles = $('.file-row:visible').length;
                const tbody = $('#filesTableBody');
                
                if (visibleFiles === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                                <p class="mb-0">No files in this folder</p>
                                <small>Upload files or move them to this folder</small>
                            </td>
                        </tr>
                    `);
                }
            }
            
            function initProgressCircle() {
                $('.progress-circle').each(function() {
                    const percentage = $(this).data('percentage');
                    $(this).css('--percentage', percentage + '%');
                });
            }
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    private function render_modals() {
        return '
        <!-- Folder Edit Modal -->
        <div class="modal fade" id="folderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folderNameInput" maxlength="50">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="saveFolderBtn">Save</button>
                        <button type="button" class="btn btn-danger" id="deleteFolderBtn">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    private function get_user_files($user_id, $folder_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_files';
        
        if ($folder_id !== null) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d AND folder_id = %d ORDER BY upload_date DESC",
                $user_id, $folder_id
            ));
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY upload_date DESC",
            $user_id
        ));
    }
    
    private function get_user_folders($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_folders';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ));
    }
    
    private function get_folder_file_count($folder_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_files';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE folder_id = %d",
            $folder_id
        ));
    }
    
    private function calculate_storage_usage($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'user_files';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(file_size) FROM {$table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return round(($result ?: 0) / (1024 * 1024), 2); // Convert to MB
    }
    
    private function get_storage_limit($user_id) {
        // Default 100MB limit, can be customized per user
        return apply_filters('wppz_user_storage_limit', 100, $user_id);
    }
    
    private function get_file_thumbnail($file) {
        $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
            return '<img src="' . esc_url($file->file_url) . '" alt="' . esc_attr($file->original_name) . '" style="max-width: 100%; max-height: 100%; object-fit: cover; border-radius: 4px;">';
        } else {
            $icon_class = $this->get_file_icon_class($extension);
            return '<i class="' . $icon_class . '" style="font-size: 1.5rem;"></i>';
        }
    }
    
    private function get_file_icon_class($extension) {
        $icons = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'zip' => 'fas fa-file-archive text-secondary',
            'rar' => 'fas fa-file-archive text-secondary',
            '7z' => 'fas fa-file-archive text-secondary',
            'ai' => 'fas fa-palette text-info',
            'psd' => 'fas fa-palette text-info',
            'eps' => 'fas fa-palette text-info',
            'txt' => 'fas fa-file-alt text-secondary',
            'rtf' => 'fas fa-file-alt text-secondary',
            'mp3' => 'fas fa-file-audio text-success',
            'wav' => 'fas fa-file-audio text-success',
            'mp4' => 'fas fa-file-video text-danger',
            'avi' => 'fas fa-file-video text-danger',
            'mov' => 'fas fa-file-video text-danger',
        ];
        
        return $icons[$extension] ?? 'fas fa-file text-muted';
    }
    
    private function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    private function sanitize_filename($filename) {
        // Remove dangerous characters and sanitize filename
        $filename = sanitize_file_name($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
    
    private function get_unique_filename($user_id, $filename) {
        $upload_path = $this->upload_dir . $user_id . '/';
        $file_path = $upload_path . $filename;
        
        if (!file_exists($file_path)) {
            return $filename;
        }
        
        $pathinfo = pathinfo($filename);
        $name = $pathinfo['filename'];
        $extension = isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '';
        $counter = 1;
        
        while (file_exists($upload_path . $name . '_' . $counter . $extension)) {
            $counter++;
        }
        
        return $name . '_' . $counter . $extension;
    }
    
    // AJAX Handlers
    public function handle_file_upload() {
        if (!wp_verify_nonce($_POST['nonce'], 'upload_user_file') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $folder_id = intval($_POST['folder_id']);
        
        if (empty($_FILES['files'])) {
            wp_send_json_error('No files uploaded');
        }
        
        // Check storage limit
        $current_usage = $this->calculate_storage_usage($user_id);
        $storage_limit = $this->get_storage_limit($user_id);
        
        $upload_path = $this->upload_dir . $user_id . '/';
        if (!file_exists($upload_path)) {
            wp_mkdir_p($upload_path);
        }
        
        $upload_url = wp_upload_dir()['baseurl'] . '/user-files/' . $user_id . '/';
        $uploaded_files = array();
        $total_size = 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_files';
        
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $file_size = $_FILES['files']['size'][$key];
            $original_name = sanitize_text_field($_FILES['files']['name'][$key]);
            $mime_type = $_FILES['files']['type'][$key];
            
            // Check file size (10MB limit per file)
            if ($file_size > 10 * 1024 * 1024) {
                wp_send_json_error('File "' . $original_name . '" is too large. Maximum size is 10MB.');
            }
            
            // Check total storage
            $total_size += $file_size;
            if (($current_usage * 1024 * 1024 + $total_size) > ($storage_limit * 1024 * 1024)) {
                wp_send_json_error('Storage limit exceeded. Please delete some files or upgrade your plan.');
            }
            
            // Sanitize and get unique filename
            $safe_filename = $this->sanitize_filename($original_name);
            $unique_filename = $this->get_unique_filename($user_id, $safe_filename);
            $file_path = $upload_path . $unique_filename;
            
            // Move uploaded file
            if (move_uploaded_file($tmp_name, $file_path)) {
                // Insert into database
                $result = $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'folder_id' => $folder_id == -1 ? -1 : $folder_id,
                        'original_name' => $original_name,
                        'file_name' => $unique_filename,
                        'file_path' => $file_path,
                        'file_url' => $upload_url . $unique_filename,
                        'file_size' => $file_size,
                        'mime_type' => $mime_type,
                        'upload_date' => current_time('mysql')
                    ),
                    array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );
                
                if ($result) {
                    $uploaded_files[] = $original_name;
                } else {
                    // Delete file if database insert failed
                    unlink($file_path);
                }
            }
        }
        
        if (empty($uploaded_files)) {
            wp_send_json_error('No files were uploaded successfully');
        }
        
        wp_send_json_success('Uploaded ' . count($uploaded_files) . ' file(s) successfully');
    }
    
    public function handle_file_delete() {
        if (!wp_verify_nonce($_POST['nonce'], 'delete_user_file') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $file_ids = array_map('intval', $_POST['file_ids']);
        
        if (empty($file_ids)) {
            wp_send_json_error('No files selected');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_files';
        $deleted_count = 0;
        
        foreach ($file_ids as $file_id) {
            // Get file info
            $file = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND user_id = %d",
                $file_id, $user_id
            ));
            
            if ($file) {
                // Delete physical file
                if (file_exists($file->file_path)) {
                    unlink($file->file_path);
                }
                
                // Delete from database
                $result = $wpdb->delete(
                    $table_name,
                    array('id' => $file_id, 'user_id' => $user_id),
                    array('%d', '%d')
                );
                
                if ($result) {
                    $deleted_count++;
                }
            }
        }
        
        if ($deleted_count > 0) {
            wp_send_json_success('Deleted ' . $deleted_count . ' file(s) successfully');
        } else {
            wp_send_json_error('No files were deleted');
        }
    }
    
    public function handle_folder_create() {
        if (!wp_verify_nonce($_POST['nonce'], 'create_user_folder') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $folder_name = sanitize_text_field($_POST['folder_name']);
        
        if (empty($folder_name)) {
            wp_send_json_error('Folder name is required');
        }
        
        if (strlen($folder_name) > 50) {
            wp_send_json_error('Folder name is too long (max 50 characters)');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_folders';
        
        // Check if folder already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND name = %s",
            $user_id, $folder_name
        ));
        
        if ($existing > 0) {
            wp_send_json_error('A folder with this name already exists');
        }
        
        // Create folder
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'name' => $folder_name,
                'parent_id' => 0,
                'created_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );
        
        if ($result) {
            wp_send_json_success('Folder created successfully');
        } else {
            wp_send_json_error('Failed to create folder');
        }
    }
    
    public function handle_folder_rename() {
        if (!wp_verify_nonce($_POST['nonce'], 'rename_folder') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $folder_id = intval($_POST['folder_id']);
        $folder_name = sanitize_text_field($_POST['folder_name']);
        
        if (empty($folder_name)) {
            wp_send_json_error('Folder name is required');
        }
        
        if (strlen($folder_name) > 50) {
            wp_send_json_error('Folder name is too long (max 50 characters)');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_folders';
        
        // Check if folder belongs to user
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND user_id = %d",
            $folder_id, $user_id
        ));
        
        if ($existing == 0) {
            wp_send_json_error('Folder not found');
        }
        
        // Check if new name already exists
        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d AND name = %s AND id != %d",
            $user_id, $folder_name, $folder_id
        ));
        
        if ($duplicate > 0) {
            wp_send_json_error('A folder with this name already exists');
        }
        
        // Update folder name
        $result = $wpdb->update(
            $table_name,
            array('name' => $folder_name),
            array('id' => $folder_id, 'user_id' => $user_id),
            array('%s'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Folder renamed successfully');
        } else {
            wp_send_json_error('Failed to rename folder');
        }
    }
    
    public function handle_folder_delete() {
        if (!wp_verify_nonce($_POST['nonce'], 'delete_folder') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $folder_id = intval($_POST['folder_id']);
        
        global $wpdb;
        $folders_table = $wpdb->prefix . 'user_folders';
        $files_table = $wpdb->prefix . 'user_files';
        
        // Check if folder belongs to user
        $folder = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$folders_table} WHERE id = %d AND user_id = %d",
            $folder_id, $user_id
        ));
        
        if (!$folder) {
            wp_send_json_error('Folder not found');
        }
        
        // Get all files in folder and delete them
        $files = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$files_table} WHERE folder_id = %d AND user_id = %d",
            $folder_id, $user_id
        ));
        
        foreach ($files as $file) {
            // Delete physical file
            if (file_exists($file->file_path)) {
                unlink($file->file_path);
            }
            
            // Delete from database
            $wpdb->delete(
                $files_table,
                array('id' => $file->id),
                array('%d')
            );
        }
        
        // Delete folder
        $result = $wpdb->delete(
            $folders_table,
            array('id' => $folder_id, 'user_id' => $user_id),
            array('%d', '%d')
        );
        
        if ($result) {
            wp_send_json_success('Folder and all its files deleted successfully');
        } else {
            wp_send_json_error('Failed to delete folder');
        }
    }
    
    public function handle_file_move() {
        if (!wp_verify_nonce($_POST['nonce'], 'move_user_file') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $file_id = intval($_POST['file_id']);
        $folder_id = intval($_POST['folder_id']);
        
        global $wpdb;
        $files_table = $wpdb->prefix . 'user_files';
        $folders_table = $wpdb->prefix . 'user_folders';
        
        // Verify file belongs to user
        $file = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$files_table} WHERE id = %d AND user_id = %d",
            $file_id, $user_id
        ));
        
        if (!$file) {
            wp_send_json_error('File not found');
        }
        
        // Verify folder belongs to user (if not -1 for uncategorized)
        if ($folder_id != -1) {
            $folder = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$folders_table} WHERE id = %d AND user_id = %d",
                $folder_id, $user_id
            ));
            
            if (!$folder) {
                wp_send_json_error('Folder not found');
            }
        }
        
        // Move file
        $result = $wpdb->update(
            $files_table,
            array('folder_id' => $folder_id),
            array('id' => $file_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('File moved successfully');
        } else {
            wp_send_json_error('Failed to move file');
        }
    }
    
    public function handle_load_folder_files() {
        if (!wp_verify_nonce($_POST['nonce'], 'load_folder_files') || !is_user_logged_in()) {
            wp_send_json_error('Security check failed');
        }
        
        $user_id = get_current_user_id();
        $folder_id = intval($_POST['folder_id']);
        
        $files = $this->get_user_files($user_id, $folder_id);
        
        wp_send_json_success(array('files' => $files));
    }
}

// Initialize the class
new WPPluginZone_User_Dashboard_Files();

/**
 * Database Tables Creation
 * Run this once to create the necessary tables
 */
function wppz_create_user_files_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // User files table
    $files_table = $wpdb->prefix . 'user_files';
    $files_sql = "CREATE TABLE $files_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        folder_id int(11) DEFAULT -1,
        original_name varchar(255) NOT NULL,
        file_name varchar(255) NOT NULL,
        file_path varchar(500) NOT NULL,
        file_url varchar(500) NOT NULL,
        file_size bigint(20) NOT NULL,
        mime_type varchar(100) NOT NULL,
        upload_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY folder_id (folder_id),
        KEY upload_date (upload_date)
    ) $charset_collate;";
    
    // User folders table
    $folders_table = $wpdb->prefix . 'user_folders';
    $folders_sql = "CREATE TABLE $folders_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        name varchar(100) NOT NULL,
        parent_id int(11) DEFAULT 0,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY parent_id (parent_id),
        UNIQUE KEY unique_folder_name (user_id, name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($files_sql);
    dbDelta($folders_sql);
}

// Hook to create tables on plugin activation
register_activation_hook(__FILE__, 'wppz_create_user_files_tables');

/**
 * Cleanup function to delete old uncategorized files (72 hours)
 */
function wppz_cleanup_old_files() {
    global $wpdb;
    
    $files_table = $wpdb->prefix . 'user_files';
    $cutoff_date = date('Y-m-d H:i:s', strtotime('-72 hours'));
    
    // Get old uncategorized files
    $old_files = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$files_table} WHERE folder_id = -1 AND upload_date < %s",
        $cutoff_date
    ));
    
    foreach ($old_files as $file) {
        // Delete physical file
        if (file_exists($file->file_path)) {
            unlink($file->file_path);
        }
        
        // Delete from database
        $wpdb->delete(
            $files_table,
            array('id' => $file->id),
            array('%d')
        );
    }
}

// Schedule cleanup to run daily
if (!wp_next_scheduled('wppz_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'wppz_daily_cleanup');
}
add_action('wppz_daily_cleanup', 'wppz_cleanup_old_files');

// Add admin menu for file management
function wppz_add_admin_menu() {
    add_management_page(
        'User File Manager',
        'User Files',
        'manage_options',
        'wppz-user-files',
        'wppz_admin_page'
    );
}
add_action('admin_menu', 'wppz_add_admin_menu');

function wppz_admin_page() {
    global $wpdb;
    
    $files_table = $wpdb->prefix . 'user_files';
    $folders_table = $wpdb->prefix . 'user_folders';
    
    $total_files = $wpdb->get_var("SELECT COUNT(*) FROM {$files_table}");
    $total_folders = $wpdb->get_var("SELECT COUNT(*) FROM {$folders_table}");
    $total_storage = $wpdb->get_var("SELECT SUM(file_size) FROM {$files_table}");
    $total_storage_mb = round(($total_storage ?: 0) / (1024 * 1024), 2);
    
    echo '<div class="wrap">';
    echo '<h1>User File Manager Statistics</h1>';
    echo '<div class="card" style="max-width: 600px;">';
    echo '<div class="card-body">';
    echo '<h3>Overview</h3>';
    echo '<p><strong>Total Files:</strong> ' . number_format($total_files) . '</p>';
    echo '<p><strong>Total Folders:</strong> ' . number_format($total_folders) . '</p>';
    echo '<p><strong>Total Storage Used:</strong> ' . number_format($total_storage_mb, 2) . ' MB</p>';
    echo '<hr>';
    echo '<p><em>Use shortcode [wppluginzone_user_dashboard_files] to display the file manager on any page.</em></p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>
<?php
/**
 * WordPress Shortcode: User Dashboard Saved Designs
 * Shortcode: [wppluginzone_user_dashboard_saved_design]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main shortcode function for displaying user's saved designs
 */
function wppluginzone_user_dashboard_saved_design_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="wppz-error">Please log in to view your saved designs.</div>';
    }
    
    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'per_page' => 10,
        'show_pagination' => 'yes',
        'allow_delete' => 'yes',
        'show_checkboxes' => 'yes'
    ), $atts, 'wppluginzone_user_dashboard_saved_design');
    
    // Get saved designs for current user
    $saved_designs = get_user_saved_designs($user_id, $atts['per_page']);
    
    // Start output buffering
    ob_start();
    ?>
    
    <div class="col-md col-xs-12 accContiner wppz-saved-designs">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs wppz-breadcrumbs">
            <a href="<?php echo esc_url(home_url('/user/dashboard')); ?>">
                <span class="acc-header-links">Dashboard <i class="fas fa-angle-double-right"></i></span>
            </a>
            <a href="<?php echo esc_url(home_url('/user/account')); ?>">
                <span class="acc-header-links">My Account <i class="fas fa-angle-double-right"></i></span>
            </a>
            <a href="<?php echo esc_url(home_url('/user/account/designs')); ?>" class="router-link-active">
                <span class="acc-header-links ctaColor lastChild">Saved Designs</span>
            </a>
        </div>

        <!-- Modals -->
        <?php echo get_design_modals(); ?>

        <!-- Page Title -->
        <p class="section-heading">Saved Designs</p>

        <?php if ($atts['show_checkboxes'] === 'yes' && $atts['allow_delete'] === 'yes'): ?>
        <!-- Bulk Actions -->
        <div class="mt-2">
            <span class="acc-bold-action p-0 wppz-delete-selected" style="color: rgb(213, 213, 213); cursor: pointer;">
                Delete Selected
            </span>
        </div>
        <?php endif; ?>

        <!-- Designs Table -->
        <div style="overflow-x: auto;">
            <table class="table acc-table wppz-designs-table">
                <thead>
                    <tr class="acc-table-head">
                        <?php if ($atts['show_checkboxes'] === 'yes'): ?>
                        <th scope="col" class="acc-table-head-td" style="width: 4rem !important;">
                            <div class="custom-control custom-checkbox mt-2" style="min-height: 1.5rem;">
                                <input class="custom-control-input" type="checkbox" id="designsSelectAll" value="true">
                                <label class="custom-control-label termsLabel" for="designsSelectAll" style="margin-left: 1rem;"></label>
                            </div>
                        </th>
                        <?php endif; ?>
                        <th scope="col" class="acc-table-head-td sortLink wppz-sort" data-sort="name" style="width: 20rem !important;">
                            <span>Name</span>
                        </th>
                        <th scope="col" class="acc-table-head-td sortLink wppz-sort" data-sort="product">
                            <span>Product</span>
                        </th>
                        <th scope="col" class="acc-table-head-td sortLink wppz-sort" data-sort="date">
                            <span>Created Date</span>
                        </th>
                        <?php if ($atts['allow_delete'] === 'yes'): ?>
                        <th scope="col" class="acc-table-head-td acc-disabled-span" style="width: 4rem !important;"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($saved_designs)): ?>
                        <?php foreach ($saved_designs as $index => $design): ?>
                        <tr class="acc-table-body" data-design-id="<?php echo esc_attr($design['id']); ?>">
                            <?php if ($atts['show_checkboxes'] === 'yes'): ?>
                            <td class="acc-table-body-td lt18" style="width: 4rem !important;">
                                <div class="custom-control custom-checkbox mt-2" style="min-height: 1.5rem;">
                                    <input class="custom-control-input wppz-design-checkbox" type="checkbox" 
                                           id="designsSelect<?php echo $index; ?>" value="<?php echo esc_attr($design['id']); ?>">
                                    <label class="custom-control-label termsLabel" 
                                           for="designsSelect<?php echo $index; ?>" style="margin-left: 1rem;"></label>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td class="acc-table-body-td-link wppz-design-name" 
                                title="<?php echo esc_attr($design['name']); ?>" 
                                style="width: 20rem !important; cursor: pointer;" 
                                data-design-id="<?php echo esc_attr($design['id']); ?>">
                                <?php echo esc_html($design['name']); ?>
                            </td>
                            <td class="acc-table-body-td">
                                <?php echo esc_html($design['product_type']); ?>
                            </td>
                            <td class="acc-table-body-td" title="<?php echo esc_attr($design['created_date_full']); ?>">
                                <?php echo esc_html($design['created_date_short']); ?>
                            </td>
                            <?php if ($atts['allow_delete'] === 'yes'): ?>
                            <td class="acc-table-body-td text-right" style="width: 4rem !important;">
                                <i class="fas fa-times wppz-delete-design" 
                                   style="color: rgb(216, 216, 216); cursor: pointer;" 
                                   data-design-id="<?php echo esc_attr($design['id']); ?>"
                                   title="Delete design"></i>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="acc-table-body">
                            <td colspan="<?php echo ($atts['show_checkboxes'] === 'yes' && $atts['allow_delete'] === 'yes') ? '5' : '3'; ?>" 
                                class="acc-table-body-td text-center">
                                <p>No saved designs found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($atts['show_pagination'] === 'yes'): ?>
        <!-- Pagination would go here -->
        <div class="wppz-pagination">
            <?php echo get_designs_pagination($user_id, $atts['per_page']); ?>
        </div>
        <?php endif; ?>
    </div>

    <style>
    .wppz-saved-designs {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .wppz-saved-designs .breadcrumbs a {
        text-decoration: none;
        color: #666;
    }
    
    .wppz-saved-designs .acc-header-links {
        margin: 0 5px;
    }
    
    .wppz-saved-designs .ctaColor {
        color: #007cba !important;
    }
    
    .wppz-saved-designs .section-heading {
        font-size: 2rem;
        margin: 2rem 0 1rem 0;
        color: #333;
    }
    
    .wppz-saved-designs .acc-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .wppz-saved-designs .acc-table-head {
        background: #f8f9fa;
    }
    
    .wppz-saved-designs .acc-table-head-td {
        padding: 15px;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .wppz-saved-designs .acc-table-body-td {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }
    
    .wppz-saved-designs .acc-table-body:hover {
        background-color: #f8f9fa;
    }
    
    .wppz-saved-designs .sortLink {
        cursor: pointer;
        user-select: none;
    }
    
    .wppz-saved-designs .sortLink:hover {
        background-color: #e9ecef;
    }
    
    .wppz-delete-selected:hover,
    .wppz-delete-design:hover {
        color: #dc3545 !important;
    }
    
    .wppz-design-name:hover {
        color: #007cba;
        text-decoration: underline;
    }
    
    .wppz-error {
        padding: 15px;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 4px;
        color: #721c24;
        margin: 10px 0;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Handle design name click to open design
        $('.wppz-design-name').on('click', function() {
            var designId = $(this).data('design-id');
            $('#eddmDesignerPopup').modal('show');
            // Store design ID for later use
            $('#eddmDesignerPopup').data('design-id', designId);
        });

        // Handle individual design deletion
        $('.wppz-delete-design').on('click', function() {
            var designId = $(this).data('design-id');
            if (confirm('Are you sure you want to delete this design?')) {
                deleteDesign(designId);
            }
        });

        // Handle bulk deletion
        $('.wppz-delete-selected').on('click', function() {
            var selectedDesigns = [];
            $('.wppz-design-checkbox:checked').each(function() {
                selectedDesigns.push($(this).val());
            });
            
            if (selectedDesigns.length === 0) {
                alert('Please select designs to delete.');
                return;
            }
            
            if (confirm('Are you sure you want to delete ' + selectedDesigns.length + ' design(s)?')) {
                bulkDeleteDesigns(selectedDesigns);
            }
        });

        // Handle select all checkbox
        $('#designsSelectAll').on('change', function() {
            $('.wppz-design-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Handle sorting
        $('.wppz-sort').on('click', function() {
            var sortBy = $(this).data('sort');
            sortDesigns(sortBy);
        });

        // AJAX functions
        function deleteDesign(designId) {
            $.ajax({
                url: wppz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wppz_delete_design',
                    design_id: designId,
                    nonce: wppz_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('tr[data-design-id="' + designId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error deleting design: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error deleting design. Please try again.');
                }
            });
        }

        function bulkDeleteDesigns(designIds) {
            $.ajax({
                url: wppz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wppz_bulk_delete_designs',
                    design_ids: designIds,
                    nonce: wppz_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        designIds.forEach(function(id) {
                            $('tr[data-design-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                            });
                        });
                        $('#designsSelectAll').prop('checked', false);
                    } else {
                        alert('Error deleting designs: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error deleting designs. Please try again.');
                }
            });
        }

        function sortDesigns(sortBy) {
            // Implement sorting logic here
            location.href = location.href + (location.href.indexOf('?') > -1 ? '&' : '?') + 'sort=' + sortBy;
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

/**
 * Get saved designs for a user
 */
function get_user_saved_designs($user_id, $per_page = 10) {
    global $wpdb;
    
    // This is a sample - you'll need to adapt this to your actual database structure
    $table_name = $wpdb->prefix . 'user_saved_designs';
    
    $designs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_date DESC LIMIT %d",
        $user_id,
        $per_page
    ), ARRAY_A);
    
    // Format the data
    $formatted_designs = array();
    foreach ($designs as $design) {
        $formatted_designs[] = array(
            'id' => $design['id'],
            'name' => $design['design_name'] ?: 'my-design_' . date('Ymd_H:i:s', strtotime($design['created_date'])),
            'product_type' => $design['product_type'] ?: 'Business Cards',
            'created_date_full' => date('M j, Y g:i:s A', strtotime($design['created_date'])),
            'created_date_short' => date('M j, Y', strtotime($design['created_date'])),
            'design_data' => $design['design_data']
        );
    }
    
    return $formatted_designs;
}

/**
 * Get design modals HTML
 */
function get_design_modals() {
    ob_start();
    ?>
    <!-- Basic Creator Modal -->
    <div class="modal fade" id="basicCreator" style="background: rgba(0, 0, 0, 0.6);">
        <div class="modal-dialog default-font">
            <div class="modal-content" style="border-radius: 0.5rem;">
                <div class="modal-header acc-green" style="text-align: center; padding: 0.5rem; border-radius: 0.5rem 0.5rem 0px 0px;">
                    <button type="button" class="close modal-close" data-dismiss="modal" style="margin-top: 0rem;">
                        <i class="fal fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 2rem 2rem 0px;">
                    <div>This design was not created using our Web UI, hence editing is not allowed. You can edit it using the tool used to create this design like Mobile App.</div>
                </div>
                <div class="modal-footer" style="text-align: center;">
                    <button type="button" class="btn btn-primary formButton ml-0" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDDM Designer Popup -->
    <div class="modal fade" id="eddmDesignerPopup" style="background: rgba(0, 0, 0, 0.6);">
        <div class="modal-dialog default-font">
            <div class="modal-content">
                <div class="modal-header acc-green" style="text-align: center; padding: 1.5rem;">
                    <h3 style="text-align: center;">Ordering Intent</h3>
                    <button type="button" class="close modal-close" data-dismiss="modal">
                        <i class="fal fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" style="padding: 3rem 2rem 2rem;">
                    <h4>Open Design to Order</h4>
                    <div class="mt-1 pl-5">
                        <div class="custom-control custom-radio mt-4" style="margin-left: 1rem; display: inline-block;">
                            <input class="custom-control-input" type="radio" name="selection" id="selection0" value="0">
                            <label class="custom-control-label" for="selection0">
                                <div class="list-address" style="line-height: 3.4rem;">EDDM Service/Package</div>
                            </label>
                        </div>
                        <div class="custom-control custom-radio mt-4" style="margin-left: 4rem; display: inline-block;">
                            <input class="custom-control-input" type="radio" name="selection" id="selection1" value="1">
                            <label class="custom-control-label" for="selection1">
                                <div class="list-address" style="line-height: 3.4rem;">Non-EDDM Regular Prints</div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="text-align: center;">
                    <button type="button" class="btn btn-primary formButton ml-0 wppz-open-design">Design</button>
                    <div class="mb-4">
                        <a href="javascript:void(0);" data-dismiss="modal" style="background: none; color: rgb(72, 72, 72);">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get pagination for designs
 */
function get_designs_pagination($user_id, $per_page) {
    // Implement pagination logic here
    return '';
}

/**
 * AJAX handler for deleting a design
 */
function wppz_ajax_delete_design() {
    check_ajax_referer('wppz_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized');
    }
    
    $design_id = intval($_POST['design_id']);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_saved_designs';
    
    $result = $wpdb->delete(
        $table_name,
        array(
            'id' => $design_id,
            'user_id' => $user_id
        ),
        array('%d', '%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Design deleted successfully');
    } else {
        wp_send_json_error('Failed to delete design');
    }
}

/**
 * AJAX handler for bulk deleting designs
 */
function wppz_ajax_bulk_delete_designs() {
    check_ajax_referer('wppz_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized');
    }
    
    $design_ids = array_map('intval', $_POST['design_ids']);
    $user_id = get_current_user_id();
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_saved_designs';
    
    $placeholders = implode(',', array_fill(0, count($design_ids), '%d'));
    $query = $wpdb->prepare(
        "DELETE FROM $table_name WHERE id IN ($placeholders) AND user_id = %d",
        array_merge($design_ids, array($user_id))
    );
    
    $result = $wpdb->query($query);
    
    if ($result !== false) {
        wp_send_json_success('Designs deleted successfully');
    } else {
        wp_send_json_error('Failed to delete designs');
    }
}

/**
 * Enqueue scripts and styles
 */
function wppz_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.min.js', array('jquery'), '4.6.0', true);
    wp_enqueue_style('bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css', array(), '4.6.0');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
    
    wp_localize_script('jquery', 'wppz_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wppz_nonce')
    ));
}

// Register shortcode
add_shortcode('wppluginzone_user_dashboard_saved_design', 'wppluginzone_user_dashboard_saved_design_shortcode');

// Register AJAX handlers
add_action('wp_ajax_wppz_delete_design', 'wppz_ajax_delete_design');
add_action('wp_ajax_wppz_bulk_delete_designs', 'wppz_ajax_bulk_delete_designs');

// Enqueue scripts
add_action('wp_enqueue_scripts', 'wppz_enqueue_scripts');

?>
<?php
/**
 * WooCommerce User Credit Cards Dashboard Shortcode
 * 
 * Usage: [wppluginzone_user_dashboard_credit_cards]
 * 
 * This shortcode displays the current user's saved payment methods/credit cards
 * in a dashboard format similar to the provided HTML structure.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the shortcode
 */
add_shortcode('wppluginzone_user_dashboard_credit_cards', 'wppluginzone_user_credit_cards_dashboard');

/**
 * Credit Cards Dashboard Shortcode Function
 */
function wppluginzone_user_credit_cards_dashboard($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p class="wc-notice wc-notice--error">You must be logged in to view your credit cards.</p>';
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return '<p class="wc-notice wc-notice--error">WooCommerce is required for this feature.</p>';
    }

    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Get user's saved payment methods
    $payment_tokens = WC_Payment_Tokens::get_customer_tokens($user_id);

    // Start output buffering
    ob_start();
    ?>
    
    <div class="wppluginzone-credit-cards-dashboard">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs mb-3">
            <a href="<?php echo wc_get_account_endpoint_url('dashboard'); ?>" class="breadcrumb-link">
                <span class="acc-header-links">Dashboard <i class="fas fa-angle-double-right"></i></span>
            </a>
            <a href="<?php echo wc_get_account_endpoint_url('edit-account'); ?>" class="breadcrumb-link">
                <span class="acc-header-links">My Account <i class="fas fa-angle-double-right"></i></span>
            </a>
            <span class="acc-header-links ctaColor lastChild">Cards</span>
        </div>

        <!-- Unsaved Changes Modal (Hidden by default) -->
        <div class="modal fade wc-modal" id="dvUnsavedChanges" style="display: none;">
            <div class="modal-dialog modal-dialog-sm">
                <div class="modal-content">
                    <div class="modal-header warning-bg">
                        <div class="bold text-center">
                            <i class="fal fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                    <div class="modal-body">
                        <p class="text-center mt-2 mb-0">Are you sure you want to go back? Your information/changes will not be saved.</p>
                    </div>
                    <div class="modal-footer text-center">
                        <button type="button" class="btn btn-primary">Yes, Go Back</button>
                        <p class="mb-1"><a href="javascript:void(0);" class="cancel-link">Cancel</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Cards Section -->
        <div class="credit-cards-section">
            <p class="section-heading">Credit Cards</p>
            
            <!-- Add New Card Button -->
            <a href="<?php echo wc_get_account_endpoint_url('add-payment-method'); ?>" class="add-card-btn">
                <button type="button" class="btn btn-primary formButton">
                    Add New <i class="fas fa-plus"></i>
                </button>
            </a>

            <?php if (!empty($payment_tokens)) : ?>
                <!-- Delete Selected Option -->
                <div class="bulk-actions">
                    <span class="acc-bold-action delete-selected" style="color: #d5d5d5;">Delete Selected</span>
                </div>

                <!-- Cards Table -->
                <div class="cards-table-container">
                    <table class="table acc-table acc-order-table wc-credit-cards-table">
                        <thead>
                            <tr class="acc-table-head">
                                <th scope="col" class="acc-table-head-td checkbox-col">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="cardsSelectAll">
                                        <label class="custom-control-label" for="cardsSelectAll"></label>
                                    </div>
                                </th>
                                <th scope="col" class="acc-table-head-td sortable">
                                    <span>Name <i></i></span>
                                </th>
                                <th scope="col" class="acc-table-head-td sortable">
                                    <span>Payment method <i></i></span>
                                </th>
                                <th scope="col" class="acc-table-head-td actions-col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 0;
                            foreach ($payment_tokens as $token) : 
                                $token_id = $token->get_id();
                                $card_type = $token->get_type();
                                $last4 = $token->get_last4();
                                $exp_month = $token->get_expiry_month();
                                $exp_year = $token->get_expiry_year();
                                $card_brand = $token->get_card_type();
                                $display_name = $token->get_display_name();
                                
                                // Get user's display name for card
                                $card_holder_name = $current_user->display_name ?: $current_user->first_name . ' ' . $current_user->last_name;
                                $card_holder_name = trim($card_holder_name) ?: $current_user->user_login;
                            ?>
                            <tr class="acc-table-body" data-token-id="<?php echo esc_attr($token_id); ?>">
                                <td class="acc-table-body-td checkbox-col">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input card-select" type="checkbox" 
                                               id="cardsSelect<?php echo $counter; ?>" 
                                               value="<?php echo esc_attr($token_id); ?>">
                                        <label class="custom-control-label" for="cardsSelect<?php echo $counter; ?>"></label>
                                    </div>
                                </td>
                                <td class="acc-table-body-td card-name">
                                    <span><b><?php echo esc_html(strtoupper($card_holder_name)); ?></b></span><br>
                                    <span><?php echo esc_html($display_name ?: strtoupper($card_brand) . ' CREDIT CARD'); ?></span>
                                </td>
                                <td class="acc-table-body-td payment-method">
                                    <span>***<?php echo esc_html($last4); ?></span>
                                    <?php if ($exp_month && $exp_year) : ?>
                                        <br><small>Expires: <?php echo esc_html($exp_month . '/' . substr($exp_year, -2)); ?></small>
                                    <?php endif; ?>
                                    <?php
                                    // Display card brand icon
                                    $card_icon = wppluginzone_get_card_icon($card_brand);
                                    if ($card_icon) {
                                        echo '<img class="ccSize list-cc" src="' . esc_url($card_icon) . '" alt="' . esc_attr($card_brand) . '">';
                                    }
                                    ?>
                                </td>
                                <td class="acc-table-body-td actions-col text-right">
                                    <i class="fas fa-times delete-card" data-token-id="<?php echo esc_attr($token_id); ?>" 
                                       style="color: #d8d8d8; cursor: pointer;" title="Delete Card"></i>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right" colspan="4" style="padding-top: 0;">
                                    <span class="acc-action edit-card" data-token-id="<?php echo esc_attr($token_id); ?>">
                                        <i class="fal fa-pencil"></i> Edit
                                    </span>
                                </td>
                            </tr>
                            <?php 
                            $counter++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="no-cards-message">
                    <p>You don't have any saved payment methods yet.</p>
                    <p><a href="<?php echo wc_get_account_endpoint_url('add-payment-method'); ?>">Add your first payment method</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .wppluginzone-credit-cards-dashboard {
        max-width: 100%;
        margin: 0 auto;
        font-family: Arial, sans-serif;
    }

    .breadcrumbs {
        margin-bottom: 1rem;
    }

    .breadcrumb-link {
        text-decoration: none;
        color: #333;
    }

    .acc-header-links {
        color: #666;
        font-size: 14px;
    }

    .ctaColor {
        color: #007cba !important;
        font-weight: bold;
    }

    .section-heading {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .add-card-btn {
        display: inline-block;
        margin-bottom: 1rem;
        text-decoration: none;
    }

    .formButton {
        background-color: #007cba;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    .formButton:hover {
        background-color: #005a87;
    }

    .bulk-actions {
        margin-bottom: 1rem;
    }

    .delete-selected {
        cursor: pointer;
        font-weight: bold;
    }

    .cards-table-container {
        overflow-x: auto;
        margin-bottom: 2rem;
    }

    .wc-credit-cards-table {
        width: 100%;
        min-width: 50rem;
        border-collapse: collapse;
        background: white;
    }

    .acc-table-head {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .acc-table-head-td {
        padding: 12px;
        font-weight: bold;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    .checkbox-col {
        width: 2rem !important;
    }

    .actions-col {
        width: 4rem !important;
    }

    .sortable {
        cursor: pointer;
    }

    .sortable:hover {
        background-color: #e9ecef;
    }

    .acc-table-body-td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: top;
    }

    .card-name b {
        font-weight: bold;
        font-size: 14px;
    }

    .card-name span:last-child {
        color: #666;
        font-size: 12px;
    }

    .payment-method {
        font-family: monospace;
    }

    .ccSize {
        width: 32px;
        height: 20px;
        margin-left: 8px;
        vertical-align: middle;
    }

    .delete-card, .edit-card {
        cursor: pointer;
        transition: color 0.2s;
    }

    .delete-card:hover {
        color: #dc3545 !important;
    }

    .edit-card {
        color: #007cba;
        text-decoration: none;
        font-size: 14px;
    }

    .edit-card:hover {
        color: #005a87;
        text-decoration: underline;
    }

    .no-cards-message {
        text-align: center;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 4px;
        margin-top: 1rem;
    }

    .no-cards-message p {
        margin-bottom: 0.5rem;
    }

    .no-cards-message a {
        color: #007cba;
        text-decoration: none;
        font-weight: bold;
    }

    .no-cards-message a:hover {
        text-decoration: underline;
    }

    /* Custom checkbox styling */
    .custom-control-input {
        opacity: 0;
        position: absolute;
    }

    .custom-control-label {
        position: relative;
        margin-bottom: 0;
        vertical-align: top;
    }

    .custom-control-label::before {
        content: "";
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-right: 8px;
        border: 1px solid #adb5bd;
        border-radius: 2px;
        background-color: #fff;
        vertical-align: middle;
    }

    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: #007cba;
        border-color: #007cba;
    }

    .custom-control-input:checked ~ .custom-control-label::after {
        content: "✓";
        position: absolute;
        left: 2px;
        top: -1px;
        color: white;
        font-size: 12px;
        font-weight: bold;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .wc-credit-cards-table {
            min-width: auto;
            font-size: 14px;
        }
        
        .section-heading {
            font-size: 1.5rem;
        }
        
        .cards-table-container {
            font-size: 12px;
        }
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Select all functionality
        $('#cardsSelectAll').change(function() {
            $('.card-select').prop('checked', this.checked);
            toggleDeleteButton();
        });

        // Individual card selection
        $('.card-select').change(function() {
            var allChecked = $('.card-select:checked').length === $('.card-select').length;
            $('#cardsSelectAll').prop('checked', allChecked);
            toggleDeleteButton();
        });

        // Toggle delete button visibility
        function toggleDeleteButton() {
            var checkedCards = $('.card-select:checked').length;
            if (checkedCards > 0) {
                $('.delete-selected').css('color', '#dc3545').css('cursor', 'pointer');
            } else {
                $('.delete-selected').css('color', '#d5d5d5').css('cursor', 'default');
            }
        }

        // Delete selected cards
        $('.delete-selected').click(function() {
            var checkedCards = $('.card-select:checked');
            if (checkedCards.length === 0) return;

            if (confirm('Are you sure you want to delete the selected payment methods?')) {
                var tokenIds = [];
                checkedCards.each(function() {
                    tokenIds.push($(this).val());
                });

                // AJAX call to delete payment methods
                $.ajax({
                    url: wc_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wppluginzone_delete_payment_methods',
                        token_ids: tokenIds,
                        nonce: '<?php echo wp_create_nonce("delete_payment_methods"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting payment methods: ' + response.data);
                        }
                    }
                });
            }
        });

        // Delete individual card
        $('.delete-card').click(function() {
            var tokenId = $(this).data('token-id');
            if (confirm('Are you sure you want to delete this payment method?')) {
                $.ajax({
                    url: wc_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wppluginzone_delete_payment_method',
                        token_id: tokenId,
                        nonce: '<?php echo wp_create_nonce("delete_payment_method"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting payment method: ' + response.data);
                        }
                    }
                });
            }
        });

        // Edit card functionality
        $('.edit-card').click(function() {
            var tokenId = $(this).data('token-id');
            // Redirect to WooCommerce edit payment method page
            window.location.href = '<?php echo wc_get_account_endpoint_url("payment-methods"); ?>';
        });
    });
    </script>

    <?php
    return ob_get_clean();
}

/**
 * Get card brand icon URL
 */
function wppluginzone_get_card_icon($card_brand) {
    $icons = array(
        'visa' => 'https://static.gotprint.com/tl/checkout/svg/cc_visa.svg',
        'mastercard' => 'https://static.gotprint.com/tl/checkout/svg/cc_mastercard.svg',
        'amex' => 'https://static.gotprint.com/tl/checkout/svg/cc_amex.svg',
        'discover' => 'https://static.gotprint.com/tl/checkout/svg/cc_discover.svg',
        'jcb' => 'https://static.gotprint.com/tl/checkout/svg/cc_jcb.svg',
        'diners' => 'https://static.gotprint.com/tl/checkout/svg/cc_diners.svg'
    );

    $brand = strtolower($card_brand);
    return isset($icons[$brand]) ? $icons[$brand] : null;
}

/**
 * AJAX handler for deleting payment methods
 */
add_action('wp_ajax_wppluginzone_delete_payment_method', 'wppluginzone_delete_payment_method_handler');
function wppluginzone_delete_payment_method_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'delete_payment_method')) {
        wp_die('Security check failed');
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    $token_id = intval($_POST['token_id']);
    $user_id = get_current_user_id();

    // Get the token and verify ownership
    $token = WC_Payment_Tokens::get($token_id);
    if (!$token || $token->get_user_id() !== $user_id) {
        wp_send_json_error('Invalid token or access denied');
    }

    // Delete the token
    if (WC_Payment_Tokens::delete($token_id)) {
        wp_send_json_success('Payment method deleted successfully');
    } else {
        wp_send_json_error('Failed to delete payment method');
    }
}

/**
 * AJAX handler for deleting multiple payment methods
 */
add_action('wp_ajax_wppluginzone_delete_payment_methods', 'wppluginzone_delete_payment_methods_handler');
function wppluginzone_delete_payment_methods_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'delete_payment_methods')) {
        wp_die('Security check failed');
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    $token_ids = array_map('intval', $_POST['token_ids']);
    $user_id = get_current_user_id();
    $deleted_count = 0;

    foreach ($token_ids as $token_id) {
        // Get the token and verify ownership
        $token = WC_Payment_Tokens::get($token_id);
        if ($token && $token->get_user_id() === $user_id) {
            if (WC_Payment_Tokens::delete($token_id)) {
                $deleted_count++;
            }
        }
    }

    if ($deleted_count > 0) {
        wp_send_json_success("Deleted {$deleted_count} payment method(s) successfully");
    } else {
        wp_send_json_error('No payment methods were deleted');
    }
}
?>
<?php
/**
 * WPPluginZone User Dashboard Store Credits Shortcode
 * Shortcode: [wppluginzone_user_dashboard_store_credits]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Store Credits Shortcode Handler
 */
function wppluginzone_user_dashboard_store_credits_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="alert alert-warning" role="alert">Please log in to view your store credits.</div>';
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return '<div class="alert alert-error" role="alert">WooCommerce is required for store credits functionality.</div>';
    }

    // Get current user
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'show_history' => 'true',
        'show_balance' => 'true',
        'limit' => 10
    ), $atts, 'wppluginzone_user_dashboard_store_credits');

    // Start output buffering
    ob_start();
    
    // Get store credit balance (this depends on your store credit plugin)
    $store_credit_balance = get_store_credit_balance($user_id);
    $credit_history = get_store_credit_history($user_id, $atts['limit']);
    
    ?>
    <div class="col-md col-xs-12 accContiner">
        <div>
            <div class="mb-3">
                <!-- Breadcrumbs -->
                <div data-v-5e5cb1eb="" class="breadcrumbs">
                    <a data-v-5e5cb1eb="" href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="">
                        <span data-v-5e5cb1eb="" class="acc-header-links">
                            Dashboard <i data-v-5e5cb1eb="" class="fas fa-angle-double-right"></i>
                        </span>
                    </a>
                    <a data-v-5e5cb1eb="" href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="">
                        <span data-v-5e5cb1eb="" class="acc-header-links">
                            My Account <i data-v-5e5cb1eb="" class="fas fa-angle-double-right"></i>
                        </span>
                    </a>
                    <a data-v-5e5cb1eb="" href="#" class="router-link-active">
                        <span data-v-5e5cb1eb="" class="acc-header-links ctaColor lastChild">
                            Store Credit
                        </span>
                    </a>
                </div>

                <div>
                    <p class="section-heading">Store Credits</p>
                    
                    <?php if ($atts['show_balance'] === 'true'): ?>
                    <p class="store-credit-balance">
                        Remaining Balance: $<span><?php echo number_format($store_credit_balance, 2); ?></span>
                    </p>
                    <?php endif; ?>

                    <?php if ($store_credit_balance <= 0): ?>
                    <div>
                        <div class="alert alert-info" role="alert" style="text-align: center; font-size: 1.3rem; line-height: 4rem;">
                            No Store Credits Found.
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($atts['show_history'] === 'true' && !empty($credit_history)): ?>
                    <div style="overflow-x: auto;">
                        <h3>Credit History</h3>
                        <table class="table table-striped store-credit-history">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($credit_history as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($transaction['date'])); ?></td>
                                    <td><?php echo esc_html($transaction['description']); ?></td>
                                    <td class="<?php echo $transaction['amount'] > 0 ? 'credit' : 'debit'; ?>">
                                        <?php echo ($transaction['amount'] > 0 ? '+' : '') . '$' . number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td>$<?php echo number_format($transaction['balance'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
    .accContiner {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .breadcrumbs {
        margin-bottom: 20px;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .acc-header-links {
        color: #666;
        text-decoration: none;
        margin-right: 10px;
    }

    .acc-header-links:hover {
        color: #333;
    }

    .ctaColor {
        color: #007cba !important;
        font-weight: 600;
    }

    .section-heading {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #333;
    }

    .store-credit-balance {
        font-size: 1.5rem;
        color: #28a745;
        margin-bottom: 20px;
    }

    .store-credit-balance span {
        font-weight: bold;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-info {
        color: #31708f;
        background-color: #d9edf7;
        border-color: #bce8f1;
    }

    .alert-warning {
        color: #8a6d3b;
        background-color: #fcf8e3;
        border-color: #faebcc;
    }

    .alert-error {
        color: #a94442;
        background-color: #f2dede;
        border-color: #ebccd1;
    }

    .store-credit-history {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .store-credit-history th,
    .store-credit-history td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .store-credit-history th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .store-credit-history .credit {
        color: #28a745;
        font-weight: 600;
    }

    .store-credit-history .debit {
        color: #dc3545;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .accContiner {
            padding: 15px;
        }
        
        .section-heading {
            font-size: 1.5rem;
        }
        
        .store-credit-balance {
            font-size: 1.2rem;
        }
    }
    </style>
    <?php
    
    return ob_get_clean();
}

/**
 * Get store credit balance for a user
 * This function needs to be adapted based on your store credit plugin
 */
function get_store_credit_balance($user_id) {
    // Example implementations for popular store credit plugins:
    
    // For WooCommerce Store Credit plugin
    if (function_exists('wc_store_credit_get_balance')) {
        return wc_store_credit_get_balance($user_id);
    }
    
    // For Smart Coupons plugin
    if (function_exists('wc_sc_get_store_credit_balance')) {
        return wc_sc_get_store_credit_balance($user_id);
    }
    
    // Custom meta field approach
    $balance = get_user_meta($user_id, 'store_credit_balance', true);
    return $balance ? floatval($balance) : 0.00;
}

/**
 * Get store credit history for a user
 * This function needs to be adapted based on your store credit plugin
 */
function get_store_credit_history($user_id, $limit = 10) {
    global $wpdb;
    
    // Example query - adjust table name and structure based on your setup
    $table_name = $wpdb->prefix . 'store_credit_transactions';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE user_id = %d 
         ORDER BY date DESC 
         LIMIT %d",
        $user_id,
        $limit
    ), ARRAY_A);
    
    // If no custom table, you might use post meta or other storage
    if (empty($results)) {
        // Alternative: Get from order notes, post meta, etc.
        $results = get_user_meta($user_id, 'store_credit_history', true);
        if (!is_array($results)) {
            $results = array();
        }
    }
    
    return $results;
}

/**
 * Register the shortcode
 */
add_shortcode('wppluginzone_user_dashboard_store_credits', 'wppluginzone_user_dashboard_store_credits_shortcode');

/**
 * Add store credits endpoint to WooCommerce My Account
 */
function add_store_credits_endpoint() {
    add_rewrite_endpoint('store-credits', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_store_credits_endpoint');

/**
 * Add store credits tab to My Account menu
 */
function add_store_credits_link_my_account($items) {
    $items['store-credits'] = 'Store Credits';
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_store_credits_link_my_account');

/**
 * Add content to store credits endpoint
 */
function store_credits_content() {
    echo do_shortcode('[wppluginzone_user_dashboard_store_credits]');
}
add_action('woocommerce_account_store-credits_endpoint', 'store_credits_content');

/**
 * AJAX handler for refreshing store credit balance
 */
function ajax_refresh_store_credits() {
    check_ajax_referer('store_credits_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_die('Unauthorized');
    }
    
    $user_id = get_current_user_id();
    $balance = get_store_credit_balance($user_id);
    
    wp_send_json_success(array(
        'balance' => number_format($balance, 2),
        'formatted_balance' => '$' . number_format($balance, 2)
    ));
}
add_action('wp_ajax_refresh_store_credits', 'ajax_refresh_store_credits');

/**
 * Enqueue scripts for AJAX functionality
 */
function enqueue_store_credits_scripts() {
    if (is_account_page()) {
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'store_credits_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('store_credits_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_store_credits_scripts');

/**
 * GotPrint-style product configurator for PixelPrint A-Frame Signs.
 */
function wppz_aframe_product_ids() {
    return apply_filters('wppz_aframe_product_ids', array(3305));
}

function wppz_aframe_is_legacy_product($product_id = 0) {
    $product_id = $product_id ? absint($product_id) : absint(get_the_ID());

    if (!$product_id) {
        return false;
    }

    $product_ids = array_map('absint', wppz_aframe_product_ids());
    $product_slug = get_post_field('post_name', $product_id);

    return in_array($product_id, $product_ids, true) || 'a-frame-signs' === $product_slug;
}

function wppz_gp_configurator_enabled_meta_key() {
    return '_wppz_gp_configurator_enabled';
}

function wppz_gp_configurator_settings_meta_key() {
    return '_wppz_gp_configurator_settings';
}

function wppz_is_aframe_product_context($product_id = 0) {
    $product_id = $product_id ? absint($product_id) : absint(get_the_ID());

    if (!$product_id) {
        return false;
    }

    $enabled = get_post_meta($product_id, wppz_gp_configurator_enabled_meta_key(), true);

    if ('yes' === $enabled) {
        return true;
    }

    if ('no' === $enabled) {
        return false;
    }

    return wppz_aframe_is_legacy_product($product_id);
}

function wppz_gp_parse_option_text($text) {
    $text = is_array($text) ? implode("\n", $text) : (string) $text;
    $options = array();
    $parts = preg_split('/[\r\n,]+/', $text);

    foreach ($parts as $part) {
        $part = trim(wp_unslash($part));

        if ('' === $part) {
            continue;
        }

        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $matches)) {
            $start = absint($matches[1]);
            $end = absint($matches[2]);

            if ($start && $end && $end >= $start && ($end - $start) <= 500) {
                foreach (range($start, $end) as $number) {
                    $options[] = (string) $number;
                }
                continue;
            }
        }

        $options[] = sanitize_text_field($part);
    }

    return array_values(array_unique(array_filter($options, 'strlen')));
}

function wppz_gp_options_to_text($options, $key = '') {
    $options = array_values(array_filter(array_map('strval', (array) $options), 'strlen'));

    if ('quantity' === $key) {
        $numbers = array_map('absint', $options);
        $count = count($numbers);

        if ($count > 2 && range($numbers[0], $numbers[$count - 1]) === $numbers) {
            return $numbers[0] . '-' . $numbers[$count - 1];
        }
    }

    return implode("\n", $options);
}

function wppz_gp_size_map_to_text($size_map) {
    $lines = array();

    foreach ((array) $size_map as $shape => $sizes) {
        $shape = sanitize_text_field($shape);
        $sizes = array_values(array_filter(array_map('strval', (array) $sizes), 'strlen'));

        if ($shape && $sizes) {
            $lines[] = $shape . ': ' . implode(', ', $sizes);
        }
    }

    return implode("\n", $lines);
}

function wppz_gp_parse_size_map_text($text, $fallback = array()) {
    $text = (string) $text;
    $size_map = array();
    $lines = preg_split('/\r\n|\r|\n/', wp_unslash($text));

    foreach ($lines as $line) {
        $line = trim($line);

        if ('' === $line || false === strpos($line, ':')) {
            continue;
        }

        list($shape, $sizes) = array_map('trim', explode(':', $line, 2));
        $shape = sanitize_text_field($shape);
        $sizes = wppz_gp_parse_option_text($sizes);

        if ($shape && $sizes) {
            $size_map[$shape] = $sizes;
        }
    }

    return $size_map ? $size_map : $fallback;
}

function wppz_gp_default_configurator_fields() {
    return array(
        'material' => array(
            'enabled' => 'yes',
            'label' => 'Material',
            'type' => 'select',
            'default' => 'Acrylic',
            'required' => 'no',
            'help' => '',
            'options' => array('Acrylic'),
        ),
        'thickness' => array(
            'enabled' => 'yes',
            'label' => 'Thickness',
            'type' => 'radio',
            'default' => '1/4"',
            'required' => 'no',
            'help' => '',
            'options' => array('1/4"'),
        ),
        'material_color' => array(
            'enabled' => 'yes',
            'label' => 'Material Color',
            'type' => 'select',
            'default' => 'Transparent',
            'required' => 'no',
            'help' => '',
            'options' => array('Transparent'),
        ),
        'shape' => array(
            'enabled' => 'yes',
            'label' => 'Shape',
            'type' => 'select',
            'default' => 'Rectangle',
            'required' => 'no',
            'help' => '',
            'options' => array(
                'Rectangle',
                'Rounded Rectangle',
                'Square',
                'Rounded Square',
                'Circle',
                'Half Circle',
                'Half Arch',
                'House',
                'Apartment',
                'Star',
                'Octagon',
                'Arrow',
                'Oval',
            ),
        ),
        'size' => array(
            'enabled' => 'yes',
            'label' => 'Size',
            'type' => 'select',
            'default' => '6" x 24"',
            'required' => 'no',
            'help' => '',
            'options' => array(),
        ),
        'orientation' => array(
            'enabled' => 'yes',
            'label' => 'Orientation',
            'type' => 'orientation',
            'default' => 'Horizontal',
            'required' => 'no',
            'help' => '',
            'options' => array('Horizontal', 'Vertical'),
        ),
        'print' => array(
            'enabled' => 'yes',
            'label' => 'Print',
            'type' => 'radio',
            'default' => 'Front',
            'required' => 'no',
            'help' => 'This product prints on the front side only.',
            'options' => array('Front'),
        ),
        'hole_drilling' => array(
            'enabled' => 'yes',
            'label' => 'Hole Drilling',
            'type' => 'select',
            'default' => 'None',
            'required' => 'no',
            'help' => '',
            'options' => array(
                'None',
                '4 Corners',
                'Top 2 Corners',
                'Top & Bottom Center',
                '4 Corners & Top & Bottom Center',
            ),
        ),
        'accessories' => array(
            'enabled' => 'yes',
            'label' => 'Accessories',
            'type' => 'select',
            'default' => 'None',
            'required' => 'no',
            'help' => '',
            'options' => array('None', '3M Command Strip (pack of 2)'),
        ),
        'quantity' => array(
            'enabled' => 'yes',
            'label' => 'Quantity',
            'type' => 'select',
            'default' => '',
            'required' => 'yes',
            'help' => '',
            'options' => array_map('strval', range(1, 150)),
        ),
    );
}

function wppz_gp_default_configurator_settings() {
    return array(
        'product_label' => 'Acrylic Board',
        'designer_subline' => 'Acrylic Board',
        'proof_width' => '10.875',
        'proof_height' => '8.375',
        'enable_upload' => 'yes',
        'enable_designer' => 'yes',
        'fields' => wppz_gp_default_configurator_fields(),
        'size_map' => wppz_aframe_size_options_by_shape(0),
    );
}

function wppz_gp_sanitize_configurator_settings($settings) {
    $defaults = wppz_gp_default_configurator_settings();
    $settings = is_array($settings) ? $settings : array();
    $clean = $defaults;

    foreach (array('product_label', 'designer_subline', 'proof_width', 'proof_height') as $key) {
        if (isset($settings[$key])) {
            $clean[$key] = sanitize_text_field(wp_unslash($settings[$key]));
        }
    }

    $clean['enable_upload'] = !empty($settings['enable_upload']) && 'no' !== $settings['enable_upload'] ? 'yes' : 'no';
    $clean['enable_designer'] = !empty($settings['enable_designer']) && 'no' !== $settings['enable_designer'] ? 'yes' : 'no';

    if (!empty($settings['fields']) && is_array($settings['fields'])) {
        foreach ($defaults['fields'] as $key => $field_defaults) {
            $incoming = isset($settings['fields'][$key]) && is_array($settings['fields'][$key]) ? $settings['fields'][$key] : array();
            $field = array_merge($field_defaults, $incoming);
            $type = in_array($field_defaults['type'], array('select', 'radio', 'orientation'), true) ? $field_defaults['type'] : 'select';

            $clean['fields'][$key] = array(
                'enabled' => !empty($field['enabled']) && 'no' !== $field['enabled'] ? 'yes' : 'no',
                'label' => sanitize_text_field(wp_unslash($field['label'])),
                'type' => $type,
                'default' => sanitize_text_field(wp_unslash($field['default'])),
                'required' => !empty($field['required']) && 'no' !== $field['required'] ? 'yes' : 'no',
                'help' => sanitize_text_field(wp_unslash($field['help'])),
                'options' => !empty($field['options']) ? array_values(array_unique(array_map('sanitize_text_field', (array) $field['options']))) : $field_defaults['options'],
            );

            if ('size' === $key && empty($clean['fields'][$key]['options'])) {
                $clean['fields'][$key]['options'] = wppz_aframe_all_size_options(0);
            }
        }
    }

    if (!empty($settings['size_map']) && is_array($settings['size_map'])) {
        $clean['size_map'] = array();

        foreach ($settings['size_map'] as $shape => $sizes) {
            $shape = sanitize_text_field(wp_unslash($shape));
            $sizes = array_values(array_filter(array_map('sanitize_text_field', (array) $sizes), 'strlen'));

            if ($shape && $sizes) {
                $clean['size_map'][$shape] = $sizes;
            }
        }

        if (!$clean['size_map']) {
            $clean['size_map'] = $defaults['size_map'];
        }
    }

    return $clean;
}

function wppz_gp_get_product_configurator_settings($product_id = 0) {
    $product_id = $product_id ? absint($product_id) : absint(get_the_ID());
    $saved = $product_id ? get_post_meta($product_id, wppz_gp_configurator_settings_meta_key(), true) : array();

    return wppz_gp_sanitize_configurator_settings(is_array($saved) ? $saved : array());
}

function wppz_gp_configurator_product_data_tab($tabs) {
    $tabs['wppz_gp_configurator'] = array(
        'label' => __('Print Configurator', 'wppluginzone-user-dashboard'),
        'target' => 'wppz_gp_configurator_product_data',
        'class' => array(),
        'priority' => 65,
    );

    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'wppz_gp_configurator_product_data_tab');

function wppz_gp_configurator_product_data_panel() {
    global $post;

    if (!$post) {
        return;
    }

    $product_id = absint($post->ID);
    $enabled = get_post_meta($product_id, wppz_gp_configurator_enabled_meta_key(), true);

    if ('' === $enabled && wppz_aframe_is_legacy_product($product_id)) {
        $enabled = 'yes';
    }

    $settings = wppz_gp_get_product_configurator_settings($product_id);
    ?>
    <div id="wppz_gp_configurator_product_data" class="panel woocommerce_options_panel hidden">
        <div class="options_group wppz-gp-admin-panel">
            <?php wp_nonce_field('wppz_gp_save_configurator_settings', 'wppz_gp_configurator_nonce'); ?>

            <p class="form-field">
                <label for="wppz_gp_configurator_enabled"><?php echo esc_html__('Enable configurator', 'wppluginzone-user-dashboard'); ?></label>
                <input type="checkbox" class="checkbox" id="wppz_gp_configurator_enabled" name="wppz_gp_configurator_enabled" value="yes" <?php checked('yes', $enabled); ?>>
                <span class="description"><?php echo esc_html__('Show the GotPrint-style option selector, upload flow, proof review, and cart metadata for this product.', 'wppluginzone-user-dashboard'); ?></span>
            </p>

            <div class="wppz-gp-admin-grid">
                <p class="form-field">
                    <label for="wppz_gp_product_label"><?php echo esc_html__('Product label', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="text" id="wppz_gp_product_label" name="wppz_gp_settings[product_label]" value="<?php echo esc_attr($settings['product_label']); ?>">
                </p>
                <p class="form-field">
                    <label for="wppz_gp_designer_subline"><?php echo esc_html__('Designer subline', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="text" id="wppz_gp_designer_subline" name="wppz_gp_settings[designer_subline]" value="<?php echo esc_attr($settings['designer_subline']); ?>">
                </p>
                <p class="form-field">
                    <label for="wppz_gp_proof_width"><?php echo esc_html__('Proof width label', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="text" id="wppz_gp_proof_width" name="wppz_gp_settings[proof_width]" value="<?php echo esc_attr($settings['proof_width']); ?>">
                </p>
                <p class="form-field">
                    <label for="wppz_gp_proof_height"><?php echo esc_html__('Proof height label', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="text" id="wppz_gp_proof_height" name="wppz_gp_settings[proof_height]" value="<?php echo esc_attr($settings['proof_height']); ?>">
                </p>
                <p class="form-field">
                    <label for="wppz_gp_enable_upload"><?php echo esc_html__('Upload button', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="checkbox" class="checkbox" id="wppz_gp_enable_upload" name="wppz_gp_settings[enable_upload]" value="yes" <?php checked('yes', $settings['enable_upload']); ?>>
                    <span class="description"><?php echo esc_html__('Allow customers to upload or select files.', 'wppluginzone-user-dashboard'); ?></span>
                </p>
                <p class="form-field">
                    <label for="wppz_gp_enable_designer"><?php echo esc_html__('Design online button', 'wppluginzone-user-dashboard'); ?></label>
                    <input type="checkbox" class="checkbox" id="wppz_gp_enable_designer" name="wppz_gp_settings[enable_designer]" value="yes" <?php checked('yes', $settings['enable_designer']); ?>>
                    <span class="description"><?php echo esc_html__('Allow customers to create a simple online design.', 'wppluginzone-user-dashboard'); ?></span>
                </p>
            </div>

            <div class="wppz-gp-admin-section">
                <h4><?php echo esc_html__('Product Form Fields', 'wppluginzone-user-dashboard'); ?></h4>
                <p><?php echo esc_html__('Enable the fields this product needs. Put each option on a new line, or use a numeric range such as 1-150 for quantity.', 'wppluginzone-user-dashboard'); ?></p>
                <table class="widefat striped wppz-gp-admin-fields">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Show', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Field', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Label', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Default', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Required', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Options', 'wppluginzone-user-dashboard'); ?></th>
                            <th><?php echo esc_html__('Help', 'wppluginzone-user-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings['fields'] as $key => $field) : ?>
                            <tr>
                                <td><input type="checkbox" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][enabled]" value="yes" <?php checked('yes', $field['enabled']); ?>></td>
                                <td>
                                    <strong><?php echo esc_html($field['label']); ?></strong>
                                    <code><?php echo esc_html($key); ?></code>
                                    <input type="hidden" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][type]" value="<?php echo esc_attr($field['type']); ?>">
                                </td>
                                <td><input type="text" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($field['label']); ?>"></td>
                                <td><input type="text" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][default]" value="<?php echo esc_attr($field['default']); ?>"></td>
                                <td><input type="checkbox" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][required]" value="yes" <?php checked('yes', $field['required']); ?>></td>
                                <td><textarea name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][options]" rows="4"><?php echo esc_textarea(wppz_gp_options_to_text($field['options'], $key)); ?></textarea></td>
                                <td><input type="text" name="wppz_gp_settings[fields][<?php echo esc_attr($key); ?>][help]" value="<?php echo esc_attr($field['help']); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="wppz-gp-admin-section">
                <h4><?php echo esc_html__('Shape Based Sizes', 'wppluginzone-user-dashboard'); ?></h4>
                <p><?php echo esc_html__('Use one line per shape: Shape: Size 1, Size 2, Size 3. These values control the Size dropdown after a Shape is selected.', 'wppluginzone-user-dashboard'); ?></p>
                <textarea class="wppz-gp-admin-size-map" name="wppz_gp_settings[size_map]" rows="9"><?php echo esc_textarea(wppz_gp_size_map_to_text($settings['size_map'])); ?></textarea>
            </div>
        </div>
    </div>
    <?php
}
add_action('woocommerce_product_data_panels', 'wppz_gp_configurator_product_data_panel');

function wppz_gp_save_configurator_product_data($product) {
    if (empty($_POST['wppz_gp_configurator_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wppz_gp_configurator_nonce'])), 'wppz_gp_save_configurator_settings')) {
        return;
    }

    $enabled = isset($_POST['wppz_gp_configurator_enabled']) ? 'yes' : 'no';
    $raw = isset($_POST['wppz_gp_settings']) && is_array($_POST['wppz_gp_settings']) ? wp_unslash($_POST['wppz_gp_settings']) : array();
    $defaults = wppz_gp_default_configurator_settings();
    $settings = array(
        'product_label' => isset($raw['product_label']) ? sanitize_text_field($raw['product_label']) : $defaults['product_label'],
        'designer_subline' => isset($raw['designer_subline']) ? sanitize_text_field($raw['designer_subline']) : $defaults['designer_subline'],
        'proof_width' => isset($raw['proof_width']) ? sanitize_text_field($raw['proof_width']) : $defaults['proof_width'],
        'proof_height' => isset($raw['proof_height']) ? sanitize_text_field($raw['proof_height']) : $defaults['proof_height'],
        'enable_upload' => isset($raw['enable_upload']) ? 'yes' : 'no',
        'enable_designer' => isset($raw['enable_designer']) ? 'yes' : 'no',
        'fields' => array(),
        'size_map' => isset($raw['size_map']) ? wppz_gp_parse_size_map_text($raw['size_map'], $defaults['size_map']) : $defaults['size_map'],
    );

    foreach ($defaults['fields'] as $key => $field_defaults) {
        $field_raw = isset($raw['fields'][$key]) && is_array($raw['fields'][$key]) ? $raw['fields'][$key] : array();
        $options = isset($field_raw['options']) ? wppz_gp_parse_option_text($field_raw['options']) : $field_defaults['options'];

        $settings['fields'][$key] = array(
            'enabled' => isset($field_raw['enabled']) ? 'yes' : 'no',
            'label' => isset($field_raw['label']) ? sanitize_text_field($field_raw['label']) : $field_defaults['label'],
            'type' => $field_defaults['type'],
            'default' => isset($field_raw['default']) ? sanitize_text_field($field_raw['default']) : $field_defaults['default'],
            'required' => isset($field_raw['required']) ? 'yes' : 'no',
            'help' => isset($field_raw['help']) ? sanitize_text_field($field_raw['help']) : '',
            'options' => $options ? $options : $field_defaults['options'],
        );
    }

    $product->update_meta_data(wppz_gp_configurator_enabled_meta_key(), $enabled);
    $product->update_meta_data(wppz_gp_configurator_settings_meta_key(), wppz_gp_sanitize_configurator_settings($settings));
}
add_action('woocommerce_admin_process_product_object', 'wppz_gp_save_configurator_product_data');

function wppz_gp_configurator_admin_styles() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || 'product' !== $screen->id) {
        return;
    }
    ?>
    <style>
        #wppz_gp_configurator_product_data .wppz-gp-admin-panel {
            padding: 16px 18px 22px;
        }
        #wppz_gp_configurator_product_data .form-field {
            padding: 5px 20px 5px 162px !important;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 18px;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-grid .form-field {
            padding-left: 0 !important;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-grid label {
            float: none;
            width: auto;
            margin: 0 0 4px;
            display: block;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-section {
            margin: 18px 0 0;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-section h4 {
            margin: 0 0 6px;
            font-size: 14px;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-fields th,
        #wppz_gp_configurator_product_data .wppz-gp-admin-fields td {
            vertical-align: top;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-fields input[type="text"],
        #wppz_gp_configurator_product_data .wppz-gp-admin-fields textarea,
        #wppz_gp_configurator_product_data .wppz-gp-admin-size-map {
            width: 100%;
        }
        #wppz_gp_configurator_product_data .wppz-gp-admin-fields code {
            display: block;
            margin-top: 4px;
        }
        @media (max-width: 960px) {
            #wppz_gp_configurator_product_data .wppz-gp-admin-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
}
add_action('admin_head-post.php', 'wppz_gp_configurator_admin_styles');
add_action('admin_head-post-new.php', 'wppz_gp_configurator_admin_styles');

function wppz_aframe_size_options_by_shape($product_id = 0) {
    if ($product_id) {
        $settings = wppz_gp_get_product_configurator_settings($product_id);

        if (!empty($settings['size_map'])) {
            return $settings['size_map'];
        }
    }

    return array(
        'Rectangle' => array(
            '6" x 18"',
            '6" x 20"',
            '6" x 24"',
            '6" x 30"',
            '8" x 10"',
            '8" x 12"',
            '8.5" x 11"',
            '9" x 24"',
            '10" x 12"',
            '10" x 14"',
            '10" x 18"',
            '10" x 20"',
            '11" x 14"',
            '11" x 19"',
            '12" x 16"',
            '12" x 18"',
            '12" x 24"',
            '16" x 20"',
            '16" x 23"',
            '18" x 20"',
            '18" x 24"',
            '18" x 30"',
            '18" x 36"',
            '20" x 30"',
            '20" x 40"',
            '22" x 23"',
            '22" x 28"',
            '24" x 30"',
            '24" x 36"',
        ),
        'Rounded Rectangle' => array(
            '6" x 18"',
            '6" x 20"',
            '6" x 24"',
            '6" x 30"',
            '8" x 10"',
            '8" x 12"',
            '8.5" x 11"',
            '9" x 24"',
            '10" x 12"',
            '10" x 14"',
            '10" x 18"',
            '10" x 20"',
            '11" x 14"',
            '11" x 19"',
            '12" x 16"',
            '12" x 18"',
            '12" x 24"',
            '16" x 20"',
            '16" x 23"',
            '18" x 20"',
            '18" x 24"',
            '18" x 30"',
            '18" x 36"',
            '20" x 30"',
            '20" x 40"',
            '22" x 23"',
            '22" x 28"',
            '24" x 30"',
            '24" x 36"',
        ),
        'Square' => array('8" x 8"', '10" x 10"', '12" x 12"', '14" x 14"', '16" x 16"', '18" x 18"', '20" x 20"', '23" x 23"', '24" x 24"'),
        'Rounded Square' => array('8" x 8"', '10" x 10"', '12" x 12"', '14" x 14"', '16" x 16"', '18" x 18"', '20" x 20"', '23" x 23"', '24" x 24"'),
        'Circle' => array('12" x 12"', '16" x 16"', '18" x 18"', '23" x 23"', '24" x 24"'),
        'Half Circle' => array('8" x 10"', '9" x 18"', '11" x 14"', '18" x 24"', '20" x 30"', '20" x 36"', '22" x 28"', '24" x 36"', '12" x 24"', '14" x 28"', '15" x 30"', '18" x 36"'),
        'Half Arch' => array('8" x 10"', '9" x 18"', '11" x 14"', '18" x 24"', '20" x 30"', '20" x 36"', '22" x 28"', '24" x 36"'),
        'House' => array('23" x 23"'),
        'Apartment' => array('23" x 23"'),
        'Star' => array('22" x 23"'),
        'Octagon' => array('23" x 23"'),
        'Arrow' => array('18" x 24"'),
        'Oval' => array('16" x 23"'),
    );
}

function wppz_aframe_all_size_options($product_id = 0) {
    $sizes = array();

    foreach (wppz_aframe_size_options_by_shape($product_id) as $shape_sizes) {
        $sizes = array_merge($sizes, $shape_sizes);
    }

    return array_values(array_unique($sizes));
}

function wppz_aframe_get_user_file_manager_items() {
    if (!is_user_logged_in()) {
        return array(
            'folders' => array(),
            'files' => array(),
            'designs' => array(),
        );
    }

    global $wpdb;

    $user_id = get_current_user_id();
    $files_table = $wpdb->prefix . 'user_files';
    $folders_table = $wpdb->prefix . 'user_folders';
    $designs_table = $wpdb->prefix . 'user_saved_designs';

    $folders = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $folders_table)) ? $wpdb->get_results($wpdb->prepare("SELECT id, name FROM {$folders_table} WHERE user_id = %d ORDER BY name ASC", $user_id)) : array();
    $files = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $files_table)) ? $wpdb->get_results($wpdb->prepare("SELECT id, folder_id, original_name, file_url, file_size, mime_type, upload_date FROM {$files_table} WHERE user_id = %d ORDER BY upload_date DESC LIMIT 100", $user_id)) : array();
    $designs = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $designs_table)) ? $wpdb->get_results($wpdb->prepare("SELECT id, design_name, product_type, created_date FROM {$designs_table} WHERE user_id = %d ORDER BY created_date DESC LIMIT 50", $user_id)) : array();

    return array(
        'folders' => is_array($folders) ? $folders : array(),
        'files' => is_array($files) ? $files : array(),
        'designs' => is_array($designs) ? $designs : array(),
    );
}

function wppz_aframe_get_user_file($file_id) {
    if (!$file_id || !is_user_logged_in()) {
        return null;
    }

    global $wpdb;

    $files_table = $wpdb->prefix . 'user_files';

    if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $files_table))) {
        return null;
    }

    return $wpdb->get_row($wpdb->prepare(
        "SELECT id, original_name, file_url, file_size, mime_type FROM {$files_table} WHERE id = %d AND user_id = %d",
        absint($file_id),
        get_current_user_id()
    ));
}

function wppz_aframe_format_file_size($bytes) {
    $bytes = absint($bytes);

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }

    return $bytes . ' B';
}

function wppz_aframe_config_options($product_id = 0) {
    $settings = wppz_gp_get_product_configurator_settings($product_id);
    $fields = array();

    foreach ($settings['fields'] as $key => $field) {
        if (empty($field['enabled']) || 'yes' !== $field['enabled']) {
            continue;
        }

        if ('size' === $key) {
            $field['options'] = wppz_aframe_all_size_options($product_id);
        }

        if (empty($field['options'])) {
            continue;
        }

        if ('' !== (string) $field['default'] && !in_array($field['default'], $field['options'], true)) {
            $field['default'] = reset($field['options']);
        }

        $field['required'] = !empty($field['required']) && 'yes' === $field['required'];
        $fields[$key] = $field;
    }

    return $fields;
}

function wppz_aframe_config_labels($product_id = 0) {
    $labels = array();

    foreach (wppz_aframe_config_options($product_id) as $key => $field) {
        $labels[$key] = $field['label'];
    }

    return array_merge($labels, array(
        'production_time' => 'Production Time',
        'process_type' => 'Process Type',
        'estimated_shipping' => 'Estimated Shipping',
        'selected_file' => 'Selected File',
        'proof_status' => 'Proof Status',
        'artwork_method' => 'Artwork Method',
        'design_summary' => 'Online Design',
    ));
}

function wppz_aframe_sanitize_config($raw_config, $product_id = 0) {
    $raw_config = is_array($raw_config) ? $raw_config : array();
    $config = array();

    foreach (wppz_aframe_config_options($product_id) as $key => $field) {
        $value = isset($raw_config[$key]) ? sanitize_text_field(wp_unslash($raw_config[$key])) : $field['default'];

        if (!in_array($value, $field['options'], true)) {
            $value = $field['default'];
        }

        $config[$key] = $value;
    }

    $shape_sizes = wppz_aframe_size_options_by_shape($product_id);

    if (!empty($config['shape']) && isset($config['size'], $shape_sizes[$config['shape']]) && !in_array($config['size'], $shape_sizes[$config['shape']], true)) {
        $available_sizes = $shape_sizes[$config['shape']];
        $config['size'] = reset($available_sizes);
    }

    if (isset($config['shape'], $config['hole_drilling']) && !in_array($config['shape'], array('Rectangle', 'Square'), true)) {
        $config['hole_drilling'] = 'None';
    }

    $production_time = isset($raw_config['production_time']) ? sanitize_text_field(wp_unslash($raw_config['production_time'])) : 'Regular (2-4 Business Days)';
    $allowed_production_times = array('Regular (2-4 Business Days)', 'Rush (2 Business Days)');
    $config['production_time'] = in_array($production_time, $allowed_production_times, true) ? $production_time : 'Regular (2-4 Business Days)';

    $process_type = isset($raw_config['process_type']) ? sanitize_text_field(wp_unslash($raw_config['process_type'])) : 'Instant Processing Proof';
    $allowed_process_types = array('Instant Processing Proof', 'Manually Processed Proof');
    $config['process_type'] = in_array($process_type, $allowed_process_types, true) ? $process_type : 'Instant Processing Proof';

    $estimated_shipping = isset($raw_config['estimated_shipping']) ? sanitize_text_field(wp_unslash($raw_config['estimated_shipping'])) : 'Calculated at checkout';
    $config['estimated_shipping'] = $estimated_shipping ?: 'Calculated at checkout';

    if (!empty($raw_config['selected_file'])) {
        $config['selected_file'] = sanitize_text_field(wp_unslash($raw_config['selected_file']));
    }

    $proof_status = isset($raw_config['proof_status']) ? sanitize_text_field(wp_unslash($raw_config['proof_status'])) : '';
    if ('Approved for Print' === $proof_status) {
        $config['proof_status'] = $proof_status;
    }

    $artwork_method = isset($raw_config['artwork_method']) ? sanitize_text_field(wp_unslash($raw_config['artwork_method'])) : '';
    $allowed_artwork_methods = array('Upload Front', 'Design Online', 'No artwork selected');
    $config['artwork_method'] = in_array($artwork_method, $allowed_artwork_methods, true) ? $artwork_method : 'No artwork selected';

    if (!empty($raw_config['design_summary'])) {
        $config['design_summary'] = sanitize_text_field(wp_unslash($raw_config['design_summary']));
    }

    return $config;
}

function wppz_aframe_field_id($key) {
    return 'wppz_aframe_' . sanitize_key($key);
}

function wppz_aframe_render_help_button($field) {
    if (empty($field['help'])) {
        return;
    }

    ?>
    <button type="button" class="wppz-gp-help" aria-label="<?php echo esc_attr('More information about ' . $field['label']); ?>" data-wppz-help="<?php echo esc_attr($field['help']); ?>">
        <span aria-hidden="true">?</span>
    </button>
    <?php
}

function wppz_aframe_render_select_field($key, $field) {
    $field_id = wppz_aframe_field_id($key);
    $is_required = !empty($field['required']);
    $has_value = '' !== (string) $field['default'];
    $classes = array('wppz-gp-field');

    if ($is_required) {
        $classes[] = 'is-required';
        $classes[] = $has_value ? 'is-complete' : 'is-empty';
    }

    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-wppz-field="<?php echo esc_attr($key); ?>" <?php echo $is_required ? 'data-wppz-required="true"' : ''; ?>>
        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field['label']); ?></label>
        <span class="wppz-gp-valid" aria-hidden="true"></span>
        <?php if ($is_required) : ?>
            <span class="wppz-gp-required-badge"><?php echo esc_html__('Required', 'wppluginzone-user-dashboard'); ?></span>
        <?php endif; ?>
        <?php wppz_aframe_render_help_button($field); ?>
        <select id="<?php echo esc_attr($field_id); ?>" name="wppz_aframe_config[<?php echo esc_attr($key); ?>]" <?php echo $is_required ? 'aria-required="true"' : ''; ?>>
            <option value="" hidden <?php selected('', $field['default']); ?>><?php echo esc_html__('Please select an option', 'wppluginzone-user-dashboard'); ?></option>
            <?php foreach ($field['options'] as $option) : ?>
                <option value="<?php echo esc_attr($option); ?>" <?php selected($option, $field['default']); ?>>
                    <?php echo esc_html($option); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function wppz_aframe_render_orientation_field($key, $field) {
    ?>
    <fieldset class="wppz-gp-orientation-field" data-wppz-field="<?php echo esc_attr($key); ?>">
        <legend><?php echo esc_html($field['label']); ?></legend>
        <div class="wppz-gp-orientation-options">
            <?php foreach ($field['options'] as $index => $option) : ?>
                <?php
                $field_id = wppz_aframe_field_id($key . '_' . $index);
                $icon_class = 'Vertical' === $option ? 'is-vertical' : 'is-horizontal';
                ?>
                <label class="wppz-gp-orientation-option" for="<?php echo esc_attr($field_id); ?>">
                    <input id="<?php echo esc_attr($field_id); ?>" type="radio" name="wppz_aframe_config[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($option); ?>" <?php checked($option, $field['default']); ?>>
                    <span class="wppz-gp-orientation-icon <?php echo esc_attr($icon_class); ?>" aria-hidden="true"></span>
                    <span><?php echo esc_html($option); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function wppz_aframe_render_radio_field($key, $field) {
    ?>
    <fieldset class="wppz-gp-radio-field" data-wppz-field="<?php echo esc_attr($key); ?>">
        <legend><?php echo esc_html($field['label']); ?></legend>
        <?php wppz_aframe_render_help_button($field); ?>
        <div class="wppz-gp-radio-options">
            <?php foreach ($field['options'] as $index => $option) : ?>
                <?php $field_id = wppz_aframe_field_id($key . '_' . $index); ?>
                <label class="wppz-gp-radio-option" for="<?php echo esc_attr($field_id); ?>">
                    <input id="<?php echo esc_attr($field_id); ?>" type="radio" name="wppz_aframe_config[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($option); ?>" <?php checked($option, $field['default']); ?>>
                    <span class="wppz-gp-radio-mark" aria-hidden="true"></span>
                    <span><?php echo esc_html($option); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
    <?php
}

function wppz_aframe_render_acrylic_note() {
    ?>
    <div class="wppz-gp-note">
        <span class="wppz-gp-note-icon" aria-hidden="true">i</span>
        <p><strong><?php echo esc_html__('Note:', 'wppluginzone-user-dashboard'); ?></strong> <?php echo esc_html__('White ink is not compatible with this paper stock. Any white elements on submitted design files will not be visible in final prints.', 'wppluginzone-user-dashboard'); ?></p>
    </div>
    <?php
}

function wppz_aframe_render_config_field($key, $field) {
    if ('orientation' === $field['type']) {
        wppz_aframe_render_orientation_field($key, $field);
        return;
    }

    if ('radio' === $field['type']) {
        wppz_aframe_render_radio_field($key, $field);
        return;
    }

    wppz_aframe_render_select_field($key, $field);
}

function wppz_aframe_render_post_quantity_options() {
    ?>
    <div class="wppz-gp-after-quantity" data-wppz-after-quantity hidden>
        <div class="wppz-gp-production">
            <div class="wppz-gp-production-title">
                <span><?php echo esc_html__('Production Time', 'wppluginzone-user-dashboard'); ?></span>
                <button type="button" class="wppz-gp-mini-help" data-wppz-help="<?php echo esc_attr__('Regular production is the standard turnaround. Rush shortens production time when available.', 'wppluginzone-user-dashboard'); ?>" aria-label="<?php echo esc_attr__('Production time help', 'wppluginzone-user-dashboard'); ?>">?</button>
            </div>
            <div class="wppz-gp-production-options" role="radiogroup" aria-label="<?php echo esc_attr__('Production Time', 'wppluginzone-user-dashboard'); ?>">
                <button type="button" class="is-active" data-wppz-production-value="Regular (2-4 Business Days)">
                    <strong><?php echo esc_html__('Regular', 'wppluginzone-user-dashboard'); ?></strong>
                    <span><?php echo esc_html__('2-4 Business Days', 'wppluginzone-user-dashboard'); ?></span>
                </button>
                <button type="button" data-wppz-production-value="Rush (2 Business Days)">
                    <strong><?php echo esc_html__('Rush', 'wppluginzone-user-dashboard'); ?></strong>
                    <span><?php echo esc_html__('2 Business Days', 'wppluginzone-user-dashboard'); ?></span>
                    <i aria-hidden="true">?</i>
                </button>
            </div>
        </div>

        <div class="wppz-gp-accordion" data-wppz-accordion>
            <button type="button" data-wppz-accordion-toggle>
                <span><?php echo esc_html__('Process Type', 'wppluginzone-user-dashboard'); ?></span>
                <i aria-hidden="true">+</i>
            </button>
            <div class="wppz-gp-accordion-panel">
                <label><input type="radio" name="wppz_process_type_choice" value="Instant Processing Proof" checked> <?php echo esc_html__('Instant Processing Proof', 'wppluginzone-user-dashboard'); ?></label>
                <p><?php echo esc_html__('Upload a compatible file and review the instant proof before adding to cart.', 'wppluginzone-user-dashboard'); ?></p>
                <label><input type="radio" name="wppz_process_type_choice" value="Manually Processed Proof"> <?php echo esc_html__('Manually Processed Proof', 'wppluginzone-user-dashboard'); ?></label>
                <p><?php echo esc_html__('Choose this if the file needs manual review or cannot be processed instantly.', 'wppluginzone-user-dashboard'); ?></p>
            </div>
        </div>

        <div class="wppz-gp-accordion" data-wppz-accordion>
            <button type="button" data-wppz-accordion-toggle>
                <span><?php echo esc_html__('Estimated Shipping', 'wppluginzone-user-dashboard'); ?></span>
                <span class="wppz-gp-mini-help" aria-hidden="true">?</span>
                <i aria-hidden="true">+</i>
            </button>
            <div class="wppz-gp-accordion-panel">
                <p><?php echo esc_html__('Shipping will be calculated in cart/checkout after destination details are available.', 'wppluginzone-user-dashboard'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

function wppz_aframe_render_proof_panel() {
    ?>
    <div class="wppz-gp-view-proof" data-wppz-view-proof hidden>
        <h3><?php echo esc_html__('View Proof', 'wppluginzone-user-dashboard'); ?></h3>
        <div class="wppz-gp-view-proof-grid">
            <div>
                <div class="wppz-gp-proof-card">
                    <div class="wppz-gp-proof-card-head">
                        <strong><?php echo esc_html__('Front', 'wppluginzone-user-dashboard'); ?></strong>
                        <span aria-hidden="true">i</span>
                    </div>
                    <div class="wppz-gp-proof-preview" data-wppz-proof-final-preview>
                        <span><?php echo esc_html__('Front artwork proof', 'wppluginzone-user-dashboard'); ?></span>
                    </div>
                </div>
                <div class="wppz-gp-proof-tools-row">
                    <a href="#" data-wppz-download-proof><?php echo esc_html__('Download Print PDF Preview', 'wppluginzone-user-dashboard'); ?></a>
                    <label><?php echo esc_html__('Guidelines', 'wppluginzone-user-dashboard'); ?><input type="checkbox" checked data-wppz-toggle-guides></label>
                    <label><?php echo esc_html__('Trimmed Preview', 'wppluginzone-user-dashboard'); ?><input type="checkbox"></label>
                </div>
            </div>
            <div class="wppz-gp-proof-details">
                <h3><?php echo esc_html__('Quick Checklist', 'wppluginzone-user-dashboard'); ?></h3>
                <p><?php echo esc_html__('Before you proceed, confirm the following:', 'wppluginzone-user-dashboard'); ?></p>
                <ul>
                    <li><?php echo esc_html__('Information on your files is accurate and spelled correctly.', 'wppluginzone-user-dashboard'); ?></li>
                    <li><?php echo esc_html__('Text size is legible.', 'wppluginzone-user-dashboard'); ?></li>
                    <li><?php echo esc_html__('Image uploaded should extend to the black line of print area.', 'wppluginzone-user-dashboard'); ?></li>
                </ul>
                <h3><?php echo esc_html__('Product Details', 'wppluginzone-user-dashboard'); ?> <button type="button" data-wppz-edit-proof><?php echo esc_html__('Edit', 'wppluginzone-user-dashboard'); ?></button></h3>
                <dl data-wppz-proof-details></dl>
                <h3><?php echo esc_html__('Approve your Files for Print', 'wppluginzone-user-dashboard'); ?></h3>
                <label class="wppz-gp-approval"><input type="checkbox" data-wppz-approve-checkbox> <?php echo esc_html__('I approve this proof for print.', 'wppluginzone-user-dashboard'); ?></label>
            </div>
        </div>
    </div>
    <?php
}

function wppz_render_aframe_product_configurator() {
    global $product;

    if (!$product || !wppz_is_aframe_product_context($product->get_id())) {
        return;
    }

    $product_id = $product->get_id();
    $settings = wppz_gp_get_product_configurator_settings($product_id);
    $base_price = (float) $product->get_price();
    $currency_symbol = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, get_bloginfo('charset'));
    $fields = wppz_aframe_config_options($product_id);
    $frontend_settings = array(
        'fields' => $fields,
        'fieldOrder' => array_keys($fields),
        'sizeOptionsByShape' => wppz_aframe_size_options_by_shape($product_id),
        'holeOptionsByShape' => array(
            'Rectangle' => !empty($fields['hole_drilling']['options']) ? $fields['hole_drilling']['options'] : array('None'),
            'Square' => !empty($fields['hole_drilling']['options']) ? $fields['hole_drilling']['options'] : array('None'),
        ),
        'designerSubline' => $settings['designer_subline'],
    );

    ?>
    <div class="wppz-gp-configurator" data-base-price="<?php echo esc_attr($base_price); ?>" data-currency-symbol="<?php echo esc_attr($currency_symbol); ?>">
        <script type="application/json" data-wppz-config-settings><?php echo wp_json_encode($frontend_settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <?php wp_nonce_field('wppz_aframe_config', 'wppz_aframe_config_nonce'); ?>
        <input type="hidden" name="wppz_aframe_config[artwork_method]" value="No artwork selected" data-wppz-artwork-method>
        <input type="hidden" name="wppz_aframe_config[design_summary]" value="" data-wppz-design-summary>
        <input type="hidden" name="wppz_aframe_config[production_time]" value="Regular (2-4 Business Days)" data-wppz-production-time>
        <input type="hidden" name="wppz_aframe_config[process_type]" value="Instant Processing Proof" data-wppz-process-type>
        <input type="hidden" name="wppz_aframe_config[estimated_shipping]" value="Calculated at checkout" data-wppz-estimated-shipping>
        <input type="hidden" name="wppz_aframe_config[selected_file]" value="" data-wppz-selected-file-name-input>
        <input type="hidden" name="wppz_aframe_config[proof_status]" value="" data-wppz-proof-status>
        <input type="hidden" name="wppz_aframe_design_data" value="" data-wppz-design-data>
        <input type="hidden" name="wppz_aframe_existing_file_id" value="" data-wppz-existing-file-id>
        <input type="hidden" name="wppz_aframe_existing_file_url" value="" data-wppz-existing-file-url>

        <div class="wppz-gp-fields">
            <?php foreach ($fields as $key => $field) : ?>
                <?php wppz_aframe_render_config_field($key, $field); ?>
                <?php if ('material_color' === $key) : ?>
                    <?php wppz_aframe_render_acrylic_note(); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php wppz_aframe_render_post_quantity_options(); ?>

        <div class="wppz-gp-total" role="status" aria-live="polite">
            <span><?php echo esc_html__('Subtotal', 'wppluginzone-user-dashboard'); ?> <em><?php echo esc_html__('(excludes shipping)', 'wppluginzone-user-dashboard'); ?></em> :</span>
            <strong data-wppz-subtotal><?php echo $base_price ? wp_kses_post(wc_price($base_price)) : esc_html('$0.00'); ?></strong>
        </div>

        <div class="wppz-gp-actions">
            <?php if ('yes' === $settings['enable_upload']) : ?>
                <button type="button" class="wppz-gp-action-button" data-wppz-open-upload="front"><?php echo esc_html__('Upload Front', 'wppluginzone-user-dashboard'); ?></button>
            <?php endif; ?>
            <?php if ('yes' === $settings['enable_designer']) : ?>
                <button type="button" class="wppz-gp-action-button" data-wppz-open-designer><?php echo esc_html__('Design Online', 'wppluginzone-user-dashboard'); ?></button>
            <?php endif; ?>
        </div>

        <div class="wppz-gp-artwork-status" data-wppz-artwork-status><?php echo esc_html__('No artwork selected', 'wppluginzone-user-dashboard'); ?></div>

        <button type="button" class="wppz-gp-honest" data-wppz-open-honest>
            <span class="wppz-gp-honest-icon" aria-hidden="true"></span>
            <span><?php echo esc_html__('Honest Pricing Guarantee', 'wppluginzone-user-dashboard'); ?></span>
        </button>

        <?php wppz_aframe_render_upload_modal(); ?>
        <?php wppz_aframe_render_proof_editor_modal($settings); ?>
        <?php wppz_aframe_render_proof_panel(); ?>
        <?php wppz_aframe_render_designer_modal($settings); ?>
        <?php wppz_aframe_render_honest_modal(); ?>
    </div>
    <?php
}
add_action('woocommerce_before_add_to_cart_button', 'wppz_render_aframe_product_configurator', 8);

function wppz_aframe_render_upload_modal() {
    $file_manager = wppz_aframe_get_user_file_manager_items();
    ?>
    <div class="wppz-gp-modal" data-wppz-upload-modal aria-hidden="true">
        <div class="wppz-gp-modal-panel wppz-gp-upload-panel" role="dialog" aria-modal="true" aria-labelledby="wppz-gp-upload-title">
            <div class="wppz-gp-upload-tabs">
                <button type="button" class="is-active" id="wppz-gp-upload-title" data-wppz-upload-tab="upload"><?php echo esc_html__('Upload', 'wppluginzone-user-dashboard'); ?></button>
                <button type="button" data-wppz-upload-tab="files"><?php echo esc_html__('My Files', 'wppluginzone-user-dashboard'); ?></button>
                <button type="button" class="wppz-gp-modal-close" data-wppz-close-modal aria-label="<?php echo esc_attr__('Close', 'wppluginzone-user-dashboard'); ?>"></button>
            </div>

            <div class="wppz-gp-upload-body" data-wppz-upload-pane="upload">
                <input type="file" name="wppz_aframe_front_file" class="wppz-gp-file-input" data-wppz-file-input="front" accept=".tif,.tiff,.eps,.ai,.psd,.bmp,.gif,.jpg,.jpeg,.png,.pdf">
                <div class="wppz-gp-dropzone" data-wppz-dropzone tabindex="0">
                    <span class="wppz-gp-upload-cloud" aria-hidden="true"></span>
                    <p data-wppz-upload-copy><?php echo esc_html__('Drag files to upload or', 'wppluginzone-user-dashboard'); ?></p>
                    <button type="button" class="wppz-gp-browse" data-wppz-browse><?php echo esc_html__('Browse Files', 'wppluginzone-user-dashboard'); ?></button>
                    <strong data-wppz-selected-file></strong>
                </div>

                <div class="wppz-gp-upload-guidelines">
                    <strong><?php echo esc_html__('Acceptable File Formats:', 'wppluginzone-user-dashboard'); ?></strong>
                    <span><?php echo esc_html__('Max. file upload size is 75MB (megabytes) per file', 'wppluginzone-user-dashboard'); ?></span>
                    <div class="wppz-gp-format-list" aria-label="<?php echo esc_attr__('Accepted file formats', 'wppluginzone-user-dashboard'); ?>">
                        <span>TIF</span><span>TIFF</span><span>EPS</span><span>AI</span><span>PSD</span><span>BMP</span><span>GIF</span><span>JPG</span><span>PNG</span><span>PDF</span>
                    </div>
                    <p><strong><?php echo esc_html__('Recommended:', 'wppluginzone-user-dashboard'); ?></strong> <?php echo esc_html__('To reduce file size, upload in .jpg format. For other formats, flatten all layers.', 'wppluginzone-user-dashboard'); ?></p>
                </div>
            </div>

            <div class="wppz-gp-my-files" data-wppz-upload-pane="files" hidden>
                <?php if (!is_user_logged_in()) : ?>
                    <div class="wppz-gp-empty-files"><?php echo esc_html__('Please log in to choose from My Files.', 'wppluginzone-user-dashboard'); ?></div>
                <?php else : ?>
                    <div class="wppz-gp-my-files-grid">
                        <aside>
                            <button type="button" class="is-active" data-wppz-filter-folder="all">
                                <span aria-hidden="true"></span><?php echo esc_html__('Uncategorized', 'wppluginzone-user-dashboard'); ?>
                            </button>
                            <?php foreach ($file_manager['folders'] as $folder) : ?>
                                <button type="button" data-wppz-filter-folder="<?php echo esc_attr($folder->id); ?>">
                                    <span aria-hidden="true"></span><?php echo esc_html($folder->name); ?>
                                </button>
                            <?php endforeach; ?>
                            <?php if (!empty($file_manager['designs'])) : ?>
                                <button type="button" data-wppz-filter-folder="designs">
                                    <span aria-hidden="true"></span><?php echo esc_html__('Saved Designs', 'wppluginzone-user-dashboard'); ?>
                                </button>
                            <?php endif; ?>
                        </aside>
                        <div class="wppz-gp-file-table">
                            <div class="wppz-gp-file-table-head">
                                <span></span>
                                <strong><?php echo esc_html__('Name', 'wppluginzone-user-dashboard'); ?></strong>
                                <strong><?php echo esc_html__('Size', 'wppluginzone-user-dashboard'); ?></strong>
                            </div>
                            <?php if (empty($file_manager['files']) && empty($file_manager['designs'])) : ?>
                                <div class="wppz-gp-empty-files"><?php echo esc_html__('No files or saved designs found.', 'wppluginzone-user-dashboard'); ?></div>
                            <?php endif; ?>
                            <?php foreach ($file_manager['files'] as $file) : ?>
                                <button type="button" class="wppz-gp-file-row" data-wppz-my-file data-folder-id="<?php echo esc_attr($file->folder_id); ?>" data-file-id="<?php echo esc_attr($file->id); ?>" data-file-name="<?php echo esc_attr($file->original_name); ?>" data-file-url="<?php echo esc_url($file->file_url); ?>" data-file-type="<?php echo esc_attr($file->mime_type); ?>">
                                    <span></span>
                                    <strong><?php echo esc_html($file->original_name); ?></strong>
                                    <em><?php echo esc_html(wppz_aframe_format_file_size($file->file_size)); ?></em>
                                </button>
                            <?php endforeach; ?>
                            <?php foreach ($file_manager['designs'] as $design) : ?>
                                <?php $design_name = $design->design_name ? $design->design_name : 'my-design_' . date('Ymd_His', strtotime($design->created_date)); ?>
                                <button type="button" class="wppz-gp-file-row" data-wppz-saved-design data-folder-id="designs" data-design-id="<?php echo esc_attr($design->id); ?>" data-file-name="<?php echo esc_attr($design_name); ?>">
                                    <span></span>
                                    <strong><?php echo esc_html($design_name); ?></strong>
                                    <em><?php echo esc_html($design->product_type ?: __('Saved Design', 'wppluginzone-user-dashboard')); ?></em>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function wppz_aframe_render_proof_editor_modal($settings = array()) {
    $settings = wp_parse_args($settings, wppz_gp_default_configurator_settings());
    $proof_width = $settings['proof_width'] ? $settings['proof_width'] : '10.875';
    $proof_height = $settings['proof_height'] ? $settings['proof_height'] : '8.375';
    ?>
    <div class="wppz-gp-modal" data-wppz-proof-modal aria-hidden="true">
        <div class="wppz-gp-modal-panel wppz-gp-proof-panel" role="dialog" aria-modal="true" aria-labelledby="wppz-gp-proof-title">
            <div class="wppz-gp-proof-titlebar">
                <h3 id="wppz-gp-proof-title"><?php echo esc_html__('Instant Processing Proof', 'wppluginzone-user-dashboard'); ?></h3>
                <button type="button" class="wppz-gp-modal-close" data-wppz-close-modal aria-label="<?php echo esc_attr__('Close', 'wppluginzone-user-dashboard'); ?>"></button>
                <button type="button" class="wppz-gp-proof-info" data-wppz-help="<?php echo esc_attr__('Review positioning and scale before continuing to proof approval.', 'wppluginzone-user-dashboard'); ?>">i</button>
            </div>
            <div class="wppz-gp-proof-editor">
                <aside><?php echo esc_html__('View Guidelines', 'wppluginzone-user-dashboard'); ?></aside>
                <div class="wppz-gp-proof-workspace">
                    <div class="wppz-gp-proof-stage" data-wppz-proof-stage>
                        <span class="wppz-gp-measure is-v"><?php echo esc_html($proof_height); ?></span>
                        <span class="wppz-gp-measure is-h"><?php echo esc_html($proof_width); ?></span>
                        <div class="wppz-gp-proof-art" data-wppz-proof-preview>
                            <span><?php echo esc_html__('Artwork Preview', 'wppluginzone-user-dashboard'); ?></span>
                            <i class="wppz-gp-proof-handle is-nw" aria-hidden="true"></i>
                            <i class="wppz-gp-proof-handle is-ne" aria-hidden="true"></i>
                            <i class="wppz-gp-proof-handle is-sw" aria-hidden="true"></i>
                            <i class="wppz-gp-proof-handle is-se" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="wppz-gp-quick-preview">
                        <span><?php echo esc_html__('Quick Preview', 'wppluginzone-user-dashboard'); ?></span>
                        <label><input type="checkbox" data-wppz-quick-preview><i><?php echo esc_html__('No', 'wppluginzone-user-dashboard'); ?></i></label>
                    </div>
                    <div class="wppz-gp-proof-toolbar">
                        <button type="button" data-wppz-proof-tool="reset"><?php echo esc_html__('Reset', 'wppluginzone-user-dashboard'); ?></button>
                        <button type="button" data-wppz-proof-tool="orig"><?php echo esc_html__('Orig', 'wppluginzone-user-dashboard'); ?></button>
                        <button type="button" data-wppz-proof-tool="center"><?php echo esc_html__('Center', 'wppluginzone-user-dashboard'); ?></button>
                        <button type="button" data-wppz-proof-tool="fit"><?php echo esc_html__('Fit', 'wppluginzone-user-dashboard'); ?></button>
                        <button type="button" data-wppz-proof-tool="left"><?php echo esc_html__('Left', 'wppluginzone-user-dashboard'); ?></button>
                        <button type="button" data-wppz-proof-tool="right"><?php echo esc_html__('Right', 'wppluginzone-user-dashboard'); ?></button>
                        <label><input type="checkbox" checked> <?php echo esc_html__('Scale Proportionally', 'wppluginzone-user-dashboard'); ?></label>
                        <label><input type="checkbox" checked> <?php echo esc_html__('Snap to guides', 'wppluginzone-user-dashboard'); ?></label>
                    </div>
                    <p class="wppz-gp-proof-approval-copy"><?php echo esc_html__('By continuing, you are approving this design.', 'wppluginzone-user-dashboard'); ?></p>
                    <button type="button" class="wppz-gp-action-button" data-wppz-view-proof-action><?php echo esc_html__('View Proofs', 'wppluginzone-user-dashboard'); ?></button>
                    <button type="button" class="wppz-gp-proof-cancel" data-wppz-close-modal><?php echo esc_html__('Cancel', 'wppluginzone-user-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function wppz_aframe_render_designer_modal($settings = array()) {
    $settings = wp_parse_args($settings, wppz_gp_default_configurator_settings());
    $designer_subline = $settings['designer_subline'] ? $settings['designer_subline'] : 'Acrylic Board';
    ?>
    <div class="wppz-gp-modal" data-wppz-designer-modal aria-hidden="true">
        <div class="wppz-gp-modal-panel wppz-gp-designer-panel" role="dialog" aria-modal="true" aria-labelledby="wppz-gp-designer-title">
            <div class="wppz-gp-modal-header">
                <h3 id="wppz-gp-designer-title"><?php echo esc_html__('Design Online', 'wppluginzone-user-dashboard'); ?></h3>
                <button type="button" class="wppz-gp-modal-close" data-wppz-close-modal aria-label="<?php echo esc_attr__('Close', 'wppluginzone-user-dashboard'); ?>"></button>
            </div>
            <div class="wppz-gp-designer-grid">
                <div class="wppz-gp-design-canvas" data-wppz-design-preview>
                    <span data-wppz-design-headline><?php echo esc_html__('YOUR MESSAGE', 'wppluginzone-user-dashboard'); ?></span>
                    <small data-wppz-design-subline><?php echo esc_html($designer_subline); ?></small>
                </div>
                <div class="wppz-gp-design-controls">
                    <label for="wppz_design_template"><?php echo esc_html__('Template', 'wppluginzone-user-dashboard'); ?></label>
                    <select id="wppz_design_template" data-wppz-design-template>
                        <option value="Retail Promo"><?php echo esc_html__('Retail Promo', 'wppluginzone-user-dashboard'); ?></option>
                        <option value="Directional"><?php echo esc_html__('Directional', 'wppluginzone-user-dashboard'); ?></option>
                        <option value="Event Notice"><?php echo esc_html__('Event Notice', 'wppluginzone-user-dashboard'); ?></option>
                    </select>

                    <label for="wppz_design_headline"><?php echo esc_html__('Headline', 'wppluginzone-user-dashboard'); ?></label>
                    <input id="wppz_design_headline" type="text" value="YOUR MESSAGE" maxlength="42" data-wppz-design-headline-input>

                    <label for="wppz_design_subline"><?php echo esc_html__('Subline', 'wppluginzone-user-dashboard'); ?></label>
                    <input id="wppz_design_subline" type="text" value="<?php echo esc_attr($designer_subline); ?>" maxlength="58" data-wppz-design-subline-input>

                    <label for="wppz_design_color"><?php echo esc_html__('Background', 'wppluginzone-user-dashboard'); ?></label>
                    <select id="wppz_design_color" data-wppz-design-color>
                        <option value="#ffffff"><?php echo esc_html__('White', 'wppluginzone-user-dashboard'); ?></option>
                        <option value="#111827"><?php echo esc_html__('Black', 'wppluginzone-user-dashboard'); ?></option>
                        <option value="#319244"><?php echo esc_html__('Green', 'wppluginzone-user-dashboard'); ?></option>
                        <option value="#ff6a3d"><?php echo esc_html__('Orange', 'wppluginzone-user-dashboard'); ?></option>
                    </select>

                    <button type="button" class="wppz-gp-action-button" data-wppz-save-design><?php echo esc_html__('Use This Design', 'wppluginzone-user-dashboard'); ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function wppz_aframe_render_honest_modal() {
    ?>
    <div class="wppz-gp-modal" data-wppz-honest-modal aria-hidden="true">
        <div class="wppz-gp-modal-panel wppz-gp-info-panel" role="dialog" aria-modal="true" aria-labelledby="wppz-gp-honest-title">
            <div class="wppz-gp-modal-header">
                <h3 id="wppz-gp-honest-title"><?php echo esc_html__('Honest Pricing Guarantee', 'wppluginzone-user-dashboard'); ?></h3>
                <button type="button" class="wppz-gp-modal-close" data-wppz-close-modal aria-label="<?php echo esc_attr__('Close', 'wppluginzone-user-dashboard'); ?>"></button>
            </div>
            <div class="wppz-gp-info-body">
                <h4><?php echo esc_html__('Low Price Promise', 'wppluginzone-user-dashboard'); ?></h4>
                <p><?php echo esc_html__('Your selected acrylic board configuration is carried into the cart so production can review the exact specifications with the order.', 'wppluginzone-user-dashboard'); ?></p>
                <h4><?php echo esc_html__('Clear Order Details', 'wppluginzone-user-dashboard'); ?></h4>
                <p><?php echo esc_html__('Material, thickness, shape, size, orientation, print side, hole drilling, accessories, quantity, uploaded artwork, and online design notes are stored with the cart item and order line item.', 'wppluginzone-user-dashboard'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

function wppz_enqueue_aframe_configurator_assets() {
    if (!function_exists('is_product') || !is_product() || !wppz_is_aframe_product_context()) {
        return;
    }

    wp_register_style('wppz-aframe-configurator', '', array(), '1.0.9');
    wp_enqueue_style('wppz-aframe-configurator');
    wp_add_inline_style('wppz-aframe-configurator', wppz_aframe_configurator_css());

    wp_enqueue_script('jquery');
    wp_register_script('wppz-aframe-configurator', '', array('jquery'), '1.0.9', true);
    wp_enqueue_script('wppz-aframe-configurator');
    wp_add_inline_script('wppz-aframe-configurator', wppz_aframe_configurator_js());
}
add_action('wp_enqueue_scripts', 'wppz_enqueue_aframe_configurator_assets', 30);

function wppz_aframe_configurator_css() {
    return <<<'CSS'
.wppz-gp-configurator {
    --wppz-gp-green: #8ec647;
    --wppz-gp-dark-green: #319244;
    --wppz-gp-text: #30343b;
    --wppz-gp-muted: #6d7178;
    --wppz-gp-border: #8ec647;
    --wppz-gp-soft: #f7faf4;
    color: var(--wppz-gp-text);
    font-size: 16px;
    line-height: 1.45;
    max-width: 560px;
    margin: 22px 0;
}

body.wppz-gp-proof-mode .wppz-gp-configurator {
    max-width: none;
    width: 100%;
}

body.wppz-gp-proof-mode .wppz-gp-configurator[data-step="view-proof"] {
    width: var(--wppz-proof-width, min(1180px, calc(100vw - 32px))) !important;
    max-width: var(--wppz-proof-width, min(1180px, calc(100vw - 32px))) !important;
    transform: translateX(var(--wppz-proof-offset, 0));
}

body.wppz-gp-proof-mode.single-product div.product {
    display: block !important;
    overflow: visible !important;
}

body.wppz-gp-proof-mode.single-product div.product div.images,
body.wppz-gp-proof-mode.single-product div.product .woocommerce-product-gallery,
body.wppz-gp-proof-mode.single-product div.product .product_title,
body.wppz-gp-proof-mode.single-product div.product .price,
body.wppz-gp-proof-mode.single-product div.product .woocommerce-product-details__short-description {
    display: none !important;
}

body.wppz-gp-proof-mode.single-product div.product div.summary {
    float: none !important;
    width: 100% !important;
    max-width: 1180px;
    margin: 0 auto !important;
    overflow: visible !important;
}

body.wppz-gp-proof-mode.single-product form.cart {
    overflow: visible !important;
}

.wppz-gp-proof-hidden {
    display: none !important;
}

.single-product.postid-3305 .wapf-wrapper,
.single-product.postid-3305 .wapf-field-container,
.single-product.postid-3305 .wapf-product-totals {
    display: none !important;
}

.single-product.postid-3305 form.cart .quantity {
    display: none !important;
}

.wppz-gp-fields {
    display: grid;
    gap: 18px;
}

.wppz-gp-field,
.wppz-gp-radio-field,
.wppz-gp-orientation-field {
    position: relative;
    width: 100%;
    min-height: 54px;
    border: 2px solid var(--wppz-gp-border);
    border-radius: 6px;
    background: #fff;
    box-shadow: none;
    margin: 0;
}

.wppz-gp-field label,
.wppz-gp-radio-field legend,
.wppz-gp-orientation-field legend {
    position: absolute;
    top: -12px;
    left: 16px;
    z-index: 2;
    margin: 0;
    padding: 0 7px;
    background: #fff;
    color: var(--wppz-gp-text);
    font-size: 15px;
    font-weight: 700;
    line-height: 20px;
}

.wppz-gp-field select {
    width: 100%;
    min-height: 52px;
    border: 0 !important;
    box-shadow: none !important;
    color: var(--wppz-gp-text);
    font-size: 16px;
    line-height: 24px;
    padding: 13px 54px 13px 12px;
    background-color: transparent;
}

.wppz-gp-field select:focus,
.wppz-gp-design-controls input:focus,
.wppz-gp-design-controls select:focus {
    outline: 2px solid rgba(49, 146, 68, 0.18);
    outline-offset: 2px;
}

.wppz-gp-valid {
    position: absolute;
    top: 14px;
    right: 38px;
    width: 23px;
    height: 23px;
    border-radius: 50%;
    background: var(--wppz-gp-green);
}

.wppz-gp-valid::after {
    content: "";
    position: absolute;
    left: 7px;
    top: 4px;
    width: 7px;
    height: 12px;
    border: solid #fff;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
}

.wppz-gp-required-badge {
    position: absolute;
    top: 17px;
    right: 14px;
    color: #eb0026;
    font-size: 13px;
    font-weight: 700;
    line-height: 18px;
}

.wppz-gp-field.is-required.is-empty {
    border-color: #eb0026;
}

.wppz-gp-field.is-required .wppz-gp-valid,
.wppz-gp-field.is-required.is-complete .wppz-gp-required-badge {
    display: none;
}

.wppz-gp-field.is-required.is-complete {
    border-color: var(--wppz-gp-border);
}

.wppz-gp-field.is-required.is-complete .wppz-gp-valid {
    display: block;
}

.wppz-gp-radio-field {
    padding: 18px 14px 12px;
}

.wppz-gp-radio-options {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 24px;
    align-items: center;
}

.wppz-gp-radio-option {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 24px;
    margin: 0;
    color: var(--wppz-gp-text);
    font-size: 16px;
    cursor: pointer;
}

.wppz-gp-radio-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.wppz-gp-radio-mark {
    width: 20px;
    height: 20px;
    border: 3px solid #9dcc69;
    border-radius: 50%;
    background: #fff;
    box-sizing: border-box;
}

.wppz-gp-radio-option input:checked + .wppz-gp-radio-mark {
    border-color: var(--wppz-gp-green);
    box-shadow: inset 0 0 0 4px #fff;
    background: var(--wppz-gp-green);
}

.wppz-gp-orientation-field {
    min-height: 50px;
}

.wppz-gp-orientation-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    min-height: 48px;
}

.wppz-gp-orientation-option {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 11px;
    min-width: 0;
    margin: 0;
    color: #9a9a9a;
    font-size: 16px;
    cursor: pointer;
}

.wppz-gp-orientation-option:first-child {
    border-right: 1px solid #d1d7cf;
}

.wppz-gp-orientation-option input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.wppz-gp-orientation-icon {
    display: inline-block;
    width: 28px;
    height: 15px;
    background: #e9e9e9;
}

.wppz-gp-orientation-icon.is-vertical {
    width: 15px;
    height: 28px;
}

.wppz-gp-orientation-option input:checked + .wppz-gp-orientation-icon {
    background: #cfe8a9;
}

.wppz-gp-orientation-option input:checked ~ span:last-child {
    color: var(--wppz-gp-text);
}

.wppz-gp-help {
    position: absolute;
    top: 12px;
    right: 14px;
    z-index: 3;
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #666;
    font-weight: 700;
    box-shadow: none;
    padding: 0;
}

.wppz-gp-help span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border: 2px solid currentColor;
    border-radius: 50%;
    font-size: 14px;
    line-height: 1;
}

.wppz-gp-help-bubble {
    position: absolute;
    z-index: 99;
    max-width: 320px;
    padding: 12px 14px;
    border-radius: 6px;
    background: #30343b;
    color: #fff;
    font-size: 13px;
    line-height: 1.45;
    box-shadow: 0 12px 28px rgba(0,0,0,.18);
}

.wppz-gp-note {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    min-height: 82px;
    border: 1px solid var(--wppz-gp-border);
    border-radius: 3px;
    background: #fff;
    overflow: hidden;
}

.wppz-gp-note-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--wppz-gp-green);
    color: transparent;
    font-size: 20px;
    font-weight: 800;
    font-family: Georgia, serif;
    text-transform: lowercase;
}

.wppz-gp-note-icon::before {
    content: "";
    position: absolute;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
}

.wppz-gp-note-icon::after {
    content: "i";
    position: relative;
    color: var(--wppz-gp-green);
    z-index: 1;
}

.wppz-gp-note p {
    margin: 0;
    padding: 13px 12px;
    color: var(--wppz-gp-text);
    font-size: 16px;
    line-height: 1.32;
}

.wppz-gp-note strong {
    font-weight: 800;
}

.wppz-gp-total {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding: 13px 14px;
    border-radius: 4px;
    background: #f3f4f5;
    color: var(--wppz-gp-text);
}

.wppz-gp-total span {
    font-size: 15px;
}

.wppz-gp-total em {
    color: var(--wppz-gp-muted);
    font-style: normal;
}

.wppz-gp-total strong {
    min-width: 86px;
    text-align: right;
}

.wppz-gp-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
    justify-content: center;
}

.wppz-gp-action-button,
.wppz-gp-browse {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    min-height: 44px;
    border: 1px solid #226a9c;
    border-radius: 999px;
    background: #367fb4;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    padding: 11px 24px;
    text-align: center;
    box-shadow: none;
    cursor: pointer;
}

.wppz-gp-action-button:hover,
.wppz-gp-action-button:focus,
.wppz-gp-browse:hover,
.wppz-gp-browse:focus {
    background: #226a9c;
    color: #fff;
}

.wppz-gp-after-quantity[hidden],
.wppz-gp-view-proof[hidden] {
    display: none !important;
}

.wppz-gp-after-quantity {
    display: grid;
    gap: 22px;
    margin-top: 22px;
}

.wppz-gp-production-title {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 0 0 4px 20px;
    font-size: 16px;
    font-weight: 800;
}

.wppz-gp-mini-help,
.wppz-gp-proof-info {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 21px;
    height: 21px;
    border: 0;
    border-radius: 50%;
    background: var(--wppz-gp-green);
    color: #fff;
    font-weight: 800;
    line-height: 1;
    padding: 0;
}

.wppz-gp-production-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    border: 2px solid var(--wppz-gp-border);
    border-radius: 5px;
    overflow: hidden;
}

.wppz-gp-production-options button {
    position: relative;
    min-height: 62px;
    border: 0;
    background: #fff;
    color: var(--wppz-gp-text);
    box-shadow: none;
    padding: 9px 14px;
}

.wppz-gp-production-options button.is-active {
    background: var(--wppz-gp-green);
}

.wppz-gp-production-options strong,
.wppz-gp-production-options span {
    display: block;
    font-size: 16px;
    line-height: 1.25;
}

.wppz-gp-production-options i {
    position: absolute;
    top: 10px;
    right: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: var(--wppz-gp-green);
    color: #fff;
    font-style: normal;
    font-weight: 800;
}

.wppz-gp-accordion {
    border: 1px solid #c9cdd2;
    border-radius: 5px;
    background: #f7f7f7;
    overflow: hidden;
}

.wppz-gp-accordion > button {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-height: 42px;
    border: 0;
    background: transparent;
    color: var(--wppz-gp-text);
    font-weight: 800;
    text-align: left;
    padding: 10px 14px;
    box-shadow: none;
}

.wppz-gp-accordion-panel {
    display: none;
    border-top: 1px solid #d7dade;
    background: #fff;
    padding: 14px;
}

.wppz-gp-accordion.is-open .wppz-gp-accordion-panel {
    display: block;
}

.wppz-gp-accordion-panel label {
    display: block;
    margin: 0 0 4px;
    font-weight: 700;
}

.wppz-gp-accordion-panel p {
    margin: 0 0 12px 22px;
    color: var(--wppz-gp-muted);
    font-size: 14px;
}

.wppz-gp-artwork-status {
    min-height: 22px;
    margin-top: 10px;
    color: var(--wppz-gp-muted);
    font-size: 14px;
}

.wppz-gp-honest {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 16px auto 0;
    border: 0;
    background: transparent;
    color: var(--wppz-gp-text);
    font-weight: 700;
    box-shadow: none;
    padding: 0;
}

.wppz-gp-honest-icon {
    position: relative;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid var(--wppz-gp-green);
}

.wppz-gp-honest-icon::before,
.wppz-gp-honest-icon::after {
    content: "";
    position: absolute;
    left: 6px;
    right: 6px;
    top: 11px;
    border-top: 2px solid var(--wppz-gp-green);
}

.wppz-gp-honest-icon::after {
    transform: rotate(90deg);
}

.wppz-gp-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 100000;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(0,0,0,.72);
}

.wppz-gp-modal[aria-hidden="false"] {
    display: flex;
}

.wppz-gp-modal-panel {
    width: min(690px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    border-radius: 8px;
    background: #fff;
    color: var(--wppz-gp-text);
    box-shadow: 0 28px 70px rgba(0,0,0,.32);
}

.wppz-gp-upload-panel {
    min-height: 560px;
}

.wppz-gp-upload-tabs {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    width: calc(100% - 40px);
    height: 54px;
    margin: 22px auto 8px;
    border-radius: 999px;
    background: #e7f3db;
}

.wppz-gp-upload-tabs button:not(.wppz-gp-modal-close) {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    border: 0;
    border-radius: 999px;
    background: var(--wppz-gp-green);
    color: #111;
    font-size: 19px;
    font-weight: 700;
    box-shadow: none;
}

.wppz-gp-upload-tabs button.is-active {
    border: 3px solid var(--wppz-gp-green);
    background: #fff;
    color: #4a4a4a;
}

.wppz-gp-modal-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 60px;
    padding: 18px 22px;
    background: var(--wppz-gp-green);
    color: #fff;
}

.wppz-gp-modal-header h3 {
    margin: 0;
    color: #fff;
    font-size: 22px;
}

.wppz-gp-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 50%;
    background: transparent;
    box-shadow: none;
    padding: 0;
}

.wppz-gp-upload-tabs .wppz-gp-modal-close {
    top: -11px;
    right: -14px;
}

.wppz-gp-modal-close::before,
.wppz-gp-modal-close::after {
    content: "";
    position: absolute;
    left: 8px;
    right: 8px;
    top: 15px;
    border-top: 2px solid currentColor;
    color: #fff;
}

.wppz-gp-modal-close::before {
    transform: rotate(45deg);
}

.wppz-gp-modal-close::after {
    transform: rotate(-45deg);
}

.wppz-gp-upload-body {
    padding: 22px 26px 28px;
    text-align: center;
}

.wppz-gp-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.wppz-gp-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 210px;
    border: 2px dashed #cbd6c1;
    border-radius: 8px;
    background: #fbfcfa;
    padding: 24px;
}

.wppz-gp-dropzone.is-dragover {
    border-color: var(--wppz-gp-green);
    background: var(--wppz-gp-soft);
}

.wppz-gp-upload-cloud {
    position: relative;
    width: 74px;
    height: 48px;
    margin-bottom: 14px;
    border-radius: 26px;
    background: #d9e8ce;
}

.wppz-gp-upload-cloud::before,
.wppz-gp-upload-cloud::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: #d9e8ce;
}

.wppz-gp-upload-cloud::before {
    width: 36px;
    height: 36px;
    left: 10px;
    top: -18px;
}

.wppz-gp-upload-cloud::after {
    width: 46px;
    height: 46px;
    right: 8px;
    top: -24px;
}

.wppz-gp-dropzone p {
    margin: 0 0 8px;
    color: var(--wppz-gp-muted);
}

.wppz-gp-dropzone strong {
    display: block;
    margin-top: 12px;
    color: var(--wppz-gp-text);
}

.wppz-gp-upload-guidelines {
    margin-top: 24px;
    color: var(--wppz-gp-text);
}

.wppz-gp-upload-guidelines > span {
    display: block;
    color: var(--wppz-gp-muted);
    margin: 2px 0 12px;
}

.wppz-gp-format-list {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 7px;
    margin: 12px 0;
}

.wppz-gp-format-list span {
    position: relative;
    min-width: 38px;
    border: 2px solid #5b85bd;
    border-radius: 4px;
    background: #fff;
    color: #48505a;
    font-size: 12px;
    font-weight: 700;
    padding: 13px 5px 4px;
}

.wppz-gp-format-list span::before {
    content: "";
    position: absolute;
    top: -2px;
    right: -2px;
    border-width: 0 13px 13px 0;
    border-style: solid;
    border-color: transparent #8aa7d5 transparent transparent;
}

.wppz-gp-upload-guidelines p {
    margin: 12px auto 0;
    max-width: 520px;
    color: var(--wppz-gp-muted);
}

.wppz-gp-modal-actions {
    margin-top: 20px;
}

.wppz-gp-my-files {
    min-height: 600px;
    padding: 26px 26px 34px;
}

.wppz-gp-my-files-grid {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 28px;
}

.wppz-gp-my-files aside {
    display: grid;
    align-content: start;
    gap: 10px;
}

.wppz-gp-my-files aside button,
.wppz-gp-file-row {
    display: grid;
    align-items: center;
    border: 0;
    background: transparent;
    box-shadow: none;
    color: #555;
    text-align: left;
    padding: 10px 0;
}

.wppz-gp-my-files aside button {
    grid-template-columns: 25px minmax(0, 1fr);
    border-bottom: 1px solid #d8d8d8;
}

.wppz-gp-my-files aside button span,
.wppz-gp-file-row span {
    width: 22px;
    height: 16px;
    border-radius: 3px 3px 2px 2px;
    background: var(--wppz-gp-green);
}

.wppz-gp-file-table-head,
.wppz-gp-file-row {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 90px;
    gap: 10px;
    width: 100%;
}

.wppz-gp-file-table-head {
    align-items: center;
    min-height: 45px;
    background: #f2f2f2;
    color: #b0b0b0;
    padding: 0 12px;
}

.wppz-gp-file-table-head span {
    width: 22px;
    height: 22px;
    border: 2px solid #e0e0e0;
    border-radius: 2px;
}

.wppz-gp-file-row {
    border-bottom: 1px solid #eee;
    padding: 12px;
}

.wppz-gp-file-row:hover,
.wppz-gp-file-row.is-selected {
    background: #f4faef;
}

.wppz-gp-file-row strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wppz-gp-file-row em {
    color: var(--wppz-gp-muted);
    font-style: normal;
    font-size: 13px;
}

.wppz-gp-empty-files {
    padding: 34px 14px;
    color: var(--wppz-gp-muted);
    text-align: center;
}

.wppz-gp-modal[data-wppz-proof-modal] {
    align-items: flex-start;
    justify-content: center;
    padding: 0 10px;
}

.wppz-gp-proof-panel {
    width: min(878px, calc(100vw - 20px));
    max-height: 100vh;
    border-radius: 0;
}

.wppz-gp-proof-titlebar {
    position: relative;
    min-height: 38px;
    background: var(--wppz-gp-green);
    color: #fff;
    text-align: center;
}

.wppz-gp-proof-titlebar h3 {
    margin: 0;
    color: #fff;
    font-size: 22px;
    line-height: 38px;
    font-weight: 800;
}

.wppz-gp-proof-titlebar .wppz-gp-modal-close {
    top: 3px;
}

.wppz-gp-proof-info {
    position: absolute;
    top: 24px;
    right: 34px;
    width: 38px;
    height: 38px;
    background: #d9362d;
    font-family: Georgia, serif;
    font-size: 24px;
}

.wppz-gp-proof-editor {
    display: grid;
    grid-template-columns: 70px minmax(0, 1fr);
    min-height: 760px;
}

.wppz-gp-proof-editor aside {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #aaa;
    color: #fff;
    font-weight: 800;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
}

.wppz-gp-proof-workspace {
    display: grid;
    justify-items: center;
    align-content: start;
    gap: 18px;
    padding: 82px 32px 28px;
}

.wppz-gp-proof-stage,
.wppz-gp-proof-card .wppz-gp-proof-preview {
    position: relative;
    width: min(520px, 100%);
    height: 397px;
    aspect-ratio: auto;
    border: 1px solid #111;
    background: #fff;
}

.wppz-gp-proof-stage::before,
.wppz-gp-proof-card .wppz-gp-proof-preview::before {
    content: "";
    position: absolute;
    inset: 6px;
    border: 1px solid #ff4ca0;
}

.wppz-gp-proof-stage::after,
.wppz-gp-proof-card .wppz-gp-proof-preview::after {
    content: "";
    position: absolute;
    inset: 52px 40px 58px;
    border: 1px solid #33c4ff;
}

.wppz-gp-proof-stage .wppz-gp-measure {
    position: absolute;
    z-index: 3;
    color: #666;
    font-size: 13px;
    line-height: 1;
}

.wppz-gp-proof-stage .wppz-gp-measure.is-v {
    left: -60px;
    top: 44px;
    bottom: 10px;
    display: flex;
    align-items: center;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
}

.wppz-gp-proof-stage .wppz-gp-measure.is-v::before {
    content: "";
    position: absolute;
    top: -8px;
    bottom: -8px;
    left: 50%;
    border-left: 1px solid #c8c8c8;
    z-index: -1;
}

.wppz-gp-proof-stage .wppz-gp-measure.is-v::after {
    content: "";
    position: absolute;
    top: -9px;
    left: calc(50% - 4px);
    width: 8px;
    height: calc(100% + 18px);
    background:
        linear-gradient(135deg, transparent 45%, #c8c8c8 46%, #c8c8c8 55%, transparent 56%) top center / 8px 8px no-repeat,
        linear-gradient(-45deg, transparent 45%, #c8c8c8 46%, #c8c8c8 55%, transparent 56%) bottom center / 8px 8px no-repeat;
    z-index: -1;
}

.wppz-gp-proof-stage .wppz-gp-measure.is-h {
    left: 0;
    right: 0;
    bottom: -27px;
    text-align: center;
}

.wppz-gp-proof-stage .wppz-gp-measure.is-h::before {
    content: "";
    position: absolute;
    left: 6px;
    right: 6px;
    top: 50%;
    border-top: 1px solid #c8c8c8;
    z-index: -1;
}

.wppz-gp-proof-stage .wppz-gp-measure.is-h::after {
    content: "";
    position: absolute;
    left: 4px;
    right: 4px;
    top: calc(50% - 4px);
    height: 8px;
    background:
        linear-gradient(45deg, transparent 45%, #c8c8c8 46%, #c8c8c8 55%, transparent 56%) left center / 8px 8px no-repeat,
        linear-gradient(-135deg, transparent 45%, #c8c8c8 46%, #c8c8c8 55%, transparent 56%) right center / 8px 8px no-repeat;
    z-index: -1;
}

.wppz-gp-proof-art {
    position: absolute;
    left: 40px;
    right: 40px;
    top: 42px;
    height: 310px;
    z-index: 2;
    border: 2px solid #6aa2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    overflow: hidden;
    text-align: center;
    color: var(--wppz-gp-text);
}

.wppz-gp-proof-art img,
.wppz-gp-proof-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
}

.wppz-gp-proof-art.is-left {
    left: 12px;
    right: 68px;
}

.wppz-gp-proof-art.is-right {
    left: 68px;
    right: 12px;
}

.wppz-gp-proof-art.is-fit {
    left: 22px;
    right: 22px;
    top: 26px;
    height: 344px;
}

.wppz-gp-proof-art.is-original {
    left: 40px;
    right: 40px;
    top: 42px;
    height: 310px;
}

.wppz-gp-proof-handle {
    position: absolute;
    width: 12px;
    height: 12px;
    background: #6aa2ff;
    z-index: 4;
}

.wppz-gp-proof-handle.is-nw {
    left: -7px;
    top: -7px;
}

.wppz-gp-proof-handle.is-ne {
    right: -7px;
    top: -7px;
}

.wppz-gp-proof-handle.is-sw {
    left: -7px;
    bottom: -7px;
}

.wppz-gp-proof-handle.is-se {
    right: -7px;
    bottom: -7px;
}

.wppz-gp-quick-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 17px;
}

.wppz-gp-quick-preview label {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 50px;
    border-radius: 4px;
    background: #4d4d4d;
    color: #fff;
    font-size: 12px;
    padding: 3px 6px;
    position: relative;
}

.wppz-gp-quick-preview input {
    appearance: none;
    width: 20px;
    height: 14px;
    margin: 0 3px 0 0;
    border-radius: 3px;
    background: #fff;
    position: relative;
}

.wppz-gp-quick-preview input:checked {
    background: #af4bd3;
}

.wppz-gp-quick-preview input:checked::after {
    content: "";
    position: absolute;
    left: 5px;
    top: 1px;
    width: 6px;
    height: 10px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.wppz-gp-proof-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px 12px;
    width: 100%;
    border-top: 10px solid #f2f2f2;
    border-bottom: 10px solid #f2f2f2;
    padding: 16px 8px 12px;
}

.wppz-gp-proof-toolbar button {
    width: 45px;
    min-height: 62px;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    color: var(--wppz-gp-text);
    font-size: 14px;
    padding: 0;
}

.wppz-gp-proof-toolbar button::before {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 43px;
    height: 43px;
    margin: 0 auto 4px;
    border: 1px solid #333;
    border-radius: 4px;
    background: #fff;
    color: #111;
    font-size: 24px;
    line-height: 1;
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="reset"]::before {
    content: "\21bb";
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="orig"]::before {
    content: "100%";
    font-size: 9px;
    border-radius: 50%;
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="center"]::before {
    content: "\2922";
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="fit"]::before {
    content: "\26F6";
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="left"]::before {
    content: "\21ba";
}

.wppz-gp-proof-toolbar button[data-wppz-proof-tool="right"]::before {
    content: "\21bb";
}

.wppz-gp-proof-toolbar label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 6px;
    font-size: 17px;
}

.wppz-gp-proof-toolbar input,
.wppz-gp-approval input {
    accent-color: var(--wppz-gp-green);
    width: 22px;
    height: 22px;
}

.wppz-gp-proof-approval-copy {
    margin: 0;
    font-size: 16px;
}

.wppz-gp-proof-workspace > .wppz-gp-action-button {
    min-width: 192px;
    margin-top: 0;
}

.wppz-gp-proof-cancel {
    border: 0;
    background: transparent;
    box-shadow: none;
    color: var(--wppz-gp-blue);
    font-size: 15px;
    line-height: 1.2;
    padding: 0;
    cursor: pointer;
}

.wppz-gp-proof-cancel:hover,
.wppz-gp-proof-cancel:focus {
    color: #1d5f98;
    text-decoration: underline;
}

.wppz-gp-view-proof {
    margin-top: 26px;
    width: 100%;
}

.wppz-gp-view-proof > h3 {
    margin: 0 0 18px;
    font-size: 22px;
}

.wppz-gp-view-proof-grid {
    display: grid;
    grid-template-columns: minmax(360px, 480px) minmax(320px, 1fr);
    gap: 52px;
    align-items: start;
}

.wppz-gp-proof-card {
    border: 1px solid var(--wppz-gp-green);
}

.wppz-gp-proof-card-head {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 44px;
    background: var(--wppz-gp-green);
}

.wppz-gp-proof-card-head span {
    position: absolute;
    right: 14px;
    top: 21px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #d9362d;
    color: #fff;
    font-family: Georgia, serif;
    font-weight: 800;
    font-size: 24px;
}

.wppz-gp-proof-card .wppz-gp-proof-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    width: min(336px, calc(100% - 70px));
    height: 257px;
    margin: 22px auto;
}

.wppz-gp-proof-card .wppz-gp-proof-preview img,
.wppz-gp-proof-card .wppz-gp-proof-preview span {
    position: relative;
    z-index: 2;
}

.wppz-gp-proof-tools-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: center;
    margin-top: 10px;
}

.wppz-gp-proof-details h3 {
    margin: 0 0 6px;
    font-size: 24px;
}

.wppz-gp-proof-details p {
    margin: 0 0 14px;
}

.wppz-gp-proof-details li {
    margin: 0 0 5px;
}

.wppz-gp-proof-details dl {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    column-gap: 4px;
    row-gap: 4px;
    margin: 22px 0 34px;
}

.wppz-gp-proof-details dt {
    font-weight: 800;
}

.wppz-gp-proof-details dd {
    margin: 0;
}

.wppz-gp-proof-details button {
    border: 0;
    background: transparent;
    color: #337ab7;
    box-shadow: none;
    font-size: 14px;
}

.wppz-gp-designer-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 22px;
    padding: 24px;
}

.wppz-gp-design-canvas {
    display: flex;
    min-height: 360px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid #d7dce0;
    border-radius: 6px;
    background: #fff;
    color: #111827;
    text-align: center;
    padding: 24px;
}

.wppz-gp-design-canvas span {
    max-width: 100%;
    overflow-wrap: anywhere;
    font-size: 34px;
    font-weight: 800;
    line-height: 1.08;
}

.wppz-gp-design-canvas small {
    display: block;
    margin-top: 14px;
    color: currentColor;
    font-size: 18px;
    opacity: .82;
}

.wppz-gp-design-controls {
    display: grid;
    gap: 10px;
    align-content: start;
}

.wppz-gp-design-controls label {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
}

.wppz-gp-design-controls input,
.wppz-gp-design-controls select {
    min-height: 42px;
    border: 2px solid var(--wppz-gp-green);
    border-radius: 5px;
    box-shadow: none;
}

.wppz-gp-info-body {
    padding: 24px;
}

.wppz-gp-info-body h4 {
    margin: 0 0 8px;
    font-size: 18px;
}

.wppz-gp-info-body p {
    margin: 0 0 18px;
    color: var(--wppz-gp-muted);
}

@media (max-width: 680px) {
    .wppz-gp-actions {
        grid-template-columns: 1fr;
    }

    .wppz-gp-production-options,
    .wppz-gp-my-files-grid,
    .wppz-gp-proof-editor,
    .wppz-gp-view-proof-grid {
        grid-template-columns: 1fr;
    }

    .wppz-gp-proof-editor aside {
        min-height: 42px;
        writing-mode: horizontal-tb;
        transform: none;
    }

    .wppz-gp-radio-options {
        display: grid;
        gap: 12px;
    }

    .wppz-gp-designer-grid {
        grid-template-columns: 1fr;
    }

    .wppz-gp-modal {
        padding: 12px;
    }
}
CSS;
}

function wppz_aframe_configurator_js() {
    return <<<'JS'
(function($) {
    function formatMoney(value, symbol) {
        var amount = Number(value || 0);
        return symbol + amount.toFixed(2);
    }

    function closeModal($modal) {
        $modal.attr('aria-hidden', 'true');
        $('body').removeClass('wppz-gp-modal-open');
    }

    function openModal($modal) {
        $modal.attr('aria-hidden', 'false');
        $('body').addClass('wppz-gp-modal-open');
    }

    function layoutProofMode($root) {
        if (!$root.length || $root.attr('data-step') !== 'view-proof') {
            return;
        }

        var width = Math.min(1180, Math.max(320, window.innerWidth - 48));
        var desiredLeft = window.innerWidth <= 680 ? 16 : 24;
        var previousOffset = parseFloat($root.css('--wppz-proof-offset')) || 0;
        var currentLeft = $root[0].getBoundingClientRect().left - previousOffset;

        $root.css({
            '--wppz-proof-width': width + 'px',
            '--wppz-proof-offset': (desiredLeft - currentLeft) + 'px'
        });
    }

    function hideSiblingBranches($container, $keep) {
        if (!$container.length || !$keep.length || $container.is('body, html')) {
            return;
        }

        var $branch = $keep;

        if (!$keep.parent().is($container)) {
            var $parents = $keep.parentsUntil($container);

            if (!$parents.length) {
                return;
            }

            $branch = $parents.last();
        }

        $container.children().not($branch).addClass('wppz-gp-proof-hidden');
    }

    function setProofPageMode($root, enabled) {
        var $form = $root.closest('form.cart');
        var $summary = $form.closest('.summary, .entry-summary');
        var $product = $form.closest('div.product, article.product, .product').first();
        var $content = $product.closest('main, #main, .site-main, #primary, .content-area, article').first();

        $('body').toggleClass('wppz-gp-proof-mode', !!enabled);

        if (enabled) {
            hideSiblingBranches($content, $product);
            hideSiblingBranches($product, $form);
            hideSiblingBranches($summary, $form);
            $form.children().not($root).not('.single_add_to_cart_button').not('.quantity').addClass('wppz-gp-proof-hidden');
            return;
        }

        $('.wppz-gp-proof-hidden').removeClass('wppz-gp-proof-hidden');
        $root.css({
            '--wppz-proof-width': '',
            '--wppz-proof-offset': ''
        });
    }

    function getConfiguratorSettings($root) {
        var cached = $root.data('wppzConfigSettings');

        if (cached) {
            return cached;
        }

        var settings = {};
        var raw = $.trim($root.find('[data-wppz-config-settings]').first().text() || '');

        if (raw) {
            try {
                settings = JSON.parse(raw);
            } catch (e) {
                settings = {};
            }
        }

        settings.fields = settings.fields || {};
        settings.fieldOrder = settings.fieldOrder || Object.keys(settings.fields);
        settings.sizeOptionsByShape = settings.sizeOptionsByShape || sizeOptionsByShape;
        settings.holeOptionsByShape = settings.holeOptionsByShape || holeOptionsByShape;
        settings.designerSubline = settings.designerSubline || 'Acrylic Board';
        $root.data('wppzConfigSettings', settings);

        return settings;
    }

    function updateDesignPreview($root) {
        var headline = $root.find('[data-wppz-design-headline-input]').val() || 'YOUR MESSAGE';
        var subline = $root.find('[data-wppz-design-subline-input]').val() || getConfiguratorSettings($root).designerSubline;
        var color = $root.find('[data-wppz-design-color]').val() || '#ffffff';
        var dark = color === '#111827' || color === '#319244' || color === '#ff6a3d';

        $root.find('[data-wppz-design-headline]').text(headline);
        $root.find('[data-wppz-design-subline]').text(subline);
        $root.find('[data-wppz-design-preview]').css({
            backgroundColor: color,
            color: dark ? '#ffffff' : '#111827'
        });
    }

    function setArtworkStatus($root, status, method) {
        $root.find('[data-wppz-artwork-status]').text(status || 'No artwork selected');
        $root.find('[data-wppz-artwork-method]').val(method || 'No artwork selected');
    }

    function setUploadTab($root, tab) {
        $root.find('[data-wppz-upload-tab]').removeClass('is-active');
        $root.find('[data-wppz-upload-tab="' + tab + '"]').addClass('is-active');
        $root.find('[data-wppz-upload-pane]').attr('hidden', true);
        $root.find('[data-wppz-upload-pane="' + tab + '"]').removeAttr('hidden');
    }

    function configFieldValue($root, key, field) {
        var selector = '[name="wppz_aframe_config[' + key + ']"]';

        if (field && (field.type === 'radio' || field.type === 'orientation')) {
            return $root.find(selector + ':checked').val();
        }

        return $root.find(selector).val();
    }

    function proofDetails($root) {
        var settings = getConfiguratorSettings($root);
        var details = [];

        settings.fieldOrder.forEach(function(key) {
            var field = settings.fields[key] || {};
            var value = configFieldValue($root, key, field);

            if (field.label && value) {
                details.push([field.label, value]);
            }
        });

        details.push(['Production Time', $root.find('[data-wppz-production-time]').val()]);

        return details.filter(function(item) {
            return item[1];
        });
    }

    function renderProofDetails($root) {
        var html = proofDetails($root).map(function(item) {
            return '<dt>' + item[0] + ':</dt><dd>' + $('<div>').text(item[1]).html() + '</dd>';
        }).join('');
        $root.find('[data-wppz-proof-details]').html(html);
    }

    function canPreviewAsImage(url) {
        if (!url) {
            return false;
        }

        return /^data:image\//i.test(url) || /\.(png|jpe?g|gif|bmp|webp|tiff?)(\?.*)?$/i.test(url);
    }

    function setProofPreview($root, label, url) {
        var safeLabel = label || 'Artwork Preview';
        var escapedLabel = $('<div>').text(safeLabel).html();
        var escapedUrl = url ? $('<div>').text(url).html() : '';
        var imageHtml = escapedUrl && canPreviewAsImage(escapedUrl) ? '<img src="' + escapedUrl + '" alt="">' : '<span>' + escapedLabel + '</span>';
        var handles = '<i class="wppz-gp-proof-handle is-nw" aria-hidden="true"></i><i class="wppz-gp-proof-handle is-ne" aria-hidden="true"></i><i class="wppz-gp-proof-handle is-sw" aria-hidden="true"></i><i class="wppz-gp-proof-handle is-se" aria-hidden="true"></i>';

        $root.find('[data-wppz-proof-preview]').html(imageHtml + handles);
        $root.find('[data-wppz-proof-final-preview]').html(imageHtml);
    }

    function openProofEditor($root, label, url) {
        renderProofDetails($root);
        setProofPreview($root, label, url);
        $root.find('[data-wppz-proof-status]').val('');
        $root.find('[data-wppz-approve-checkbox]').prop('checked', false);
        closeModal($root.find('[data-wppz-upload-modal]'));
        openModal($root.find('[data-wppz-proof-modal]'));
    }

    function previewUploadedArtwork($root, file) {
        if (!file) {
            return;
        }

        if (file.type && file.type.indexOf('image/') === 0 && window.FileReader) {
            var reader = new FileReader();
            reader.onload = function(event) {
                openProofEditor($root, file.name, event.target.result);
            };
            reader.readAsDataURL(file);
            return;
        }

        openProofEditor($root, file.name, '');
    }

    function showProofStep($root) {
        var $form = $root.closest('form.cart');

        renderProofDetails($root);
        $root.attr('data-step', 'view-proof');
        setProofPageMode($root, true);
        $root.find('[data-wppz-view-proof]').removeAttr('hidden');
        $root.find('.wppz-gp-fields, [data-wppz-after-quantity], .wppz-gp-total, .wppz-gp-actions, .wppz-gp-artwork-status, .wppz-gp-honest').hide();
        $root.find('[data-wppz-proof-status]').val('Approved for Print');
        $root.find('[data-wppz-approve-checkbox]').prop('checked', true);
        $form.find('.single_add_to_cart_button').show();
        layoutProofMode($root);

        if (window.history && window.history.pushState) {
            var url = new URL(window.location.href);
            url.searchParams.set('step', 'viewProof');
            window.history.pushState({}, '', url.toString());
        }

        setTimeout(function() {
            layoutProofMode($root);
            $('html, body').animate({ scrollTop: Math.max(0, $root.find('[data-wppz-view-proof]').offset().top - 16) }, 250);
        }, 20);
    }

    var sizeOptionsByShape = {
        'Rectangle': ['6" x 18"', '6" x 20"', '6" x 24"', '6" x 30"', '8" x 10"', '8" x 12"', '8.5" x 11"', '9" x 24"', '10" x 12"', '10" x 14"', '10" x 18"', '10" x 20"', '11" x 14"', '11" x 19"', '12" x 16"', '12" x 18"', '12" x 24"', '16" x 20"', '16" x 23"', '18" x 20"', '18" x 24"', '18" x 30"', '18" x 36"', '20" x 30"', '20" x 40"', '22" x 23"', '22" x 28"', '24" x 30"', '24" x 36"'],
        'Rounded Rectangle': ['6" x 18"', '6" x 20"', '6" x 24"', '6" x 30"', '8" x 10"', '8" x 12"', '8.5" x 11"', '9" x 24"', '10" x 12"', '10" x 14"', '10" x 18"', '10" x 20"', '11" x 14"', '11" x 19"', '12" x 16"', '12" x 18"', '12" x 24"', '16" x 20"', '16" x 23"', '18" x 20"', '18" x 24"', '18" x 30"', '18" x 36"', '20" x 30"', '20" x 40"', '22" x 23"', '22" x 28"', '24" x 30"', '24" x 36"'],
        'Square': ['8" x 8"', '10" x 10"', '12" x 12"', '14" x 14"', '16" x 16"', '18" x 18"', '20" x 20"', '23" x 23"', '24" x 24"'],
        'Rounded Square': ['8" x 8"', '10" x 10"', '12" x 12"', '14" x 14"', '16" x 16"', '18" x 18"', '20" x 20"', '23" x 23"', '24" x 24"'],
        'Circle': ['12" x 12"', '16" x 16"', '18" x 18"', '23" x 23"', '24" x 24"'],
        'Half Circle': ['8" x 10"', '9" x 18"', '11" x 14"', '18" x 24"', '20" x 30"', '20" x 36"', '22" x 28"', '24" x 36"', '12" x 24"', '14" x 28"', '15" x 30"', '18" x 36"'],
        'Half Arch': ['8" x 10"', '9" x 18"', '11" x 14"', '18" x 24"', '20" x 30"', '20" x 36"', '22" x 28"', '24" x 36"'],
        'House': ['23" x 23"'],
        'Apartment': ['23" x 23"'],
        'Star': ['22" x 23"'],
        'Octagon': ['23" x 23"'],
        'Arrow': ['18" x 24"'],
        'Oval': ['16" x 23"']
    };
    var holeOptionsByShape = {
        'Rectangle': ['None', '4 Corners', 'Top 2 Corners', 'Top & Bottom Center', '4 Corners & Top & Bottom Center'],
        'Square': ['None', '4 Corners', 'Top 2 Corners', 'Top & Bottom Center', '4 Corners & Top & Bottom Center']
    };

    function renderSelectOptions($select, options, selected) {
        $select.empty().append($('<option>', {
            value: '',
            text: 'Please select an option',
            hidden: true
        }));

        options.forEach(function(option) {
            $select.append($('<option>', {
                value: option,
                text: option,
                selected: option === selected
            }));
        });
    }

    function syncShapeDependentFields($root) {
        var settings = getConfiguratorSettings($root);
        var shape = $root.find('[name="wppz_aframe_config[shape]"]').val() || 'Rectangle';
        var sizes = settings.sizeOptionsByShape[shape] || settings.sizeOptionsByShape.Rectangle || [];
        var $size = $root.find('[name="wppz_aframe_config[size]"]');
        if (!$size.length || !sizes.length) {
            return;
        }
        var selectedSize = sizes.indexOf($size.val()) !== -1 ? $size.val() : sizes[0];
        var holes = settings.holeOptionsByShape[shape] || [];
        var $holeField = $root.find('[data-wppz-field="hole_drilling"]');
        var $hole = $root.find('[name="wppz_aframe_config[hole_drilling]"]');
        var selectedHole = holes.indexOf($hole.val()) !== -1 ? $hole.val() : 'None';

        renderSelectOptions($size, sizes, selectedSize);
        $size.val(selectedSize);

        if (holes.length) {
            renderSelectOptions($hole, holes, selectedHole);
            $hole.val(selectedHole);
            $holeField.show();
        } else if ($hole.length) {
            renderSelectOptions($hole, ['None'], 'None');
            $hole.val('None');
            $holeField.hide();
        }
    }

    function selectedQuantity($root) {
        return parseInt($root.find('[name="wppz_aframe_config[quantity]"]').val(), 10) || 0;
    }

    function updateRequiredFields($root) {
        $root.find('[data-wppz-required="true"]').each(function() {
            var $field = $(this);
            var value = $field.find('select').val() || $field.find('input:checked').val() || '';
            $field.toggleClass('is-empty', !value);
            $field.toggleClass('is-complete', !!value);
        });
    }

    function ensureCartQuantityInput($root) {
        var $form = $root.closest('form.cart');
        var $input = $form.find('input.qty[name="quantity"], input[name="quantity"]').first();

        if (!$input.length) {
            $input = $('<input>', {
                type: 'hidden',
                name: 'quantity',
                value: '1',
                'data-wppz-cart-quantity': 'true'
            }).appendTo($form);
        }

        return $input;
    }

    function updateSubtotal($root) {
        var basePrice = parseFloat($root.attr('data-base-price')) || 0;
        var qty = selectedQuantity($root);
        var symbol = $root.attr('data-currency-symbol') || '$';
        $root.find('[data-wppz-subtotal]').text(formatMoney(basePrice * qty, symbol));
    }

    function syncCartQuantity($root) {
        var qty = selectedQuantity($root);

        if (qty > 0) {
            ensureCartQuantityInput($root).val(qty).trigger('change');
        }

        var $afterQuantity = $root.find('[data-wppz-after-quantity]');
        $afterQuantity.prop('hidden', qty <= 0);

        if ($root.attr('data-step') !== 'view-proof') {
            $afterQuantity.toggle(qty > 0);
        }

        updateRequiredFields($root);
        updateSubtotal($root);
    }

    function setActiveUploadSide($root, side) {
        side = side === 'back' ? 'front' : side;
        $root.data('wppzActiveUploadSide', side);
        var sideLabel = side === 'back' ? 'Back' : 'Front';
        var input = $root.find('[data-wppz-file-input="' + side + '"]')[0];
        var fileName = input && input.files ? input.files[0] : null;
        $root.find('[data-wppz-upload-copy]').text('Drag ' + sideLabel.toLowerCase() + ' file to upload or');
        $root.find('[data-wppz-selected-file]').text(fileName ? fileName.name : '');
    }

    function applyFileToInput(input, files) {
        if (!files || !files.length) {
            return;
        }

        try {
            var transfer = new DataTransfer();
            transfer.items.add(files[0]);
            input.files = transfer.files;
        } catch (e) {
            return;
        }
    }

    $(function() {
        $('.wppz-gp-configurator').each(function() {
            var $root = $(this);
            var $form = $root.closest('form.cart');

            if ($form.length) {
                $form.attr('enctype', 'multipart/form-data');
                $form.find('.wapf-wrapper, .wapf-field-container, .wapf-product-totals').hide();
                $form.find('.quantity').hide();
                $form.find('.single_add_to_cart_button').hide();
            }

            syncShapeDependentFields($root);
            syncCartQuantity($root);
            updateDesignPreview($root);

            $root.on('change', 'select, input[type="radio"]', function() {
                syncShapeDependentFields($root);
                syncCartQuantity($root);
            });

            $form.on('change input', 'input.qty', function() {
                updateSubtotal($root);
            });

            $root.on('click', '[data-wppz-help]', function() {
                var $button = $(this);
                $('.wppz-gp-help-bubble').remove();

                var $bubble = $('<div class="wppz-gp-help-bubble" role="status"></div>').text($button.data('wppz-help'));
                $button.after($bubble);
                setTimeout(function() {
                    $bubble.fadeOut(160, function() {
                        $(this).remove();
                    });
                }, 4800);
            });

            $root.on('click', '[data-wppz-open-upload]', function() {
                var side = $(this).data('wppz-open-upload') || 'front';
                setUploadTab($root, 'upload');
                setActiveUploadSide($root, side);
                openModal($root.find('[data-wppz-upload-modal]'));
            });

            $root.on('click', '[data-wppz-upload-tab]', function() {
                setUploadTab($root, $(this).data('wppz-upload-tab'));
            });

            $root.on('click', '[data-wppz-filter-folder]', function() {
                var folder = String($(this).data('wppz-filter-folder'));
                $root.find('[data-wppz-filter-folder]').removeClass('is-active');
                $(this).addClass('is-active');
                $root.find('[data-wppz-my-file], [data-wppz-saved-design]').each(function() {
                    var itemFolder = String($(this).data('folder-id'));
                    $(this).toggle(folder === 'all' || folder === itemFolder);
                });
            });

            $root.on('click', '[data-wppz-my-file]', function() {
                var $file = $(this);
                var fileName = $file.data('file-name') || 'Selected file';
                var fileUrl = $file.data('file-url') || '';

                $root.find('[data-wppz-my-file]').removeClass('is-selected');
                $file.addClass('is-selected');
                $root.find('[data-wppz-existing-file-id]').val($file.data('file-id') || '');
                $root.find('[data-wppz-existing-file-url]').val(fileUrl);
                $root.find('[data-wppz-selected-file-name-input]').val(fileName);
                setArtworkStatus($root, 'Front artwork selected: ' + fileName, 'Upload Front');
                openProofEditor($root, fileName, fileUrl);
            });

            $root.on('click', '[data-wppz-saved-design]', function() {
                var $design = $(this);
                var fileName = $design.data('file-name') || 'Saved design';

                $root.find('[data-wppz-design-summary]').val('Saved design: ' + fileName);
                $root.find('[data-wppz-design-data]').val(JSON.stringify({
                    savedDesignId: $design.data('design-id') || '',
                    name: fileName
                }));
                $root.find('[data-wppz-selected-file-name-input]').val(fileName);
                setArtworkStatus($root, 'Online design selected: ' + fileName, 'Design Online');
                openProofEditor($root, fileName, '');
            });

            $root.on('click', '[data-wppz-production-value]', function() {
                $root.find('[data-wppz-production-value]').removeClass('is-active');
                $(this).addClass('is-active');
                $root.find('[data-wppz-production-time]').val($(this).data('wppz-production-value'));
                renderProofDetails($root);
            });

            $root.on('change', 'input[name="wppz_process_type_choice"]', function() {
                $root.find('[data-wppz-process-type]').val($(this).val());
            });

            $root.on('click', '[data-wppz-accordion-toggle]', function() {
                var $accordion = $(this).closest('[data-wppz-accordion]');
                $accordion.toggleClass('is-open');
                $(this).find('i').text($accordion.hasClass('is-open') ? '-' : '+');
            });

            $root.on('click', '[data-wppz-open-designer]', function() {
                openModal($root.find('[data-wppz-designer-modal]'));
            });

            $root.on('click', '[data-wppz-open-honest]', function() {
                openModal($root.find('[data-wppz-honest-modal]'));
            });

            $root.on('click', '[data-wppz-close-modal]', function() {
                closeModal($(this).closest('.wppz-gp-modal'));
            });

            $root.on('click', '.wppz-gp-modal', function(event) {
                if (event.target === this) {
                    closeModal($(this));
                }
            });

            $root.on('click', '[data-wppz-browse]', function() {
                var side = $root.data('wppzActiveUploadSide') || 'front';
                $root.find('[data-wppz-file-input="' + side + '"]').trigger('click');
            });

            $root.on('change', '[data-wppz-file-input]', function() {
                var side = $(this).data('wppz-file-input') || 'front';
                setActiveUploadSide($root, side);

                if (this.files && this.files[0]) {
                    var file = this.files[0];

                    $root.find('[data-wppz-existing-file-id], [data-wppz-existing-file-url]').val('');
                    $root.find('[data-wppz-selected-file-name-input]').val(file.name);
                    setArtworkStatus($root, 'Front artwork selected: ' + file.name, 'Upload Front');
                    previewUploadedArtwork($root, file);
                }
            });

            $root.on('dragover', '[data-wppz-dropzone]', function(event) {
                event.preventDefault();
                $(this).addClass('is-dragover');
            });

            $root.on('dragleave', '[data-wppz-dropzone]', function(event) {
                event.preventDefault();
                $(this).removeClass('is-dragover');
            });

            $root.on('drop', '[data-wppz-dropzone]', function(event) {
                event.preventDefault();
                $(this).removeClass('is-dragover');

                var side = $root.data('wppzActiveUploadSide') || 'front';
                var input = $root.find('[data-wppz-file-input="' + side + '"]')[0];
                var files = event.originalEvent.dataTransfer.files;
                applyFileToInput(input, files);
                $(input).trigger('change');
            });

            $root.on('click', '[data-wppz-confirm-upload]', function() {
                var frontFile = $root.find('[data-wppz-file-input="front"]')[0].files[0];

                if (frontFile) {
                    setArtworkStatus($root, 'Front artwork selected: ' + frontFile.name, 'Upload Front');
                }

                closeModal($root.find('[data-wppz-upload-modal]'));
            });

            $root.on('click', '[data-wppz-proof-tool]', function() {
                var tool = $(this).data('wppz-proof-tool');
                var $art = $root.find('[data-wppz-proof-preview]');
                $art.removeClass('is-left is-right is-fit is-original');
                if (tool === 'left') {
                    $art.addClass('is-left');
                } else if (tool === 'right') {
                    $art.addClass('is-right');
                } else if (tool === 'fit') {
                    $art.addClass('is-fit');
                } else if (tool === 'orig') {
                    $art.addClass('is-original');
                }
            });

            $root.on('click', '[data-wppz-view-proof-action]', function() {
                closeModal($root.find('[data-wppz-proof-modal]'));
                showProofStep($root);
            });

            $root.on('click', '[data-wppz-edit-proof]', function() {
                $root.attr('data-step', 'configure');
                setProofPageMode($root, false);
                $root.find('[data-wppz-view-proof]').attr('hidden', true);
                $root.find('.wppz-gp-fields, .wppz-gp-total, .wppz-gp-actions, .wppz-gp-artwork-status, .wppz-gp-honest').show();
                syncCartQuantity($root);
                $root.closest('form.cart').find('.single_add_to_cart_button').hide();
            });

            $root.on('change', '[data-wppz-approve-checkbox]', function() {
                $root.find('[data-wppz-proof-status]').val(this.checked ? 'Approved for Print' : '');
                $form.find('.single_add_to_cart_button').toggle(this.checked);
            });

            $root.on('input change', '[data-wppz-design-template], [data-wppz-design-headline-input], [data-wppz-design-subline-input], [data-wppz-design-color]', function() {
                updateDesignPreview($root);
            });

            $root.on('click', '[data-wppz-save-design]', function() {
                var data = {
                    template: $root.find('[data-wppz-design-template]').val(),
                    headline: $root.find('[data-wppz-design-headline-input]').val(),
                    subline: $root.find('[data-wppz-design-subline-input]').val(),
                    background: $root.find('[data-wppz-design-color]').val()
                };
                var summary = data.template + ': ' + data.headline + ' / ' + data.subline;

                $root.find('[data-wppz-design-summary]').val(summary);
                $root.find('[data-wppz-design-data]').val(JSON.stringify(data));
                setArtworkStatus($root, 'Online design selected: ' + data.template, 'Design Online');
                closeModal($root.find('[data-wppz-designer-modal]'));
                openProofEditor($root, data.template, '');
            });

            $(window).on('resize.wppzGpProof', function() {
                layoutProofMode($root);
            });
        });
    });
})(jQuery);
JS;
}

function wppz_aframe_validate_uploaded_file($file_key, $label) {
    if (empty($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
        return true;
    }

    if (!empty($_FILES[$file_key]['error']) && UPLOAD_ERR_OK !== (int) $_FILES[$file_key]['error']) {
        wc_add_notice(sprintf('%s could not be uploaded. Please choose the file again.', $label), 'error');
        return false;
    }

    $allowed_extensions = array('tif', 'tiff', 'eps', 'ai', 'psd', 'bmp', 'gif', 'jpg', 'jpeg', 'png', 'pdf');
    $extension = strtolower(pathinfo(sanitize_file_name(wp_unslash($_FILES[$file_key]['name'])), PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions, true)) {
        wc_add_notice(sprintf('%s must be one of these formats: TIF, TIFF, EPS, AI, PSD, BMP, GIF, JPG, PNG, or PDF.', $label), 'error');
        return false;
    }

    if (!empty($_FILES[$file_key]['size']) && (int) $_FILES[$file_key]['size'] > 75 * 1024 * 1024) {
        wc_add_notice(sprintf('%s must be 75MB or smaller.', $label), 'error');
        return false;
    }

    return true;
}

function wppz_aframe_add_to_cart_validation($passed, $product_id) {
    if (!$passed || !wppz_is_aframe_product_context($product_id)) {
        return $passed;
    }

    if (empty($_POST['wppz_aframe_config_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wppz_aframe_config_nonce'])), 'wppz_aframe_config')) {
        wc_add_notice(__('Please refresh the product page and try again.', 'wppluginzone-user-dashboard'), 'error');
        return false;
    }

    $raw_config = isset($_POST['wppz_aframe_config']) ? wp_unslash($_POST['wppz_aframe_config']) : array();
    $fields = wppz_aframe_config_options($product_id);

    foreach ($fields as $key => $field) {
        if (empty($field['required'])) {
            continue;
        }

        $value = isset($raw_config[$key]) ? sanitize_text_field(wp_unslash($raw_config[$key])) : '';

        if ('' === $value || !in_array($value, $field['options'], true)) {
            wc_add_notice(sprintf(__('Please select %s.', 'wppluginzone-user-dashboard'), strtolower($field['label'])), 'error');
            return false;
        }
    }

    $artwork_method = isset($raw_config['artwork_method']) ? sanitize_text_field(wp_unslash($raw_config['artwork_method'])) : '';
    $proof_status = isset($raw_config['proof_status']) ? sanitize_text_field(wp_unslash($raw_config['proof_status'])) : '';

    if ('No artwork selected' === $artwork_method || '' === $artwork_method) {
        wc_add_notice(__('Please upload artwork, choose a file, or design online before adding to cart.', 'wppluginzone-user-dashboard'), 'error');
        return false;
    }

    if ('Approved for Print' !== $proof_status) {
        wc_add_notice(__('Please view and approve your proof before adding to cart.', 'wppluginzone-user-dashboard'), 'error');
        return false;
    }

    $front_valid = wppz_aframe_validate_uploaded_file('wppz_aframe_front_file', __('Front artwork', 'wppluginzone-user-dashboard'));

    return $front_valid;
}
add_filter('woocommerce_add_to_cart_validation', 'wppz_aframe_add_to_cart_validation', 20, 2);

function wppz_aframe_upload_mimes() {
    return array(
        'jpg|jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'tif|tiff' => 'image/tiff',
        'pdf' => 'application/pdf',
        'eps|ai' => 'application/postscript',
        'psd' => 'image/vnd.adobe.photoshop',
    );
}

function wppz_aframe_handle_uploaded_file($file_key, $side_label) {
    if (empty($_FILES[$file_key]) || empty($_FILES[$file_key]['name'])) {
        return null;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';

    $uploaded = wp_handle_upload(
        $_FILES[$file_key],
        array(
            'test_form' => false,
            'mimes' => wppz_aframe_upload_mimes(),
        )
    );

    if (!empty($uploaded['error'])) {
        wc_add_notice(sprintf('%s upload failed: %s', $side_label, $uploaded['error']), 'error');
        return null;
    }

    return array(
        'side' => $side_label,
        'name' => sanitize_file_name(wp_unslash($_FILES[$file_key]['name'])),
        'url' => esc_url_raw($uploaded['url']),
        'type' => sanitize_text_field($uploaded['type']),
        'size' => isset($_FILES[$file_key]['size']) ? absint($_FILES[$file_key]['size']) : 0,
    );
}

function wppz_aframe_add_cart_item_data($cart_item_data, $product_id) {
    if (!wppz_is_aframe_product_context($product_id)) {
        return $cart_item_data;
    }

    $raw_config = isset($_POST['wppz_aframe_config']) ? wp_unslash($_POST['wppz_aframe_config']) : array();
    $cart_item_data['wppz_aframe_config'] = wppz_aframe_sanitize_config($raw_config, $product_id);
    $cart_item_data['wppz_aframe_config_labels'] = wppz_aframe_config_labels($product_id);

    if (!empty($_POST['wppz_aframe_design_data'])) {
        $cart_item_data['wppz_aframe_design_data'] = sanitize_textarea_field(wp_unslash($_POST['wppz_aframe_design_data']));
    }

    $uploaded_files = array();
    $front_file = wppz_aframe_handle_uploaded_file('wppz_aframe_front_file', 'Front Artwork');
    $existing_file_id = !empty($_POST['wppz_aframe_existing_file_id']) ? absint($_POST['wppz_aframe_existing_file_id']) : 0;

    if ($front_file) {
        $uploaded_files[] = $front_file;
    }

    if (!$front_file && $existing_file_id) {
        $existing_file = wppz_aframe_get_user_file($existing_file_id);

        if ($existing_file) {
            $uploaded_files[] = array(
                'side' => 'Front Artwork',
                'name' => sanitize_text_field($existing_file->original_name),
                'url' => esc_url_raw($existing_file->file_url),
                'type' => sanitize_text_field($existing_file->mime_type),
                'size' => absint($existing_file->file_size),
                'source' => 'My Files',
            );
        }
    }

    if ($uploaded_files) {
        $cart_item_data['wppz_aframe_files'] = $uploaded_files;
    }

    $cart_item_data['wppz_aframe_unique_key'] = md5(wp_json_encode($cart_item_data['wppz_aframe_config']) . microtime());

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'wppz_aframe_add_cart_item_data', 20, 2);

function wppz_aframe_restore_cart_item_from_session($cart_item, $values) {
    foreach (array('wppz_aframe_config', 'wppz_aframe_config_labels', 'wppz_aframe_files', 'wppz_aframe_design_data', 'wppz_aframe_unique_key') as $key) {
        if (isset($values[$key])) {
            $cart_item[$key] = $values[$key];
        }
    }

    return $cart_item;
}
add_filter('woocommerce_get_cart_item_from_session', 'wppz_aframe_restore_cart_item_from_session', 20, 2);

function wppz_aframe_get_item_data($item_data, $cart_item) {
    if (empty($cart_item['wppz_aframe_config'])) {
        return $item_data;
    }

    $labels = !empty($cart_item['wppz_aframe_config_labels']) && is_array($cart_item['wppz_aframe_config_labels']) ? $cart_item['wppz_aframe_config_labels'] : wppz_aframe_config_labels(!empty($cart_item['product_id']) ? $cart_item['product_id'] : 0);

    foreach ($cart_item['wppz_aframe_config'] as $key => $value) {
        if ('' === $value || !isset($labels[$key])) {
            continue;
        }

        $item_data[] = array(
            'key' => $labels[$key],
            'value' => wc_clean($value),
            'display' => esc_html($value),
        );
    }

    if (!empty($cart_item['wppz_aframe_files'])) {
        foreach ($cart_item['wppz_aframe_files'] as $file) {
            $name = !empty($file['name']) ? $file['name'] : __('Uploaded file', 'wppluginzone-user-dashboard');
            $item_data[] = array(
                'key' => !empty($file['side']) ? $file['side'] : __('Artwork', 'wppluginzone-user-dashboard'),
                'value' => wc_clean($name),
                'display' => esc_html($name),
            );
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'wppz_aframe_get_item_data', 20, 2);

function wppz_aframe_add_order_item_meta($item, $cart_item_key, $values) {
    if (empty($values['wppz_aframe_config'])) {
        return;
    }

    $labels = !empty($values['wppz_aframe_config_labels']) && is_array($values['wppz_aframe_config_labels']) ? $values['wppz_aframe_config_labels'] : wppz_aframe_config_labels(!empty($values['product_id']) ? $values['product_id'] : 0);

    foreach ($values['wppz_aframe_config'] as $key => $value) {
        if ('' === $value || !isset($labels[$key])) {
            continue;
        }

        $item->add_meta_data($labels[$key], $value, true);
    }

    if (!empty($values['wppz_aframe_design_data'])) {
        $item->add_meta_data('Online Design Data', $values['wppz_aframe_design_data'], true);
    }

    if (!empty($values['wppz_aframe_files'])) {
        foreach ($values['wppz_aframe_files'] as $file) {
            if (empty($file['url'])) {
                continue;
            }

            $label = !empty($file['side']) ? $file['side'] . ' URL' : 'Artwork URL';
            $item->add_meta_data($label, esc_url_raw($file['url']), true);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'wppz_aframe_add_order_item_meta', 20, 3);
?>
