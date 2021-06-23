<?php

GFForms::include_feed_addon_framework();
 
class EnquireGform extends GFAddOn {
 
    protected $_version = ENQUIRE_GFORM_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'enquire_gform';
    protected $_path = 'enquire_gform/enquire_gform.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Enquire Add-On for Gravity Forms';
    protected $_short_title = 'Enquire GForm';
 
    private static $_instance = null;
	
	/**
	 * Get an instance of this class.
	 *
	 * @return EnquireGform
	 */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new EnquireGform();
        }
 
        return self::$_instance;
    }
	
	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
    }
	
	/**
	 * This function maps the fields and then sends the data to the endpoint.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */		
	public function after_submission( $entry, $form ) {

		if (!function_exists('write_log')) {

			function write_log($log) {
				if (true === WP_DEBUG) {
					if (is_array($log) || is_object($log)) {
						error_log(print_r($log, true));
					} else {
						error_log($log);
					}
				}
			}

		}
		
		$active_form = $form['id'];
		
		$settings = $this->get_form_settings( $form );
		$plugin_settings = $this->get_plugin_settings();
		
		$send_form = '';
		if (isset($settings['send_form'])) {
			$send_form = $settings['send_form'];
		}
		
		if ($send_form === '1') {
			
			// write_log('Enquire sending form ' . $active_form);
		
			$json_settings = json_encode($settings);	
			
			$quick_query = [];
			
			if (isset($settings['group_ids']) && !empty($settings['group_ids'])) {
				$group_ids = $settings['group_ids'];
			} else {
				$group_ids = '';
			}
			
			if (isset($settings['enquire_fields_first_name']) && !empty($settings['enquire_fields_first_name'])) {
				$map_first_name = $settings['enquire_fields_first_name'];
				$first_name = $entry[$map_first_name];
				$quick_query['FirstName'] = $first_name;
			}
			
			if (isset($settings['enquire_fields_last_name']) && !empty($settings['enquire_fields_last_name'])) {
				$map_last_name = $settings['enquire_fields_last_name'];
				$last_name = $entry[$map_last_name];
				$quick_query['LastName'] = $last_name;
			}
			
			if (isset($settings['enquire_fields_email_address']) && !empty($settings['enquire_fields_email_address'])) {
				$map_email = $settings['enquire_fields_email_address'];
				$email = $entry[$map_email];
				$quick_query['Email'] = $email;
			} else {
				return;
			}
			
			if (isset($settings['enquire_fields_phone']) && !empty($settings['enquire_fields_phone'])) {
				$map_phone = $settings['enquire_fields_phone'];
				$phone = $entry[$map_phone];
				$quick_query['HomePhone'] = $phone;
			}
			
			if (isset($settings['enquire_fields_message']) && !empty($settings['enquire_fields_message'])) {
				$map_message = $settings['enquire_fields_message'];
				$message = $entry[$map_message];
				$quick_query['Message'] = $message;
			}

			$quick_query['CommunityName'] = $group_ids;
			
			if ( isset($settings['enquire_fields_over_community']) && !empty($settings['enquire_fields_over_community']) ) {
				$map_community = $settings['enquire_fields_over_community'];
				$trim_map_community = trim($entry[$map_community]);
				if ( !empty($trim_map_community) ) {
					$quick_query['CommunityName'] = $trim_map_community;
				}
			}
			
			if ( isset($settings['activity_type_name']) && !empty($settings['activity_type_name']) ) {
				$trim_activity_type_name = trim($settings['activity_type_name']);
				if ( !empty($trim_activity_type_name) ) {
					$quick_query['ActivityTypeName'] = $trim_activity_type_name;
				}
			}
			
			$json_query = json_encode($quick_query);
			// write_log($json_query);
			
			$auth_key = $settings['auth_key'];
			
			$post_url = $settings['target_url'];
			
			$send_mail = false;
			if (isset($plugin_settings['send_debug_email']) && ($plugin_settings['send_debug_email'])) {
				if (isset($plugin_settings['debug_email']) && !empty($plugin_settings['debug_email']) && is_email($plugin_settings['debug_email'])) {
					$send_mail = true;
					$target_email = $plugin_settings['debug_email'];
					$site_name = get_bloginfo('name');
					$email_subject = 'Enquire GForm Debug mail for form id ' . $active_form . ' from ' . $site_name;
				} else {
					write_log('There is an issue with the debug email attached to the Enquire Gform configuration. Please check the configuration.');
				}
			}
			
			$send_w_curl = false;
			
			if ($settings['use_curl']) {
				$send_w_curl = true;
			}

			if ( $send_w_curl ) {
			
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $post_url);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_query);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				// curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
					'Ocp-Apim-Subscription-Key: '. $auth_key,
					'Content-Type: application/json'                        
					)
				);
				
				$result = curl_exec($ch);
				$error = curl_error($ch);
				
				$info = curl_getinfo($ch);
				$response = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
							
				// write_log('cURL used to post to Enquire. Query Sent: ' . $json_query . ' Response: ' . $response . ' Result: ' . $result);
				
				if ($result === false) {
					$error = curl_error($ch);
					$error_number = curl_errno($ch);
					$message = 'cURL error posting Gravity Form ID #' . $active_form . ' to Enquire. Query Sent: ' . $json_query . ' Error information: ' . $error_number . ' ' . $error;
					
				} else {
					$message = 'cURL used to post Gravity Form ID #' . $active_form . ' to Enquire. Query Sent: ' . $json_query . ' Response: ' . $response . ' Result: ' . $result;
				}

				write_log($message);
				
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
		
			} else {
		
				$headers = array (
					'Ocp-Apim-Subscription-Key' => $auth_key,
					'Content-type' => 'application/json'
				);

				$em_connect = array (
					'method' => 'POST',
					'timeout' => 15,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => $headers,
					'body' => $json_query,
					'cookies' => array()
				);

				$response = wp_remote_post( $post_url, $em_connect );
				
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					$message = 'Wordpress Remote Post error posting Gravity Form ID #' . $active_form . ' to Enquire. Query Sent: ' . $json_query . ' Error information: ' . $error_message;
				} else {
					$message = 'Wordpress Remote Post used to post Gravity Form ID #' . $active_form . ' to Enquire. Query Sent: ' . $json_query . ' Response: ' . wp_remote_retrieve_response_code($response) . ' - ' . wp_remote_retrieve_response_message($response). ' Result: ' . wp_remote_retrieve_body($response);
				}
				
				write_log($message);
				
				if ($send_mail) {
					wp_mail($target_email, $email_subject, $message);
				}
				
			}		
		
		} else {
			// write_log('Enquire not sending form ' . $active_form);
		}

	}
 
	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'enquire_gform_js',
                'src'     => $this->get_base_url() . '/js/enquire_gform.js',
                'version' => $this->_version,
                'deps'    => array( 'jquery' ),
                'strings' => array(),
                'enqueue' => array(
                    array(
                        'admin_page' => array( 'plugin_page' )
                    )
                )
            ),
 
        );
 
        return array_merge( parent::scripts(), $scripts );
    }

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
    public function styles() {
        $styles = array(
            array(
                'handle'  => 'enquire_gform_css',
                'src'     => $this->get_base_url() . '/css/enquire_gform.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array( 
						'admin_page' => array( 'plugin_page' ) 
					)
                )
            )
        );
 
        return array_merge( parent::styles(), $styles );
    }
 
	/**
	 * Add the text in the plugin settings to the bottom of the form if enabled for this form.
	 *
	 * @param string $button The string containing the input tag to be filtered.
	 * @param array $form The form currently being displayed.
	 *
	 * @return string
	 */
    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "<div>{$text}</div>" . $button;
        }
 
        return $button;
    }

	/**
	 * Creates a custom page for this add-on.
	 */		
    public function plugin_page() {
		
		$instructions = '';
		$instructions .= '<p>For use only with Gravity Forms v1.9 or greater.</p>';
		$instructions .= '<h2>Instructions</h2>';
		$instructions .= '<p>Most configuration settings are done on a form by form basis (see "Sending a Debug Email" below), and can be found under admin -> Forms -> Forms -> {form name} -> Settings -> Enquire GForm.</p>';
		$instructions .= '<p>Select the "Send this form to Enquire" checkbox to attach the form. You will need a Subscription Key.</p>';
		$instructions .= '<p>The Enquire Endpoint URL may be edited if necessary.</p>';
		$instructions .= '<p>Add any Enquire Community Names under Community Names. If you have more than one, seperate them with a comma. Community Names are provided by Enquire.</p>';
		$instructions .= '<p>You can add an Activity Type Name if necessary. Enquire will default to the value \'Web Form\' if nothing is entered. This may be important if you are tracking by which sources leads are entered.</p>';
		$instructions .= '<p>By default this plugin uses Remote Post (wp_remote_post) to send form data. This can be changed to to use cURL. If you have cURL installed and wish to use this method, select this checkbox.</p>';
		$instructions .= '<p>To map the form fields, select the relevant Field (to be mapped for Enquire) to the Form Field (from the Gravity Form).</p>';
		$instructions .= '<p>The form field must be of the correct type. The mapping is as follows:</p>';
		$instructions .= '<ul class="instruction">';
		$instructions .= '<li>First Name -> name, text or hidden</li>';
		$instructions .= '<li>Last Name -> name, text or hidden</li>';
		$instructions .= '<li>Email Address -> email or hidden</li>';
		$instructions .= '<li>Home Phone -> phone or hidden</li>';
		$instructions .= '<li>Message -> textarea, text or hidden</li>';
		$instructions .= '<li>Community Names -> select</li>';
		$instructions .= '</ul>';
		$instructions .= '<p>So make sure when creating your form that you use the correct form field types for the Enquire field mapping.</p>';
		$instructions .= '<p>If you map the Community Names field, this value will overwrite the required Community Names for the form. This field is provided to allow for multiple communities to be assigned to a single form (and selected by an end user). When mapping this field, please ensure that the Value (and not the Label) of the field is set to a valid Community Name as provided by Enquire. Please note that the Community Names is still a required field in the form\'s settings.</p>';
		$instructions .= '<h3>Sending a Debug Email</h3>';
		$instructions .= '<p>You can send a debug email for all submissions that contain logging information if you do not have logging enabled. This setting can be found under admin -> Forms -> Settings -> Enquire GForm.</p>';
		$instructions .= '<p>Select "Send a debug email" to enable this feature, and enter a valid email under "Debug email address". This will send an email containing logging information for all forms submitted to Enquire.</p>';
		
        echo $instructions;
    }
	
	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */ 
    public function plugin_settings_fields() {
		
		// echo 'There are no global settings for Enquire GForm.';
		
        return array(
            array(
                'title'  => esc_html__( 'Enquire GForm Settings', 'enquire_gform' ),
                'fields' => array(
					/*
                    array(
                        'name' => 'setting_page',
                        'type' => 'custom_field_type',
                    ),
					*/
					array(
						'label' => esc_html__('Send a debug email', 'enquire_gform'),
						'type' => 'checkbox',
						'name' => 'send_debug_email',
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'enquire_gform'),
								'name' => 'send_debug_email'
							),
						),
					),
					array(
						'label' => esc_html__('Debug email address', 'enquire_gform'),
						'type' => 'text',
						'name' => 'debug_email',
						'default_value' => 'someone@example.com',
						'tooltip' => esc_html__('Enter a valid email address.', 'enquire_gform'),
						'style' => 'width: 300px;',
					),
                )
            )
        );
		
    }
	
	/*
	public function settings_custom_field_type($field, $echo = true) {
		
		echo '<p>' . esc_html__('There are no settings here. Page maintained for uninstall.') . '</p>';
		
	}
	*/	
	
 	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Enquire GForm area.
	 *
	 * @return array
	 */
    public function form_settings_fields($form) {
		
        return array(
			array(
				'title' => esc_html__('Enquire GForm Settings', 'enquire_gform'),
				'fields' => array(
					array(
						'label' => esc_html__('Send this form to Enquire'),
						'type' => 'checkbox',
						'name' => 'send_form',
						'tooltip' => esc_html__('Select to send form submissions to enquire.', 'enquire_gform'),
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'enquire_gform'),
								'name' => 'send_form'
							),
						),
					),
					array(
						'label' => esc_html__('Subscription Key', 'enquire_gform'),
						'type' => 'text',
						'name' => 'auth_key',
						'tooltip' => esc_html__('The Enquire 2.0 subscription key. This is provided by Enquire to connect to the remote API.', 'enquire_gform'),
						'style' => 'width: 400px;',
						'required' => true,
						'default_value' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
					),
					array(
						'label' => esc_html__('Enquire Endpoint URL', 'enquire_gform'),
						'type' => 'text',
						'name' => 'target_url',
						'tooltip' => esc_html__('The endpoint url.'),
						'style' => 'width: 400px;',
						'required' => true,
						'default_value' => 'https://api2.enquiresolutions.com/2/Individual/',
					),
					array(
						'label' => esc_html__('Community Names', 'enquire_gform'),
						'type' => 'text',
						'name' => 'group_ids',
						'tooltip' => esc_html__('Community Names comma delinated.', 'enquire_gform'),
						'class' => 'small',
						'required' => true,
					),
					array(
						'label' => esc_html__('Activity Type Name', 'enquire_gform'),
						'type' => 'text',
						'name' => 'activity_type_name',
						'tooltip' => esc_html__('The name of the activity type that gets created as Inquiry. It will default to type \'Web Form\' if nothing is entered.', 'enquire_gform'),
						'required' => false,
					),
					array(
						'label' => esc_html__('Use cURL', 'enquire_gform'),
						'type' => 'checkbox',
						'name' => 'use_curl',
						'tooltip' => esc_html__('Send form data using cURL. If unselected, Wordpress Remote Post will be used.', 'enquire_gform'),
						'choices' => array(
							array(
								'label' => esc_html__('Yes', 'enquire_gform'),
								'name' => 'use_curl',
							),
						),
					),
					
				),			
				
			),
			array(
				'title'  => esc_html__( 'Map Enquire Fields', 'enquire_gform' ),
				'fields' => array(
					array(
						'name'      => 'enquire_fields',
						'label'     => esc_html__( 'Map Fields', 'enquire_gform' ),
						'type'      => 'field_map',
						'field_map' => $this->enquire_fields_for_feed_mapping(),
						'tooltip'   => '<h6>' . esc_html__('Map Fields', 'enquire_gform' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective third-party service fields.', 'enquire_gform'),
					),
				),
			),
        );
    }
	
 	/**
	 * Configures the mapping fiels on the GForm config page.
	 *
	 * @return array
	 */
	public function enquire_fields_for_feed_mapping() {
		return array(
			array(
				'name'          => 'first_name',
				'label'         => esc_html__( 'First Name', 'enquire_gform' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'tooltip' => esc_html__('Must be a text field type', 'enquire_gform'),
				'default_value' => $this->get_first_field_by_type( 'name', 3 ),
			),
			array(
				'name'          => 'last_name',
				'label'         => esc_html__( 'Last Name', 'enquire_gform' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'tooltip' => esc_html__('Must be a text field type', 'enquire_gform'),
				'default_value' => $this->get_first_field_by_type( 'name', 6 ),
			),
			array(
				'name'          => 'email_address',
				'label'         => esc_html__( 'Email Address', 'enquire_gform' ),
				'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'tooltip' => esc_html__('Must be an email field type', 'enquire_gform'),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
			array(
				'name' => 'phone',
				'label' => esc_html__('Phone', 'enquire_gform'),
				'required' => false,
				'field_type' => array('phone', 'hidden'),
				'tooltip' => esc_html__('Must be a phone field type', 'enquire_gform'),
				'default_value' => $this->get_first_field_by_type( 'phone' ),
			),
			array(
				'name' => 'message',
				'label' => esc_html__('Message', 'enquire_gform'),
				'required' => false,
				'field_type' => array('textarea', 'text', 'hidden'),
				'tooltip' => esc_html__('Must be a textarea or text field type', 'enquire_gform'),
				'default_value' => $this->get_first_field_by_type( 'textarea' ),
			),
			array(
				'name' => 'over_community',
				'label' => esc_html__('Community Names', 'enquire_gform'),
				'required' => false,
				'field_type' => array('select'),
				'tooltip' => esc_html__('Overwrites form\'s Community Names. Must be a select field type.', 'enquire_gform'),
			),
		);
	}
 
}
