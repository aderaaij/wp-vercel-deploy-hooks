<?php

/**
 *  @package Vercel Deploy Hooks
 */
/*
Plugin Name: Vercel Deploy Hooks
Plugin URI: https://github.com/aderaaij/wp-vercel-deploy-hooks
Description: Allows for building your static site on command, post publish/update or scheduled
Version: 1.0
Author: Arden de Raaij
Author URI: https://arden.nl
License: GPLv3 or later
Text Domain: vercel-deploy-hooks
*/

/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) or die('You do not have access to this file, sorry mate');

class deployWebhook {

    /**
    * Constructor
    *
    * @since 1.0.0
    **/
    public function __construct() {

      // Stop crons on uninstall
      register_deactivation_hook(__FILE__,  array( $this, 'deactivate_scheduled_cron'));

    	// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

        // Add Settings and Fields
      add_action( 'admin_init', array( $this, 'setup_sections' ) );
      add_action( 'admin_init', array( $this, 'setup_schedule_fields' ) );
      add_action( 'admin_init', array( $this, 'setup_developer_fields' ) );
      add_action( 'admin_footer', array( $this, 'run_the_mighty_javascript' ) );
      add_action( 'admin_bar_menu', array( $this, 'add_to_admin_bar' ), 90 );

      // Listen to cron scheduler option updates
      add_action('update_option_enable_scheduled_builds', array( $this, 'build_schedule_options_updated' ), 10, 3 );
      add_action('update_option_select_schedule_builds', array( $this, 'build_schedule_options_updated' ), 10, 3 );
      add_action('update_option_select_time_build', array( $this, 'build_schedule_options_updated' ), 10, 3 );

      // Trigger cron scheduler every WP load
      add_action('wp', array( $this, 'set_build_schedule_cron') );

      // Add custom schedules
      add_filter( 'cron_schedules', array( $this, 'custom_cron_intervals' ) );

      // Link event to function
      add_action('scheduled_vercel_build', array( $this, 'fire_vercel_webhook' ) );
      
      // add actions for deploying on publish
      add_action('publish_future_post', array( $this,' vb_webhook_future_post'), 10);
      add_action('publish_post', array( $this,'vb_webhook_post'), 10, 2);
      add_action('publish_page', array( $this, 'vb_webhook_post'), 10, 2);
      add_action('post_updated', array( $this,'vb_webhook_update'), 10, 3);

    }

    /**
    * Main Plugin Page markup
    *
    * @since 1.0.0
    **/
    public function plugin_settings_page_content() {?>
    	<div class="wrap">
    		<h2><?php _e('Vercel Deploy Hooks', 'vercel-deploy-hooks');?></h2>
        <hr>
        <h3><?php _e('Build Website', 'vercel-deploy-hooks');?></h3>
        <button id="build_button" class="button button-primary" name="submit" type="submit">
          <?php _e('Build Site', 'vercel-deploy-hooks');?>
        </button>
        <br>
        <p id="build_status" style="font-size: 12px; margin: 16px 0;">
            <ul>
                <li id="build_status_id" style="display:none"></li>
                <li id="build_status_state" style="display:none"></li>
                <li id="build_status_createdAt" style="display:none"></li>
            </ul>
        </p>
        <p style="font-size: 12px">*<?php _e('Do not abuse the Build Site button', 'vercel-deploy-hooks');?>*</p><br>
       <?php
    }

    /**
    * Schedule Builds (subpage) markup
    *
    * @since 1.1.2
    **/
    public function plugin_settings_schedule_content() {?>
    	<div class="wrap">
    		<h1><?php _e('Schedule vercel Builds', 'vercel-deploy-hooks');?></h1>
    		<p><?php _e('This section allows regular vercel builds to be scheduled.', 'vercel-deploy-hooks');?></p>
        <hr>

        <?php
        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
              $this->admin_notice();
        } ?>

        <form method="POST" action="options.php">
                <?php
                    settings_fields( 'schedule_webhook_fields' );
                    do_settings_sections( 'schedule_webhook_fields' );
                    submit_button();
                ?>
        </form>
    	</div> <?php
    }

    /**
    * Settings (subpage) markup
    *
    * @since 1.0.0
    **/
    public function plugin_settings_developer_content() {?>
    	<div class="wrap">
    		<h1><?php _e('Settings', 'vercel-deploy-hooks');?></h1>    	
            <hr>

            <?php
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                  $this->admin_notice();
            } ?>
    		<form method="POST" action="options.php">
                <?php
                    settings_fields( 'developer_webhook_fields' );
                    do_settings_sections( 'developer_webhook_fields' );
                    submit_button();
                ?>
    		</form>

            <footer>
                <h3><?php _e('Extra Info', 'vercel-deploy-hooks');?></h3>
                <p><a href="https://github.com/aderaaij/wp-vercel-deploy-hooks"><?php _e('Plugin repository on Github', 'vercel-deploy-hooks');?></a></p>
                <p><a href="https://vercel.com/docs/more/deploy-hooks"><?php _e('Vercel Deploy Hooks Documentation', 'vercel-deploy-hooks');?></a></p>
            </footer>

    	</div> <?php
    }

    /**
    * The Mighty JavaScript
    *
    * @since 1.0.0
    **/
    public function run_the_mighty_javascript() {
        // TODO: split up javascript to allow to be dynamically imported as needed
        // $screen = get_current_screen();
        // if ( $screen && $screen->parent_base != 'developer_webhook_fields' && $screen->parent_base != 'deploy_webhook_fields_sub' ) {
        //     return;
        // }
        ?>
        <script type="text/javascript" >
        console.log('run_the_mighty_javascript');
        jQuery(document).ready(function($) {
            var _this = this;
            $( ".deploy_page_developer_webhook_fields td > input" ).css( "width", "100%");

            var webhook_url = '<?php echo(get_option('webhook_address')) ?>';
            var vercel_site_id = '<?php echo(get_option('vercel_site_id')) ?>';


            function vercelDeploy() {
                return $.ajax({
                    type: "POST",
                    url: webhook_url,
                    dataType: "json",
                })
            }

            $("#status_button").on("click", function(e) {
                e.preventDefault();
                // getDeployData();
            });

            $("#previous_deploys").on("click", function(e) {
                e.preventDefault();
                getAllPreviousBuilds();
            });

            $("#build_button").on("click", function(e) {

                // hide deploy
                $('#build_img_link').attr('href', '');
                $('#build_img').attr('src', '');
                $('#deploy_id').html('');
                $('#deploy_finish_time').html('');
                $('#deploy_ssl_url').html('');
                $('#deploy_preview').html('');

                e.preventDefault();

                vercelDeploy().done(function(res) {
                    console.log("success")
                    // getDeployData();
                    $("#build_status" ).html('Bulding in progress');
                    $("#build_status_id" ).removeAttr('style');
                    $("#build_status_id").html('<b>ID</b>: ' + res.job.id);
                    $("#build_status_state" ).removeAttr('style');
                    $("#build_status_state").html('<b>State</b>: ' + res.job.state);
                    $("#build_status_createdAt" ).removeAttr('style');
                    var timestamp = res.job.createdAt;
                    var miliSeconds = 
                    $("#build_status_createdAt").html('<b>Created At</b>: ' + new Date(res.job.createdAt).toLocaleString());
                })
                .fail(function() {
                    console.error("error res => ", this)
                    $( "#build_status" ).html('There seems to be an error with the build', this);
                })
            });

            $(document).on('click', '#wp-admin-bar-vercel-deploy-button', function(e) {
                e.preventDefault();

                var $button = $(this),
                    $buttonContent = $button.find('.ab-item:first');

                if ($button.hasClass('deploying') || $button.hasClass('running')) {
                    return false;
                }

                $button.addClass('running').css('opacity', '0.5');

                vercelDeploy().done(function() {
                    var $badge = $('#admin-bar-vercel-deploy-status-badge');

                    $button.removeClass('running');
                    $button.addClass('deploying');

                    $buttonContent.find('.ab-label').text('Deploying…');

                    if ($badge.length) {
                        if (!$badge.data('original-src')) {
                            $badge.data('original-src', $badge.attr('src'));
                        }

                        $badge.attr('src', $badge.data('original-src') + '?updated=' + Date.now());
                    }
                })
                .fail(function() {
                    $button.removeClass('running').css('opacity', '1');
                    $buttonContent.find('.dashicons-hammer')
                        .removeClass('dashicons-hammer').addClass('dashicons-warning');

                    console.error("error res => ", this)
                })
            });
        });
        </script> <?php
    }

    /**
    * Plugin Menu Items Setup
    *
    * @since 1.0.0
    **/
    public function create_plugin_settings_page() {
        $run_deploys = apply_filters( 'vercel_deploy_capability', 'manage_options' );
        $adjust_settings = apply_filters( 'vercel_adjust_settings_capability', 'manage_options' );

        if ( current_user_can( $run_deploys ) ) {
            $page_title = __('Deploy to vercel', 'vercel-deploy-hooks');
            $menu_title = __('Deploy', 'vercel-deploy-hooks');
            $capability = $run_deploys;
            $slug = 'deploy_webhook_fields';
            $callback = array( $this, 'plugin_settings_page_content' );
            $icon = 'dashicons-admin-plugins';
            $position = 100;

            add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
        }

        if ( current_user_can( $adjust_settings ) ) {
            $sub_page_title = __('Schedule Builds', 'vercel-deploy-hooks');
            $sub_menu_title = __('Schedule Builds', 'vercel-deploy-hooks');
            $sub_capability = $adjust_settings;
            $sub_slug = 'schedule_webhook_fields';
            $sub_callback = array( $this, 'plugin_settings_schedule_content' );

            add_submenu_page( $slug, $sub_page_title, $sub_menu_title, $sub_capability, $sub_slug, $sub_callback );
        }

        if ( current_user_can( $adjust_settings ) ) {
            $sub_page_title = __('Settings', 'vercel-deploy-hooks');
            $sub_menu_title = __('Settings', 'vercel-deploy-hooks');
            $sub_capability = $adjust_settings;
            $sub_slug = 'developer_webhook_fields';
            $sub_callback = array( $this, 'plugin_settings_developer_content' );

            add_submenu_page( $slug, $sub_page_title, $sub_menu_title, $sub_capability, $sub_slug, $sub_callback );
        }


    }

    public function custom_cron_intervals($schedules) {
      // add a 'weekly' interval
      $schedules['weekly'] = array(
        'interval' => 604800,
        'display' => __('Once Weekly', 'vercel-deploy-hooks')
      );
      $schedules['monthly'] = array(
        'interval' => 2635200,
        'display' => __('Once a month', 'vercel-deploy-hooks')
      );

      return $schedules;
    }

    /**
    * Notify Admin on Successful Update
    *
    * @since 1.0.0
    **/
    public function admin_notice() { ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Your settings have been updated!', 'vercel-deploy-hooks');?></p>
        </div>
    <?php
    }

    /**
    * Setup Sections
    *
    * @since 1.0.0
    **/
    public function setup_sections() {
        add_settings_section( 'schedule_section', __('Scheduling Settings', 'vercel-deploy-hooks'), array( $this, 'section_callback' ), 'schedule_webhook_fields' );
        add_settings_section( 'developer_section', __('Webhook Settings', 'vercel-deploy-hooks'), array( $this, 'section_callback' ), 'developer_webhook_fields' );
    }

    /**
    * Check it wont break on build and deploy
    *
    * @since 1.0.0
    **/
    public function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
    		case 'developer_section':
    			echo __('A Vercel Deploy hook URL is required to run this plugin', 'vercel-deploy-hooks');
    			break;
    	}
    }

    /**
    * Fields used for schedule input data
    *
    * Based off this article:
    * @link https://www.smashingmagazine.com/2016/04/three-approaches-to-adding-configurable-fields-to-your-plugin/
    *
    * @since 1.1.0
    **/
    public function setup_schedule_fields() {
        $fields = array(
          array(
            'uid' => 'enable_scheduled_builds',
            'label' => __('Enable Scheduled Events', 'vercel-deploy-hooks'),
            'section' => 'schedule_section',
            'type' => 'checkbox',
            'options' => array(
              'enable' => __('Enable', 'vercel-deploy-hooks'),
              ),
            'default' =>  array()
          ),
          array(
            'uid' => 'select_time_build',
            'label' => __('Select Time to Build', 'vercel-deploy-hooks'),
            'section' => 'schedule_section',
            'type' => 'time',
            'default' => '00:00'
          ),
          array(
            'uid' => 'select_schedule_builds',
            'label' => __('Select Build Schedule', 'vercel-deploy-hooks'),
            'section' => 'schedule_section',
            'type' => 'select',
            'options' => array(
              'daily' => __('Daily', 'vercel-deploy-hooks'),
              'weekly' => __('Weekly', 'vercel-deploy-hooks'),
              'monthly' => __('Monthly', 'vercel-deploy-hooks'),
            ),
            'default' => array('week')
          )
        );
    	foreach( $fields as $field ){
        	add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'schedule_webhook_fields', $field['section'], $field );
            register_setting( 'schedule_webhook_fields', $field['uid'] );
    	}
    }

    /**
    * Fields used for developer input data
    *
    * @since 1.0.0
    **/
    public function setup_developer_fields() {
        $fields = array(
          array(
            'uid' => 'webhook_address',
            'label' => __('Vercel Deploy Hook URL', 'vercel-deploy-hooks'),
            'section' => 'developer_section',
            'type' => 'text',
                'placeholder' => 'e.g. https://api.vercel.com/v1/integrations/deploy/QmcwKGEbAyFtfybXBxvuSjFT54dc5dRLmAYNB5jxxXsbeZ/hUg65Lj4CV',
                'default' => '',
          ),
          array(
            'uid' => 'enable_on_post_update',
            'label' => __('Activate deploy on post update', 'vercel-deploy-hooks'),
            'section' => 'developer_section',
            'type' => 'checkbox',
            'options' => array(
              'enable' => __('Enable', 'vercel-deploy-hooks'),
              ),
            'default' =>  array()
          ),       
          
          
        );
      foreach( $fields as $field ){
          add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'developer_webhook_fields', $field['section'], $field );
            register_setting( 'developer_webhook_fields', $field['uid'] );
      }
    }

    /**
    * Field callback for handling multiple field types
    *
    * @since 1.0.0
    * @param $arguments
    **/
    public function field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] );

        if ( !$value ) {
            $value = $arguments['default'];
        }

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                break;
            case 'time':
              printf( '<input name="%1$s" id="%1$s" type="time" value="%2$s" />', $arguments['uid'], $value );
              break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
                break;
            case 'select':
            case 'multiselect':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $attributes = '';
                    $options_markup = '';
                    foreach( $arguments['options'] as $key => $label ){
                        $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value[ array_search( $key, $value, true ) ], $key, false ), $label );
                    }
                    if( $arguments['type'] === 'multiselect' ){
                        $attributes = ' multiple="multiple" ';
                    }
                    printf( '<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup );
                }
                break;
            case 'radio':
            case 'checkbox':
                if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
                    $options_markup = '';
                    $iterator = 0;
                    foreach( $arguments['options'] as $key => $label ){
                        $iterator++;
                        $options_markup .= sprintf( '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked( count($value) > 0 ? $value[ array_search( $key, $value, true ) ] : false, $key, false ), $label, $iterator );
                    }
                    printf( '<fieldset>%s</fieldset>', $options_markup );
                }
                break;
        }
    }

    /**
    * Add Deploy Button and Deployment Status to admin bar
    *
    * @since 1.1.0
    **/
    public function add_to_admin_bar( $admin_bar ) {
        $run_deploys = apply_filters( 'vercel_deploy_capability', 'manage_options' );
        if ( current_user_can( $run_deploys ) ) {
            $webhook_address = get_option( 'webhook_address' );
            if ( $webhook_address ) {
                $button = array(
                    'id' => 'vercel-deploy-button',
                    'title' => '<div style="cursor: pointer;"><span class="ab-icon dashicons dashicons-hammer"></span> <span class="ab-label">'. __('Deploy Site', 'vercel-deploy-hooks') .'</span></div>'
                );
                $admin_bar->add_node( $button );
            }
        }
    }

    /**
    *
    * Manage the cron jobs for triggering builds
    *
    * Check if scheduled builds have been enabled, and pass to
    * the enable function. Or disable.
    *
    * @since 1.1.2
    **/
    public function build_schedule_options_updated() {
      $enable_builds = get_option( 'enable_scheduled_builds' );
      if( $enable_builds ){
        // Clean any previous setting
        $this->deactivate_scheduled_cron();
        // Reset schedule
        $this->set_build_schedule_cron();
      } else {
        $this->deactivate_scheduled_cron();
      }
    }

    /**
    *
    * Activate cron job to trigger build
    *
    * @since 1.1.2
    **/
    public function set_build_schedule_cron() {
        $enable_builds = get_option( 'enable_scheduled_builds' );
        if( $enable_builds ){
            if( !wp_next_scheduled('scheduled_vercel_build') ) {
                $schedule = get_option( 'select_schedule_builds' );
                $set_time = get_option( 'select_time_build' );
                $timestamp = strtotime( $set_time );
                wp_schedule_event( $timestamp , $schedule[0], 'scheduled_vercel_build' );
            }
        } else {
            $this->deactivate_scheduled_cron();
        }
    }

    /**
    *
    * Remove cron jobs set by this plugin
    *
    * @since 1.1.2
    **/
    public function deactivate_scheduled_cron(){
        // find out when the last event was scheduled
        $timestamp = wp_next_scheduled('scheduled_vercel_build');
        // unschedule previous event if any
        wp_unschedule_event($timestamp, 'scheduled_vercel_build');
    }

    /**
    *
    * Trigger vercel Build
    *
    * @since 1.1.2
    **/
    public function fire_vercel_webhook(){
        $webhook_url = get_option('webhook_address');
        if($webhook_url){
            $options = array(
                'method'  => 'POST',
            );
            return wp_remote_post($webhook_url, $options);
        }
        return false;
    }

    public function vb_webhook_post($post_id, $post) {
        $enable_builds = get_option( 'enable_on_post_update' );
        if ($enable_builds && $post->post_status === 'publish') {
            $this->fire_vercel_webhook();           
        }
    }
  
    public function vb_webhook_future_post( $post_id ) {
        $this->vb_webhook_post($post_id, get_post($post_id));
    }
  
    public function vb_webhook_update($post_id, $post_after, $post_before) {
        $this->vb_webhook_post($post_id, $post_after);
    }
}

new deployWebhook;
?>