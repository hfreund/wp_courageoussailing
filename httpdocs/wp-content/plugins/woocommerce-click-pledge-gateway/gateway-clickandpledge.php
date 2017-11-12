<?php
/*
Plugin Name: WooCommerce Click & Pledge Gateway
Plugin URI: http://manual.clickandpledge.com/
Description: With Click & Pledge, Accept all major credit cards directly on your WooCommerce website with a seamless and secure checkout experience.<a href="http://manual.clickandpledge.com/" target="_blank">Click Here</a> to get a Click & Pledge account.
Version: 2.100.002
Author: Click & Pledge
Author URI: http://www.clickandpledge.com
*/
@ini_set('display_errors', 0);
add_action('plugins_loaded', 'woocommerce_clickandpledge_init', 0);

function woocommerce_clickandpledge_init() {
	
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		add_action( 'admin_notices', 'wc_cnp_notice' );
	}
	function wc_cnp_notice() {
		echo '<div class="error"><p><strong> <i> WooCommerce Click & Pledge Gateway </i> </strong> Requires <a href="'.admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce').'"> <strong> <u>Woocommerce</u></strong>  </a> To Be Installed And Activated </p></div>';
	}
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

	require_once( WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/classes/clickandpledge-request.php' );	
	/**
 	* Gateway class
 	**/
	class WC_Gateway_ClickandPledge extends WC_Payment_Gateway {
		var $AccountID;
		var $AccountGuid;
		var $maxrecurrings_Installment;
		var $maxrecurrings_Subscription;
		var $pselectedval;
		var $liveurl = 'http://manual.clickandpledge.com/';
		var $testurl = 'http://manual.clickandpledge.com/';
		var $testmode;
	
		function __construct() { 
			
			$this->id				= 'clickandpledge';
			$this->method_title 	= __('Click & Pledge', 'woothemes');
			$this->icon 			= WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/CP_Secured.jpg';
			
			// Load the form fields
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Get setting values
			$this->title 			= $this->settings['title'];
			$this->description 		= $this->settings['description'];
			$this->enabled 			= $this->settings['enabled'];
			$this->AccountID 	    = $this->settings['AccountID'];
			$this->AccountGuid    	= $this->settings['AccountGuid'];
			$this->testmode 		= $this->settings['testmode'];
			$this->defaultpayment   = $this->settings['DefaultpaymentMethod'];
			$this->ReferenceNumber_Label   = $this->settings['ReferenceNumber_Label'];
			$this->Periodicity      = array();
			$this->RecurringMethod  = array();
			$this->available_cards  = array();
			$this->CustomPayments   = array();
			$this->recurring_details   = array();
			if($this->description == "") {$this->description = "Pay with Click & Pledge Payment Gateway.";}
			if(isset($this->settings['CreditCard']) && $this->settings['CreditCard'] == 'yes')
			$this->Paymentmethods['CreditCard'] = 'Credit Card';
			if(isset($this->settings['eCheck']) && $this->settings['eCheck'] == 'yes')
			$this->Paymentmethods['eCheck'] = 'eCheck';
		
			if(isset($this->settings['CustomPayment']) && $this->settings['CustomPayment'] == 'yes') {
				$CustomPayments = explode(';', html_entity_decode($this->settings['CustomPayment_Titles']));
				if(count($CustomPayments) > 0) {
					foreach($CustomPayments as $key => $val) {
						if(trim($val) != '') {
							$this->Paymentmethods[trim($val)] = trim($val);
							$this->CustomPayments[] = trim($val);
						}
					}
				}				
			}			
			
			//Available Credit Cards
			$this->acceptedcreditcards_details = get_option( 'woocommerce_clickandpledge_acceptedcreditcards',
		
				array(
					'Visa'                 => $this->get_option( 'Visa' ),
					'American Express'     => $this->get_option( 'American Express' ),
					'Discover'             => $this->get_option( 'Discover' ),
					'MasterCard'           => $this->get_option( 'MasterCard' ),
					'JCB'                  => $this->get_option( 'JCB' )
					
				
			)
		);
			if((isset($this->acceptedcreditcards_details['Visa']) && ($this->acceptedcreditcards_details['Visa'] == 'Visa') )){
				$this->available_cards['Visa']		= 'Visa';
			}
			if((isset($this->acceptedcreditcards_details['American_Express']) && ($this->acceptedcreditcards_details['American_Express'] == 'American Express') )){
				$this->available_cards['American Express']		= 'American Express';
			}
			if((isset($this->acceptedcreditcards_details['Discover']) && ($this->acceptedcreditcards_details['Discover'] == 'Discover') )){
				$this->available_cards['Discover']		= 'Discover';
			}
			if((isset($this->acceptedcreditcards_details['MasterCard']) && ($this->acceptedcreditcards_details['MasterCard'] == 'MasterCard') )){
				$this->available_cards['MasterCard']		= 'MasterCard';
			}
			if((isset($this->acceptedcreditcards_details['JCB']) && ($this->acceptedcreditcards_details['JCB'] == 'JCB') )){
				$this->available_cards['JCB']		= 'JCB';
			}
			
		
			$this->isRecurring 		= (isset($this->settings['isRecurring']) && ($this->settings['isRecurring'] == '1')) ? true : false;
		
			$this->indefinite 		= (isset($this->settings['indefinite']) && $this->settings['indefinite'] == 'yes') ? true : false;
			$this->recurring_details = get_option( 'woocommerce_clickandpledge_recurring',
		
				array(
					'Installment'      => $this->get_option( 'Installment' ),
					'Subscription'     => $this->get_option( 'Subscription' ),
					'week'             => $this->get_option( 'week' ),
					'tweeks'           => $this->get_option( 'tweeks' ),
					'month'            => $this->get_option( 'month' ),
					'2months'          => $this->get_option( '2months' ),
					'quarter'          => $this->get_option( 'quarter' ),
					'smonths'          => $this->get_option( 'smonths' ),
					'year'             => $this->get_option( 'year' ),
					'indefinite'       => $this->get_option( 'indefinite' ),
					'isRecurring_oto'  => $this->get_option( 'isRecurring_oto' ),
					'isRecurring_recurring'  => $this->get_option( 'isRecurring_recurring' ),
					'dfltpayoptn'            => $this->get_option( 'dfltpayoptn' ),
					'dfltrectypoptn'         => $this->get_option( 'dfltrectypoptn' ),
					'dfltnoofpaymnts'        => $this->get_option( 'dfltnoofpaymnts' ),
					'payoptn'                => $this->get_option( 'payoptn' ),
					'rectype'                => $this->get_option( 'rectype' ),
					'periodicity'            => $this->get_option( 'periodicity' ),
					'noofpayments'           => $this->get_option( 'noofpayments' ),
					'dfltnoofpaymentslbl'    => $this->get_option( 'dfltnoofpaymentslbl' ),
					'maxnoofinstallments'    => $this->get_option( 'maxnoofinstallments' ),
					'maxrecurrings_Subscription'  => $this->get_option( 'maxrecurrings_Subscription' )
				
			)
		);
		
		   if((isset($this->recurring_details['installment']) && ($this->recurring_details['installment'] == 'Installment') )){
				$this->RecurringMethod['Installment']		= 'Installment';
			}
			if((isset($this->recurring_details['subscription']) && ($this->recurring_details['subscription'] == 'Subscription') )){
				$this->RecurringMethod['Subscription']		= 'Subscription';
			}
			
			if((isset($this->recurring_details['week']) && ($this->recurring_details['week'] == 'Week') )) {
				$this->Periodicity['Week']		= 'Week';
			}
			if((isset($this->recurring_details['2_weeks']) && ($this->recurring_details['2_weeks'] == '2 Weeks'))) {
				$this->Periodicity['2 Weeks']		= '2 Weeks';
			}
			if((isset($this->recurring_details['month']) && ($this->recurring_details['month'] == 'Month'))) {
				$this->Periodicity['Month']		= 'Month';
			}
			if((isset($this->recurring_details['2_months']) && ($this->recurring_details['2_months'] == '2 Months'))) {
				$this->Periodicity['2 Months']		= '2 Months';
			}
			if((isset($this->recurring_details['quarter']) && ($this->recurring_details['quarter'] == 'Quarter') )) {
				$this->Periodicity['Quarter']		= 'Quarter';
			}
			if((isset($this->recurring_details['6_months']) && ($this->recurring_details['6_months'] == '6 Months') )){
				$this->Periodicity['6 Months']		= '6 Months';
			}
			if((isset($this->recurring_details['year']) && ($this->recurring_details['year'] == 'Year') )){
				$this->Periodicity['Year']		= 'Year';
			}
			
			// Hooks
			add_action( 'admin_notices', array( &$this, 'ssl_check') );			
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_recurring_details' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_acceptedcreditcards_details' ) );
			
		}
		
		/**
	 	* Check if SSL is enabled and notify the user if SSL is not enabled
	 	**/
		function ssl_check() {
	      if ($this->recurring_details['isRecurring_oto'] == "" &&  $this->recurring_details['isRecurring_recurring'] == "")
		 {	
				echo '<div class="error"><p>'.sprintf(__('Click & Pledge is enabled, but you have not added any <strong>Recurring Settings</strong>.<br>Customers will not be able to purchase products from your store until you set <strong>Recurring Settings</strong>.', 'woothemes'), admin_url('admin.php?page=woocommerce')).'</p></div>';
		}

		if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
		
			echo '<div class="error"><p>'.sprintf(__('Click & Pledge is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate - Click & Pledge will only work in test mode.', 'woothemes'), admin_url('admin.php?page=woocommerce')).'</p></div>';
		
		endif;
		
		}
		
		/**
	     * Initialize Gateway Settings Form Fields
	     */
	    function init_form_fields() {			
			$paddingleft = 80;	
			$paddingheight ="height:2px;";		
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Status', 'woothemes' ), 
								'label' => __( 'Enable Click & Pledge', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => '', 
								'default' => true,
							), 
							
			
				'testmode' => array(
							'type'        => 'testmode_details'
			   ),	
				'title' => array(
								'title' => __( 'Title <span style="color: #ff0000;">*</span>', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Credit Card', 'woothemes' ),
								'desc_tip'    => true,
							), 
							
				
				'description' => array(
								'title' => __( 'Description', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'The payment description.', 'woothemes' ), 
								'default' => 'Pay with Click & Pledge Payment Gateway.',
								'desc_tip'    => true,
							),  
				
				'AccountID' => array(
								'title' => __( 'C&P Account ID <span style="color: #ff0000;">*</span>', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "Account ID" from Click & Pledge. [Portal > Account Info > API Information].', 'woothemes' ), 
								'default' => '',
								'class' => 'required',
								'desc_tip'    => true,
							), 
				'AccountGuid' => array(
								'title' => __( 'C&P API Account GUID <span style="color: #ff0000;">*</span>', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "API Account GUID" from Click & Pledge [Portal > Account Info > API Information].', 'woothemes' ), 
								'default' => '',
								'maxlength' => 200,
								'desc_tip'    => true,
							),
				
											
				
							
				'Paymentmethods' => array(
								'title' => __( 'Payment Methods', 'woothemes' ), 
								'type' => 'title',
							),

				'CreditCard' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Credit Card</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => 'yes',
								'label'       => __( ' ', 'woocommerce' ),
							),
				'AcceptedCreditCards' => array(
								'title' => __( 'Accepted Credit Cards', 'woothemes' ), 
								'type' => 'acceptedcreditcards_details',
								'class' => 'CredicardSection',
								
							),
		
				
				/*'Preauthorization' => array(
								'title' => __( 'Allow Pre-Authorization for 0 (Zero) balance', 'woothemes' ), 
								'type' => 'title', 
								'description' => __( 'To allow for processing free transactions (CreditCard) , the following manual change has to be made to the "needs_payment" function in "woocommerce/includes/class-wc-cart.php".<br> <b>Original code</b>: return apply_filters( \'woocommerce_cart_needs_payment\', $this->total > 0, $this );<br>
<b>Modified code</b>: return apply_filters( \'woocommerce_cart_needs_payment\', $this->total >= 0, $this );<br>This code change has to be made after each upgrade of woocommerce.', 'woothemes' ),
								'default' => 'no',
								'label'       => __( ' ', 'woocommerce' ),
							),*/
				'eCheck' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">eCheck</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ),
								'label'       => __( ' ', 'woocommerce' ),
							),
				
				'CustomPayment' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Custom Payment</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ),
								'label'       => __( ' ', 'woocommerce' ),
							),
				'CustomPayment_Titles' => array(
								'title' => __( '<span style="padding-left:130px;">Title(s)</span>', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'Separate with semicolon (;)', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500,
							),
					'ReferenceNumber_Label' => array(
								'title' => __( 'Reference Number Label', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( '', 'woothemes' ), 
								'default' => '',
							),			
							
				'DefaultpaymentMethod' => array(
								'title' => __( 'Default Payment Method', 'woothemes' ), 
								'type' => 'select',
								'class' => '',
								'options'     => array(
									  '' => 'Please select',
									 
								),
							),
				
							
				'ReceiptSettings' => array(
								'title' => __( 'Receipt Settings', 'woothemes' ), 
								'type' => 'title',
								'class' => 'ReceiptSettingsSection',
							),
				'cnp_email_customer' => array(
								'title' => __( 'Send Receipt to Patron', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => 'yes',
								'label'       => __( ' ', 'woocommerce' ),
							),
							
				'OrganizationInformation' => array(
								'title' => __( 'Receipt Header', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'Maximum: 1500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;.  You have <span id="OrganizationInformation_countdown">1500</span> characters left.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500,
							),				
				'TermsCondition' => array(
								'title' => __( 'Terms & Conditions', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'To be added at the bottom of the receipt. Typically the text provides proof that the patron has read & agreed to the terms & conditions. The following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. <br>Maximum: 1500 characters, You have <span id="TermsCondition_countdown">1500</span> characters left.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500
							),
							
				'RecurringSection' => array(
								'title' => __( 'Recurring Settings', 'woothemes' ), 
								'type' => 'title',
								'class' => 'RecurringSection',
							),
				
				'RecurringLabel' => array(
								'title' => __( 'Label', 'woothemes' ), 
								'type' => 'text',
								'disabled' => false,
								'description' => __( '', 'woothemes' ), 
								'default' => 'Set this as a recurring payment',
								'css' => 'maxlength:200;',
							),
				
				'recurring_details' => array(
							'type'        => 'recurring_details'
			),
				
				);
	    }
		public function generate_testmode_details_html() {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'API Mode', 'woocommerce' ); ?>:</th>
			<td class="forminp" id="cnp_apimode">
				<table  cellspacing="0">
					
					<tbody class="accounts">
						<?php
							echo '<tr class="account">									
									<td style="padding:2px;"><input type="radio" value="yes" name="woocommerce_clickandpledge_testmode" id="woocommerce_clickandpledge_testmode" '.checked($this->testmode, 'yes',false ).'/>Test Mode</td>
									<td><input type="radio" value="no" name="woocommerce_clickandpledge_testmode" id="woocommerce_clickandpledge_testmode"  '.checked( $this->testmode, 'no',false ).'/>Live Mode</td>
								  </tr>';
							
						?>
					</tbody>
					
				</table>
				
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}
	 public function generate_acceptedcreditcards_details_html() {
		ob_start();
		?>
		<tr valign="top" class="clsacptcrds">
			<th scope="row" class="titledesc"><?php // _e( 'Accepted Credit Cards', 'woocommerce' ); ?></th>
			<td  id="cnp_cards">
				<table  cellspacing="0">
					
					<tbody class="accounts">
						<?php
							echo '<tr class="account" >								
									<td style="padding:2px;"><strong>Accepted Credit Cards</strong></td></tr>
							<tr class="account" >								
									<td style="padding:2px;"><br><input type="Checkbox"  name="woocommerce_clickandpledge_Visa" id="woocommerce_clickandpledge_Visa" '. checked($this->available_cards['Visa'], 'Visa',false ).' value="Visa"/>Visa</td></tr><tr>
									<td style="padding:2px;"><input type="Checkbox"  name="woocommerce_clickandpledge_American_Express" id="woocommerce_clickandpledge_American_Express"  '.checked( $this->available_cards['American Express'], 'American Express',false ).' value="American Express"/>American Express</td>
								  </tr>
								  <tr>
									<td style="padding:2px;"><input type="Checkbox"  name="woocommerce_clickandpledge_Discover" id="woocommerce_clickandpledge_Discover"  '.checked( $this->available_cards['Discover'], 'Discover',false ).' value="Discover"/>Discover</td>
								  </tr>
								  <tr>
									<td style="padding:2px;"><input type="Checkbox"  name="woocommerce_clickandpledge_MasterCard" id="woocommerce_clickandpledge_MasterCard"  '.checked( $this->available_cards['MasterCard'], 'MasterCard',false ).' value="MasterCard"/>MasterCard</td>
								  </tr>
								  <tr>
									<td style="padding:2px;"><input type="Checkbox"  name="woocommerce_clickandpledge_JCB" id="woocommerce_clickandpledge_JCB"  '.checked($this->available_cards['JCB'], 'JCB',false ).' value="JCB"/>JCB</td>
								  </tr>';
							
						?>
						
					</tbody>
					
				</table>
				
			</td>
		</tr>
		<tr><td><strong>Allow Pre-Authorization for 0 (Zero) balance</strong> </td><td>To allow for processing free transactions  , the following manual change has to be made to the "needs_payment" function in "woocommerce/includes/class-wc-cart.php".<br> <b>Original code</b>: return apply_filters( \'woocommerce_cart_needs_payment\', $this->total > 0, $this );<br>
<b>Modified code</b>: return apply_filters( \'woocommerce_cart_needs_payment\', $this->total >= 0, $this );<br>This code change has to be made after each upgrade of WooCommerce. <td><tr>
		<?php
		return ob_get_clean();
   }
	    public function generate_recurring_details_html() {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php  _e( 'Settings', 'woocommerce' ); ?></th>
			<td class="forminp" id="cnp_recdtl">
				<table  cellspacing="0">
					<tbody>
					<tr><td valign="top">
					<label for="woocommerce_recurring_paymentoptions_label">	
					<input type="text" name="woocommerce_clickandpledge_payoptn" id="woocommerce_clickandpledge_payoptn" value='<?php if(esc_attr( $this->recurring_details['payoptn'] ) == ""){ echo "Payment options";}else{ echo esc_attr( $this->recurring_details['payoptn']);}  ?>'  placeholder='Payment options' onchange="" />
					</label>
					</td>
					<td>
					<div><input type="checkbox" id="woocommerce_clickandpledge_isRecurring_oto" name="woocommerce_clickandpledge_isRecurring_oto" value="0" <?php echo checked($this->recurring_details['isRecurring_oto'],'0',false )?> class="rectyp">&nbsp;One Time Only</div>	
					<div><input type="checkbox" id="woocommerce_clickandpledge_isRecurring_recurring" name="woocommerce_clickandpledge_isRecurring_recurring" value="1"  <?php echo checked($this->recurring_details['isRecurring_recurring'],1,false );?> class="rectyp">&nbsp;Recurring</div>
					</td></tr>
					
					<tr class="trdfltpymntoptn"><td><label>Default payment options </label></td><td>
					<select name="woocommerce_clickandpledge_dfltpayoptn" id="woocommerce_clickandpledge_dfltpayoptn" >
					<option value="Recurring" <?php selected( $this->recurring_details['dfltpayoptn'],'Recurring' ); ?>>Recurring</option>
					<option value="One Time Only" <?php selected( $this->recurring_details['dfltpayoptn'],'One Time Only' ); ?>>One Time Only</option>
					</select></td></tr>	
					
					<tr class="trrectyp"><td valign="top">
					<label for="gfcnp_recurring_RecurringTypes_label"><input type="text" name="woocommerce_clickandpledge_rectype" id="woocommerce_clickandpledge_rectype" 
					value='<?php if(esc_attr( $this->recurring_details['rectype'] ) == ""){ echo "Recurring types";}else{ echo esc_attr( $this->recurring_details['rectype']);}  ?>'  placeholder='Recurring types' /></label>	
						</td><td><div><input type="checkbox" id="woocommerce_clickandpledge_Installment" name="woocommerce_clickandpledge_Installment"  value="Installment" <?php echo checked($this->recurring_details['installment'],'Installment',false )?> class='clsrectype' >&nbsp;Installment (e.g. pay $1000 in 10 installments of $100 each)</div>
					<div><input type="checkbox" id="woocommerce_clickandpledge_Subscription" name="woocommerce_clickandpledge_Subscription" value="Subscription" class='clsrectype'  <?php echo checked($this->recurring_details['subscription'], 'Subscription',false )?>>&nbsp;Subscription (e.g. pay $100 every month for 12  months)</div>	
					
					</td></tr>
					
					<tr class="trdfltrecoptn" id="trdfltrecoptn"><td><label>Default Recurring type</label></td><td>
					<select name="woocommerce_clickandpledge_dfltrectypoptn" id="woocommerce_clickandpledge_dfltrectypoptn" >
					<option value="Subscription" <?php selected( $this->recurring_details['dfltrectypoptn'],'Subscription' ); ?>>Subscription</option>
					<option value="Installment" <?php selected( $this->recurring_details['dfltrectypoptn'],'Installment' ); ?>>Installment</option>
					</select></td></tr>	
					<script language="javascript">
					
						jQuery('#woocommerce_clickandpledge_Subscription').click(function(){
						if(jQuery("#woocommerce_clickandpledge_Installment").is(':checked') && jQuery("#woocommerce_clickandpledge_Subscription").is(':checked'))
						{
						  jQuery("tr.trdfltrecoptn").show();
						}
						});
					</script>
					<tr class="trprdcty"><td valign="top">
					<label for="gfcnp_recurring_periodicity_label">
					<input type="text" name="woocommerce_clickandpledge_periodicity" id="woocommerce_clickandpledge_periodicity"  placeholder='Periodicity'  value="<?php if(esc_attr( $this->recurring_details['periodicity'] ) == ""){ echo "Periodicity";}else{ echo esc_attr( $this->recurring_details['periodicity']);}  ?>"/>
					</label></td>
					<td><div>
					<input type="checkbox" id="woocommerce_clickandpledge_Week" name="woocommerce_clickandpledge_Week" value="Week" <?php echo checked($this->recurring_details['week'], 'Week',false )?>  onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Week<br>
					<input type="checkbox" id="woocommerce_clickandpledge_2_Weeks" name="woocommerce_clickandpledge_2_Weeks" value="2 Weeks" <?php echo checked($this->recurring_details['2_weeks'], '2 Weeks',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;2 Weeks<br>
					<input type="checkbox" id="woocommerce_clickandpledge_Month" name="woocommerce_clickandpledge_Month" value="Month" <?php echo checked($this->recurring_details['month'], 'Month',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Month<br>
					<input type="checkbox" id="woocommerce_clickandpledge_2_Months" name="woocommerce_clickandpledge_2_Months" value="2 Months" <?php echo checked($this->recurring_details['2_months'], '2 Months',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;2 Months<br>
					<input type="checkbox" id="woocommerce_clickandpledge_Quarter" name="woocommerce_clickandpledge_Quarter" value="Quarter" <?php echo checked($this->recurring_details['quarter'], 'Quarter',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Quarter<br>
					<input type="checkbox" id="woocommerce_clickandpledge_6_Months" name="woocommerce_clickandpledge_6_Months" value="6 Months" <?php echo checked($this->recurring_details['6_months'], '6 Months',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;6 Months<br>
					<input type="checkbox" id="woocommerce_clickandpledge_Year" name="woocommerce_clickandpledge_Year" value="Year" <?php echo checked($this->recurring_details['year'], 'Year',false )?> onclick="GFCnpRecurring.FieldSet2(this)">&nbsp;Year<br><br>
					</div></td></tr>
					<tr class="trnoofpaymnts"><td valign="top">
					<label for="woocommerce_clickandpledge_recurring_Noofpaymnts_label"><input type="text" name="woocommerce_clickandpledge_noofpayments" id="woocommerce_clickandpledge_noofpayments" value="<?php if(esc_attr( $this->recurring_details['noofpayments'] ) == ""){ echo "Number of payments";}else{ echo esc_attr( $this->recurring_details['noofpayments']);}  ?>" placeholder='Number of payments' /></label>
					</td><td><div id="indefinite_div">
					<input type="radio" class='clsnoofpaymnts' name="woocommerce_clickandpledge_indefinite" id="woocommerce_clickandpledge_indefinite" value="1" <?php echo checked($this->recurring_details['indefinite'], '1', false )?>>&nbsp;Indefinite Only
					</div><div id="openfild_div">
					<input type="radio" class='clsnoofpaymnts' name="woocommerce_clickandpledge_indefinite" id="woocommerce_clickandpledge_indefinite" value="openfield"  <?php echo checked($this->recurring_details['indefinite'], 'openfield',false )?>>&nbsp;Open Field Only
					</div><div id="indefinite_openfield_div">
					<input type="radio" class='clsnoofpaymnts' name="woocommerce_clickandpledge_indefinite"  id="woocommerce_clickandpledge_indefinite" value="indefinite_openfield"  <?php echo checked($this->recurring_details['indefinite'], 'indefinite_openfield',false )?>>&nbsp;Indefinite + Open Field Option</div><div id="fixdnumber_div">
					<input type="radio" class='clsnoofpaymnts' name="woocommerce_clickandpledge_indefinite"  id="woocommerce_clickandpledge_indefinite" value="fixednumber"  <?php echo checked($this->recurring_details['indefinite'], 'fixednumber',false )?>>&nbsp;Fixed Number - No Change Allowed</div>
				   </td></tr>
					<tr class="dfltnoofpaymnts"><td>
					<label><input type="text" name="woocommerce_clickandpledge_dfltnoofpaymentslbl" id="woocommerce_clickandpledge_dfltnoofpaymentslbl"  placeholder='Default number of payments' value="<?php if(esc_attr( $this->recurring_details['dfltnoofpaymentslbl'] ) == ""){ echo "Default number of payments";}else{ echo esc_attr( $this->recurring_details['dfltnoofpaymentslbl']);}  ?>"/></label></td>
					<td><input type="text"  id="woocommerce_clickandpledge_dfltnoofpaymnts"  maxlength="3" name="woocommerce_clickandpledge_dfltnoofpaymnts" value="<?php echo esc_attr( $this->recurring_details['dfltnoofpaymnts'] ) ?>"  /></td></tr>
					<tr class="maxnoofinstlmnts"><td><div id="maxnoofinstlmntslbl_div">
					<label><input type="text" name="woocommerce_clickandpledge_maxnoofinstallments" id="woocommerce_clickandpledge_maxnoofinstallments"  placeholder='Maximum number of installments allowed' value="<?php if(esc_attr( $this->recurring_details['maxnoofinstallments'] ) == ""){ echo "Maximum number of installments allowed";}else{ echo esc_attr( $this->recurring_details['maxnoofinstallments']);}  ?>"/></label></div></td>
					<td><div id="maxnoofinstlmnts_div"><input type="text" id="woocommerce_clickandpledge_maxrecurrings_Subscription" name="woocommerce_clickandpledge_maxrecurrings_Subscription"  maxlength="3" value="<?php echo esc_attr( $this->recurring_details['maxrecurrings_Subscription'] ) ?>"/></div></td></tr>
					<script language="javascript">
					jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').keypress(function(e) {
						var a = [];
						var k = e.which;

						for (i = 48; i < 58; i++)
							a.push(i);

						if (!(a.indexOf(k)>=0))
							e.preventDefault();
					});
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').keypress(function(e) {
						var a1 = [];
						var k1 = e.which;

						for (i1 = 48; i1 < 58; i1++)
							a1.push(i1);

						if (!(a1.indexOf(k1)>=0))
							e.preventDefault();
					});
					jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').change(function(e) {
					var paymethods1 = []; var paymethods_titles1 =[];var str1 = '';
					if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked')) {
						paymethods1.push('CreditCard');
						paymethods_titles1.push('Credit Card');
					}
					if(jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
						paymethods1.push('eCheck');
						paymethods_titles1.push('eCheck');
					}
					var defaultval1 = jQuery('#woocommerce_clickandpledge_DefaultpaymentMethod').val();
					if(jQuery('#woocommerce_clickandpledge_CustomPayment').is(':checked')) {	
					 var titles1 = jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').val();
						var titlesarr1 = titles1.split(";");
						for(var j1=0;j1 < titlesarr1.length; j1++)
						{ 
							if(titlesarr1[j1] !=""){
								paymethods1.push(titlesarr1[j1]);
								paymethods_titles1.push(titlesarr1[j1]);
							}
						}
						if(paymethods1.length > 0) {
						for(var i1 = 0; i1 < paymethods1.length; i1++) {
							if(paymethods1[i1] == defaultval1) {
							str1 += '<option value="'+paymethods1[i1]+'" selected>'+paymethods_titles1[i1]+'</option>';
							} else {
							str1 += '<option value="'+paymethods1[i1]+'">'+paymethods_titles1[i1]+'</option>';
							}
						}
					} else {
					 str = '<option selected="selected" value="">Please select</option>';
					}
					jQuery('#woocommerce_clickandpledge_DefaultpaymentMethod').html(str1);
					}
					});
					
					jQuery('#woocommerce_clickandpledge_isRecurring_recurring').click(function(e) {
					if(jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked')== false)
					{
					    jQuery("tr.trdfltpymntoptn").hide();
						 jQuery("tr.trrectyp").hide();
						 jQuery("tr.trdfltrecoptn").hide();
						 jQuery("tr.trprdcty").hide();
						 jQuery("tr.trnoofpaymnts").hide();
						 jQuery("tr.dfltnoofpaymnts").hide();
						 jQuery("tr.maxnoofinstlmnts").hide();
						
					}
					else
					{
					    jQuery("tr.trdfltpymntoptn").show();
						jQuery("tr.trrectyp").show();
						jQuery("tr.trdfltrecoptn").show();
						jQuery("tr.trprdcty").show();
						jQuery("tr.trnoofpaymnts").show();
						jQuery("tr.dfltnoofpaymnts").show();
						jQuery("tr.maxnoofinstlmnts").show();
					}
					});
					</script>				
					</tbody>
					
				</table>
				
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}
	
	
	/**
	 * Save account details table.
	 */
	public function save_recurring_details() {

		$cnprecurring = array();

		if ( isset( $_POST['woocommerce_clickandpledge_Installment'] )  || isset( $_POST['woocommerce_clickandpledge_Subscription']) || 
		    isset( $_POST['woocommerce_clickandpledge_isRecurring_recurring'] )   || isset( $_POST['woocommerce_clickandpledge_isRecurring_oto'] ) ) {

			$installment     				 =  $_POST['woocommerce_clickandpledge_Installment'];
			$subscription    				 =  $_POST['woocommerce_clickandpledge_Subscription'];
			$week           				 =  $_POST['woocommerce_clickandpledge_Week'];
			$tweeks          				 =  $_POST['woocommerce_clickandpledge_2_Weeks'] ;
			$month           				 =  $_POST['woocommerce_clickandpledge_Month'] ;
			$tmonths                         =  $_POST['woocommerce_clickandpledge_2_Months'] ;
			$quarter                         =  $_POST['woocommerce_clickandpledge_Quarter'];
			$smonths                         =  $_POST['woocommerce_clickandpledge_6_Months'];
			$year                            =  $_POST['woocommerce_clickandpledge_Year'] ;
			$indefinite                      =  $_POST['woocommerce_clickandpledge_indefinite'];
			$isRecurring_oto                 =  $_POST['woocommerce_clickandpledge_isRecurring_oto'];
			$isRecurring_recurring           =  $_POST['woocommerce_clickandpledge_isRecurring_recurring'];
			$dfltpayoptn                     =  $_POST['woocommerce_clickandpledge_dfltpayoptn'];
			$dfltrectypoptn                  =  $_POST['woocommerce_clickandpledge_dfltrectypoptn'];
			$dfltnoofpaymnts                 =  $_POST['woocommerce_clickandpledge_dfltnoofpaymnts'];
			$payoptn                         =  $_POST['woocommerce_clickandpledge_payoptn'];
			$rectype                         =  $_POST['woocommerce_clickandpledge_rectype'];
	  	    $periodicity                     =  $_POST['woocommerce_clickandpledge_periodicity'];
			$noofpayments                    =  $_POST['woocommerce_clickandpledge_noofpayments'];
			$dfltnoofpaymentslbl             =  $_POST['woocommerce_clickandpledge_dfltnoofpaymentslbl'];
			$maxnoofinstallments             =  $_POST['woocommerce_clickandpledge_maxnoofinstallments'];
			$maxrecurrings_Subscription      =  $_POST['woocommerce_clickandpledge_maxrecurrings_Subscription'];
		
			
			
				$cnprecurring = array(
					'installment'      => $installment,
					'subscription'     => $subscription,
					'week'             => $week,
					'2_weeks'          => $tweeks,
					'month'            => $month,
					'2_months'         => $tmonths,
					'quarter'          => $quarter,
					'6_months'         => $smonths,
					'year'             => $year,
					'indefinite'       => $indefinite,
					'isRecurring_oto'  => $isRecurring_oto,
					'isRecurring_recurring' => $isRecurring_recurring,	
					'dfltpayoptn'      => $dfltpayoptn,	
					'dfltrectypoptn'   => $dfltrectypoptn,	
					'dfltnoofpaymnts'  => $dfltnoofpaymnts,	
					'payoptn'          => $payoptn,	
					'rectype'          => $rectype,	
					'periodicity'      => $periodicity,	
					'noofpayments'     => $noofpayments,
					'dfltnoofpaymentslbl'         => $dfltnoofpaymentslbl,
					'maxnoofinstallments'         => $maxnoofinstallments,
					'maxrecurrings_Subscription'  => $maxrecurrings_Subscription					
				);
			
		}

		update_option( 'woocommerce_clickandpledge_recurring', $cnprecurring );

	}
	/**
	 * Save acceptedcreditcards details table.
	 */
	public function save_acceptedcreditcards_details() {

		$cnprecurring = array();

		if ( isset( $_POST['woocommerce_clickandpledge_Visa'] )  || isset( $_POST['woocommerce_clickandpledge_American_Express'] ) ||
		     isset( $_POST['woocommerce_clickandpledge_Discover'] )  || isset( $_POST['woocommerce_clickandpledge_MasterCard'] ) ||
		     isset( $_POST['woocommerce_clickandpledge_JCB'] )  ) {

						
			$Visa                         =  $_POST['woocommerce_clickandpledge_Visa'];
	  	    $American_Express             =  $_POST['woocommerce_clickandpledge_American_Express'];
			$Discover                     =  $_POST['woocommerce_clickandpledge_Discover'];
			$MasterCard                   =  $_POST['woocommerce_clickandpledge_MasterCard'];
			$JCB                          =  $_POST['woocommerce_clickandpledge_JCB'];
			
			
				$cnpcreditcards = array(
					'Visa'                 => $Visa,
					'American_Express'     => $American_Express,
					'Discover'             => $Discover,
					'MasterCard'           => $MasterCard,
					'JCB'                  => $JCB				
				);
			
		}

		update_option( 'woocommerce_clickandpledge_acceptedcreditcards', $cnpcreditcards );

	}
	    /**
		 * Admin Panel Options 
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 */
		function admin_options() {
	    	?>
	    	<h3><?php _e( 'Click & Pledge', 'woothemes' ); ?></h3>
	    	<p><?php _e( 'Click & Pledge works by adding credit card fields on the checkout and then sending the details to Click & Pledge for verification.', 'woothemes' ); ?>
			</p>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->
			
			<script>
			
			jQuery(document).ready(function(){
				
				limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);	
				limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				displaycheck();
				//recurringdisplay();
				 jQuery("#woocommerce_clickandpledge_DefaultpaymentMethod option[value='<?php echo $this->defaultpayment;?>']").prop('selected', true);
				
				function displaycheck() {
					if(!jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') && !jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
					
						jQuery('.CredicardSection').next('table').hide();
						jQuery('.CredicardSection').hide();
						jQuery('.clsacptcrds').hide();
							
						jQuery('.RecurringSection').next('table').hide();
						jQuery('.RecurringSection').hide();
					} else {
						if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked')) {
						
							jQuery('.CredicardSection').next('table').show();
							jQuery('.CredicardSection').show();
							jQuery('#woocommerce_clickandpledge_Preauthorization').closest('tr').show();
							jQuery('.clsacptcrds').show();
						} else {
							jQuery('.CredicardSection').next('table').hide();
							jQuery('.CredicardSection').hide();
							jQuery('#woocommerce_clickandpledge_Preauthorization').closest('tr').hide();
							jQuery('.clsacptcrds').hide();
						}
						
						if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') || jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
							jQuery('.RecurringSection').next('table').show();
							jQuery('.RecurringSection').show();
						}
					}	
					if(jQuery('#woocommerce_clickandpledge_isRecurring_oto').is(':checked') == true && 
					   jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked') == true)
					{
					    jQuery("tr.trdfltpymntoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltpymntoptn").hide();
					}
					if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') == true && jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') == true)
					{
					     jQuery("tr.trdfltrecoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltrecoptn").hide();
					}
					if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') == true)
					{
						
						jQuery("#indefinite_div").show();jQuery("#indefinite_openfield_div").show();jQuery("#openfild_div").show();jQuery("#fixdnumber_div").show();
					}
					else
					{
						jQuery("#indefinite_div").hide();jQuery("#indefinite_openfield_div").hide();
					}
					if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') == true)
					{
					
					    jQuery("#openfild_div").show();jQuery("#fixdnumber_div").show();
					}
				    if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == 1)
					{ 
					
						jQuery("tr.dfltnoofpaymnts").hide();jQuery("tr.maxnoofinstlmnts").hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
					}
					if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == "openfield")
					{
					
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").show();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
					}
					if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == "indefinite_openfield")
					{
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").show();
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('999');
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', true);
					}
					if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == "fixednumber")
					{
						
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
					}
					if(jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked')== false)
					{
					    jQuery("tr.trdfltpymntoptn").hide();
						 jQuery("tr.trrectyp").hide();
						 jQuery("tr.trdfltrecoptn").hide();
						 jQuery("tr.trprdcty").hide();
						 jQuery("tr.trnoofpaymnts").hide();
						 jQuery("tr.dfltnoofpaymnts").hide();
						 jQuery("tr.maxnoofinstlmnts").hide();
						
						
					}
					else
					{
					   
						jQuery("tr.trrectyp").show();
						jQuery("tr.trprdcty").show();
						jQuery("tr.trnoofpaymnts").show();
						if(jQuery('#woocommerce_clickandpledge_isRecurring_oto').is(':checked') == true && 
						   jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked') == true)
					{
					    jQuery("tr.trdfltpymntoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltpymntoptn").hide();
					}
					if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') == true && jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') == true)
					{
					    jQuery("tr.trdfltrecoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltrecoptn").hide();
					}
						if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == 1)
					    { 
							jQuery("tr.dfltnoofpaymnts").hide();
							jQuery("tr.maxnoofinstlmnts").hide();
						}
						else  if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == "fixednumber")
						{
							jQuery("tr.dfltnoofpaymnts").show();
						   
						}
						else
						{
							jQuery("tr.dfltnoofpaymnts").show();
						    jQuery("tr.maxnoofinstlmnts").show();
						}
					}
					defaultpayment();
				}
				function defaultpayment() {
					var paymethods = [];
					var paymethods_titles = [];
					var str = '';
					var defaultval = jQuery('#woocommerce_clickandpledge_DefaultpaymentMethod').val();
					if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked')) {
						paymethods.push('CreditCard');
						paymethods_titles.push('Credit Card');
					}
					if(jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
						paymethods.push('eCheck');
						paymethods_titles.push('eCheck');
					}
					
					if(jQuery('#woocommerce_clickandpledge_CustomPayment').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_ReferenceNumber_Label').closest('tr').show();
						
						var titles = jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').val();
						var titlesarr = titles.split(";");
						for(var j=0;j < titlesarr.length; j++)
						{
							if(titlesarr[j] !=""){
								paymethods.push(titlesarr[j]);
								paymethods_titles.push(titlesarr[j]);
							}
						}
					} else {
						jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_ReferenceNumber_Label').closest('tr').hide();
					}
					
					if(paymethods.length > 0) {
						for(var i = 0; i < paymethods.length; i++) {
							if(paymethods[i] == defaultval) {
							str += '<option value="'+paymethods[i]+'" selected>'+paymethods_titles[i]+'</option>';
							} else {
							str += '<option value="'+paymethods[i]+'">'+paymethods_titles[i]+'</option>';
							}
						}
					} else {
					 str = '<option selected="selected" value="">Please select</option>';
					}
					jQuery('#woocommerce_clickandpledge_DefaultpaymentMethod').html(str);
				}
				jQuery('.rectyp').click(function()
				{
				  if(jQuery('#woocommerce_clickandpledge_isRecurring_oto').is(':checked') && jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked'))
					{
					    jQuery("tr.trdfltpymntoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltpymntoptn").hide();
					}
				
				});
				jQuery('.clsrectype').click(function()
				{
				  if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') && jQuery('#woocommerce_clickandpledge_Subscription').is(':checked'))
					{
					    jQuery("tr.trdfltrecoptn").show();
					}
					else
					{
					     jQuery("tr.trdfltrecoptn").hide();
					}
				
				});
				
				jQuery(".clsnoofpaymnts").change(function(){
																			
					var noofpay = jQuery(this).val();
					if(noofpay == 1)
					{
						jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").val('');
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('');
						jQuery("tr.dfltnoofpaymnts").hide();jQuery("tr.maxnoofinstlmnts").hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
					}
					if(noofpay == "openfield")
					{
					    jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").val('');
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('');
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").show();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
				   }
					if(noofpay == "indefinite_openfield")
					{
					    jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").val('');
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('');
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").show();
						jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").val('999');
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('999');
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', true);
					}
					if(noofpay == "fixednumber")
					{
					    jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").val('');
						jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").val('');
						jQuery("tr.dfltnoofpaymnts").show();jQuery("tr.maxnoofinstlmnts").hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').attr('readonly', false);
					}
					
				});
				jQuery("#woocommerce_clickandpledge_Subscription").click(function(){
				if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') == true)
				{
				
				 jQuery("#indefinite_div").show();jQuery("#indefinite_openfield_div").show();jQuery("#openfild_div").show();jQuery("#fixdnumber_div").show();
				 
				}
				else if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') == false)
				{
				
				  jQuery("#indefinite_div").hide();jQuery("#indefinite_openfield_div").hide();
				}
				
				});
				
				jQuery( "form" ).submit(function( event ) {
				  if(jQuery('input[name=woocommerce_clickandpledge_testmode]:checked').length <= 0)
					{
						alert('Please select API Mode');
						jQuery('#woocommerce_clickandpledge_testmode').focus();
						return false;
					}
					if(jQuery('#woocommerce_clickandpledge_title').val() == '')
					{
						alert('Please enter title');
						jQuery('#woocommerce_clickandpledge_title').focus();
						return false;
					}
					
					if(jQuery('#woocommerce_clickandpledge_AccountID').val() == '')
					{
						alert('Please enter AccountID');
						jQuery('#woocommerce_clickandpledge_AccountID').focus();
						return false;
					}
					if(jQuery('#woocommerce_clickandpledge_AccountID').val().length > 10)
					{
						alert('Please enter only 10 digits');
						jQuery('#woocommerce_clickandpledge_AccountID').focus();
						return false;
					}					
					if(jQuery('#woocommerce_clickandpledge_AccountGuid').val() == '')
					{
						alert('Please enter AccountGuid');
						jQuery('#woocommerce_clickandpledge_AccountGuid').focus();
						return false;
					}
					if(jQuery('#woocommerce_clickandpledge_AccountGuid').val().length != 36)
					{
						alert('AccountGuid should be 36 characters');
						jQuery('#woocommerce_clickandpledge_AccountGuid').focus();
						return false;
					}
					
					var paymethods = 0;
					if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked'))
					{
						paymethods++;
					}
					if(jQuery('#woocommerce_clickandpledge_eCheck').is(':checked'))
					{
						paymethods++;
					}
					if(jQuery('#woocommerce_clickandpledge_CustomPayment').is(':checked'))
					{
						paymethods++;
					}
					
					if(paymethods == 0) {
						alert('Please select at least  one payment method');
						jQuery('#woocommerce_clickandpledge_CreditCard').focus();
						return false;
					}
					
					
					var cards = 0;
					if(jQuery('#woocommerce_clickandpledge_Visa').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_American_Express').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_Discover').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_MasterCard').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_JCB').is(':checked'))
					{
						cards++;
					}
					
					if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') && cards == 0) {						
						alert('Please select at least  one card');
						jQuery('#woocommerce_clickandpledge_Visa').focus();
						return false;						
					}
					if(jQuery('#woocommerce_clickandpledge_CustomPayment').is(':checked') && jQuery.trim(jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').val()) == '') {
						alert('Please enter at least one payment method name');
						jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').val('');
						jQuery('#woocommerce_clickandpledge_CustomPayment_Titles').focus();
						return false;	
					}
					var selected5 = 0;
					if(jQuery("#woocommerce_clickandpledge_isRecurring_oto").prop('checked')) selected5++;
					if(jQuery("#woocommerce_clickandpledge_isRecurring_recurring").prop('checked')) selected5++;
				
					if(selected5 == 0) {
						alert('Please select at least  one payment option');
						jQuery("#woocommerce_clickandpledge_isRecurring_oto").focus();
						return false;
					}
					
					if(jQuery('#woocommerce_clickandpledge_isRecurring_recurring').is(':checked') == true) {		
					var selected = 0;
			
					if(jQuery("#woocommerce_clickandpledge_Week").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_2_Weeks").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_Month").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_2_Months").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_Quarter").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_6_Months").prop('checked')) selected++;
					if(jQuery("#woocommerce_clickandpledge_Year").prop('checked')) selected++;
					if(selected == 0) {
						alert('Please select at least one period');
						jQuery("#woocommerce_clickandpledge_Week").focus();
						return false;
					}
					var selected2 = 0;
					if(jQuery("#woocommerce_clickandpledge_Installment").prop('checked')) selected2++;
					if(jQuery("#woocommerce_clickandpledge_Subscription").prop('checked')) selected2++;
				
					if(selected2 == 0) {
						alert('Please select at least one recurring type');
						jQuery("#woocommerce_clickandpledge_Installment").focus();
						return false;
					}
					
					if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').length<=0)
					{
					   alert("Please select at least one option for number of payments");
					   jQuery("#woocommerce_clickandpledge_indefinite").focus();
					   return false;
					}
					if(jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() != "1")
			        {
			
				if(jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() == "" && jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() == "fixednumber")
				{
				   alert("Please enter default number of payments");
				   jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").focus();
				   return false;														
				}
				if(jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() != "" && jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() <= 1)
				{
				   alert("Please enter default number of payments value grater than 1");
				   jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").focus();
				   return false;														
				}
				if(!isInteger(jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val()) && jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() != "" )
				
				{
					
				   alert("Please enter an integer value only");
				   jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").focus();
				   return false;														
				}
				if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() != "" && jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() <=1)
				{
				   alert("Please enter maximum number of installments allowed value grater than 1");
				   jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").focus();
				   return false;														
				}
				if(parseInt(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val()) < parseInt(jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val()))
				{
					alert("Maximum number of installments allowed to be greater than or equal to default number of payments");
					jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').focus();
					return false;
				}
				if(!isInteger(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val()) && jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() != "" )
				{
				   alert("Please enter an integer value only");
				   jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").focus();
				   return false;														
				}
			     		if(jQuery("#woocommerce_clickandpledge_Installment").is(':checked') && jQuery("#woocommerce_clickandpledge_Subscription").is(':checked'))
						{
						  jQuery("tr.trdfltrecoptn").show();
						}
						else{
						  jQuery("tr.trdfltrecoptn").hide();
						}
				if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') && !jQuery('#woocommerce_clickandpledge_Subscription').is(':checked'))
				{
					
					if(jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() !=""  && jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() > 998 &&
					 jQuery('tr.dfltnoofpaymnts').css('display') != 'none')
					{
						   alert("Please enter value between 2 to 998 for installment");
						   jQuery("#woocommerce_clickandpledge_dfltnoofpaymnts").focus();
						   return false;
					}
					if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() !=""  && jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() > 998 && jQuery('tr.maxnoofinstlmnts').css('display') != 'none')
					{
						   alert("Please enter value between 2 to 998 for installment");
						   jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").focus();
						   return false;
					}
				}
				else if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') && jQuery('#woocommerce_clickandpledge_Subscription').is(':checked'))
				{
					
					if(jQuery('#woocommerce_clickandpledge_dfltrectypoptn').val() == "Installment" && jQuery('#woocommerce_clickandpledge_dfltnoofpaymnts').val() > 998 && 
					   jQuery('tr.dfltnoofpaymnts').css('display') != 'none' && 
					   jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() != "indefinite_openfield")
					{
						 alert("Please enter value between 2 to 998 for installment");
						   jQuery("#gfcnp_dfltnoofpaymnts").focus();
						   return false;
					}
					if(jQuery('#woocommerce_clickandpledge_dfltrectypoptn').val() == "Installment" && 
					   jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() > 998 &&
					   jQuery('tr.gfcnp_maxnoofinstallmentsallowed').css('display') != 'none' && 
					   jQuery('input[name=woocommerce_clickandpledge_indefinite]:checked').val() != "indefinite_openfield")
					{
						  alert("Please enter value between 2 to 998 for installment");
						   jQuery("#woocommerce_clickandpledge_maxrecurrings_Subscription").focus();
						   return false;
					}
				}
				
			}
		}
					
					function isInt(n) {
						return n % 1 === 0;
					}
					 function isInteger(n) {
						return /^[0-9]+$/.test(n);
					}
				
				});
				
				function limitText(limitField, limitCount, limitNum) {
					if (limitField.val().length > limitNum) {
						limitField.val( limitField.val().substring(0, limitNum) );
					} else {
						limitCount.html (limitNum - limitField.val().length);
					}
				}
				
				
				
				///////Events Start
				
				//OrganizationInformation
				jQuery('#woocommerce_clickandpledge_OrganizationInformation').keydown(function(){
					limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
				jQuery('#woocommerce_clickandpledge_OrganizationInformation').keyup(function(){
					limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
								
				//Payment Methods
				jQuery('#woocommerce_clickandpledge_CreditCard').click(function(){
					if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked')) {
						jQuery('.CredicardSection').next('table').show();
						jQuery('.CredicardSection').show();
						jQuery('#woocommerce_clickandpledge_Preauthorization').closest('tr').show();
						jQuery('.clsacptcrds').show();
					} else {
						jQuery('.CredicardSection').next('table').hide();
						jQuery('.CredicardSection').hide();
						jQuery('#woocommerce_clickandpledge_Preauthorization').closest('tr').hide();
							jQuery('.clsacptcrds').hide();
					}
					
					if(!jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') && !jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
						jQuery('.RecurringSection').next('table').hide();
						jQuery('.RecurringSection').hide();
					} else {
						if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') || jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
							jQuery('.RecurringSection').next('table').show();
							jQuery('.RecurringSection').show();
						}
					}
					defaultpayment();
				});
				jQuery('#woocommerce_clickandpledge_eCheck').click(function(){
				
					defaultpayment();
						if(!jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') && !jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
						jQuery('.RecurringSection').next('table').hide();
						jQuery('.RecurringSection').hide();
					} else {
						if(jQuery('#woocommerce_clickandpledge_CreditCard').is(':checked') || jQuery('#woocommerce_clickandpledge_eCheck').is(':checked')) {
							jQuery('.RecurringSection').next('table').show();
							jQuery('.RecurringSection').show();
						}
					}
				});				
			
				jQuery('#woocommerce_clickandpledge_CustomPayment').click(function(){					
					defaultpayment();
				});
				//TermsCondition
				jQuery('#woocommerce_clickandpledge_TermsCondition').keydown(function(){
					limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				jQuery('#woocommerce_clickandpledge_TermsCondition').keyup(function(){
					limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				
			});
			</script>
	    	<?php
	    }
				
		/**
	     * Get the users country either from their order, or from their customer data
	     */
		function get_country_code() {
			global $woocommerce;
			
			if(isset($_GET['order_id'])) {
			
				$order = new WC_Order($_GET['order_id']);
	
				return $order->billing_country;
				
			} elseif ($woocommerce->customer->get_country()) {
				
				return $woocommerce->customer->get_country();
			
			}
			
			return NULL;
		}
	
		/**
	     * Payment form on checkout page
	     */
		function payment_fields() {			
			$user_country = $this->get_country_code();			
			if(empty($user_country)) :
				echo __('Select a country to see the payment form', 'woothemes');
				return;
			endif;		
			$available_cards = $this->available_cards;
			
			?>
			<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE/SANDBOX ENABLED', 'woothemes'); ?></p><?php endif; ?>
			<?php if ($this->description != "") { ?><p><?php echo $this->description; ?></p><?php }?>
			
			<?php 
			if(count($this->Paymentmethods) > 0) {
			
				if($this->recurring_details['isRecurring_recurring'] == 1 && WC()->cart->total > 0) { ?>
				<input type="hidden" name="hdnrecdtl" id="hdnrecdtl" value="<?php echo $this->recurring_details['indefinite']?>">
				<script type="text/javascript">
				manageshowhideRecpay();manageRecpay();
				function manageshowhideRecpay() {
				if(jQuery('.recpayoptions:checked').val() == 'One Time Only') {	
						jQuery('#dvperdcty').hide();
						jQuery('#dvrecurtyp').hide();
						jQuery('#dvnoofpymnts').hide();
					
						
					} else {	
						jQuery('#dvperdcty').show();
					    jQuery('#dvrecurtyp').show();	
						jQuery('#dvnoofpymnts').show();
					
						
						}
						manageRecpay();
					}
					
					function manageRecpay() { 
					if(jQuery('.recpayoptions:checked').val() == 'Recurring') {
					
					 if(jQuery('.cnpflow').length > 0) { 
					
					 if(jQuery('.cnpflow').val() == 'Installment' && jQuery('#hdnrecdtl').val() == 'indefinite_openfield') {
					 		
					if(jQuery('#clickandpledge_Installment').val() != '' && jQuery('#clickandpledge_Installment').val() <= 998){jQuery('#clickandpledge_Installment').val(jQuery('#clickandpledge_Installment').val());}else{jQuery('#clickandpledge_Installment').val('998');}
						
						} 
						if(jQuery('.cnpflow').val() == 'Subscription' &&  jQuery('#hdnrecdtl').val() == 'indefinite_openfield') {
						if(jQuery('#clickandpledge_Installment').val() != ''){jQuery('#clickandpledge_Installment').val(jQuery('#clickandpledge_Installment').val());}else{jQuery('#clickandpledge_Installment').val('999');}			
						} 
					}
					}
					}
			
				
				
				</script>
				<table  style="border-collapse:collapse;border: 0px solid rgba(0, 0, 0, 0.1) !important;"><tr><td colspan="2" style="border:none;outline:none;">
				
					<label for="clickandpledge_cart_type">
					<!--<input type="checkbox" name="clickandpledge_isRecurring" id="clickandpledge_isRecurring" onclick="isRecurring()">&nbsp;-->
					<strong><?php echo __($this->settings['RecurringLabel'], 'woocommerce') ?> </strong></label>
			
			
				</td></tr>
				<?php if($this->recurring_details['isRecurring_oto'] != "" ){?>
				<tr ><td style="border:none;outline:none;">
				 <label for="clickandpledge_cart_type">
				<?php echo __($this->recurring_details['payoptn'], 'woocommerce') ?><span class="required" style="color:red;">*</span> </label>
			</td><td style="border:none;outline:none;">
				<input type='radio' class='recpayoptions' name='clickandpledge_isRecurring' id='clickandpledge_isRecurring' value='One Time Only' style="margin: 0 0 0 0;" onclick='manageshowhideRecpay();' <?php if($this->recurring_details['dfltpayoptn'] == "One Time Only"){echo "checked";}?>>&nbsp;One Time Only
				&nbsp;&nbsp;&nbsp;<input type='radio' class='recpayoptions' name='clickandpledge_isRecurring' id='clickandpledge_isRecurring'  style="margin: 0 0 0 0;" value='Recurring' onclick='manageshowhideRecpay();' <?php if($this->recurring_details['dfltpayoptn'] == "Recurring"){echo "checked";}?>>&nbsp;Recurring
				
				 </td></tr>
				 <?php } else { ?>
				 <input type="hidden" name="clickandpledge_isRecurring" id="clickandpledge_isRecurring" value="Recurring" />
				 <?php }?>
				 <tr id="dvrecurtyp" ><td style="border:none;outline:none;">
				
					<label for="clickandpledge_cart_number"><?php echo __($this->recurring_details['rectype'], 'woocommerce') ?> <span class="required" style="color:red;">*</span></label>
			</td><td style="border:none;outline:none;">
					    <?php  
						  if(count($this->RecurringMethod) > 1 ){
						?>
							<select id="clickandpledge_RecurringMethod" name="clickandpledge_RecurringMethod"  class="cnpflow" onchange="manageRecpay();">
								<?php foreach ($this->RecurringMethod as $r) : ?>
											<option value="<?php echo $r ?>" <?php selected( $this->recurring_details['dfltrectypoptn'],$r); ?>><?php echo $r; ?></options>
								<?php endforeach; ?>			
							</select>
						<?php
					}
						
						else
						{
						  if(isset($this->RecurringMethod["Installment"]) && $this->RecurringMethod["Installment"] !== ""){
						     echo $this->RecurringMethod["Installment"];
							 echo "<input type='hidden' name='clickandpledge_RecurringMethod' id='clickandpledge_RecurringMethod' value='".$this->RecurringMethod["Installment"]."'>";
						   }
						    if(isset($this->RecurringMethod["Subscription"]) && $this->RecurringMethod["Subscription"] !== ""){
						  	 echo $this->RecurringMethod["Subscription"];
							 echo "<input type='hidden' name='clickandpledge_RecurringMethod' id='clickandpledge_RecurringMethod' value='".$this->RecurringMethod["Subscription"]."'>";
						   }
					}
						?>
			</td></tr><tr id="dvperdcty" ><td style="border:none;outline:none;">
								
					<?php echo __($this->recurring_details['periodicity'], 'woocommerce');?>
					 <span class="required" style="color:red;">*</span></td><td style="border:none;outline:none;"><?php  
						 if(count($this->Periodicity) > 1 ){
						?>
						<select id="clickandpledge_Periodicity" name="clickandpledge_Periodicity" class="cnpflow">
					    
						<?php foreach ($this->Periodicity as $p) : ?>
									<option value="<?php echo $p ?>"><?php echo $p; ?></options>
						<?php endforeach; ?>
					</select>
					<?php
					}
						
						else
						{
							$this->pselectedval ="";
						   if(isset($this->Periodicity["Week"]) && $this->Periodicity["Week"] !== ""){
						     echo $this->Periodicity["Week"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["Week"]."'>";
						   }
						   if(isset($this->Periodicity["2 Weeks"]) && $this->Periodicity["2 Weeks"] !== ""){
						     echo $this->Periodicity["2 Weeks"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["2 Weeks"]."'>";
						   }
						   if(isset($this->Periodicity["Month"]) && $this->Periodicity["Month"] !== ""){
						     echo $this->Periodicity["Month"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["Month"]."'>";
						   }
						   if(isset($this->Periodicity["2 Months"]) && $this->Periodicity["2 Months"] !== ""){
						     echo $Periodicity['2 Months'];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["2 Months"]."'>";
						   }
						   if(isset($this->Periodicity["Quarter"]) && $this->Periodicity["Quarter"] !== ""){
						     echo $this->Periodicity["Quarter"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["Quarter"]."'>";
						   }
						   if(isset($this->Periodicity["6 Months"]) && $this->Periodicity["6 Months"] !== ""){
						     echo $this->Periodicity["6 Months"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["6 Months"]."'>";
						   }
						   if(isset($this->Periodicity["Year"]) && $this->Periodicity["Year"] !== ""){
						
						     echo $this->Periodicity["Year"];
							 echo "<input type='hidden' name='clickandpledge_Periodicity' id='clickandpledge_Periodicity' value='".$this->Periodicity["Year"]."'>";
						   }
						   
						    
						}
						?>
				</td></tr><tr id="dvnoofpymnts"> <td style="border:none;outline:none;">
				<?php echo __($this->recurring_details['noofpayments'], 'woocommerce') ?> <span class="required" style="color:red;">*</span></td><td style="border:none;outline:none;"><p>
				<?php if($this->recurring_details['indefinite'] == 'fixednumber'){?>	
				<label for="clickandpledge_cart_number"> <?php echo $this->recurring_details['dfltnoofpaymnts'] ;?> </label>
				<input type="hidden" name="clickandpledge_indefinite" id="clickandpledge_indefinite" value="no" />
				<input type="hidden" name="clickandpledge_Installment" id="clickandpledge_Installment" value="<?php echo $this->recurring_details['dfltnoofpaymnts'];?>" />
				
				<?php }?>
				<?php if($this->recurring_details['indefinite'] == '1'){?>	
				<label for="clickandpledge_cart_number"> Indefinite Recurring Only</label>
				<input type="hidden" name="clickandpledge_indefinite" id="clickandpledge_indefinite" value="on" />
				<input type="hidden" name="clickandpledge_Installment" id="clickandpledge_Installment" value="999" />
				
				<?php }?>
				<?php if($this->recurring_details['indefinite'] == 'openfield'){?>	
				
				<input type="text" class="input-text " id="clickandpledge_Installment" name="clickandpledge_Installment" maxlength="3" style="margin-right:2px; width:150px;" value="<?php echo $this->recurring_details['dfltnoofpaymnts'];?>" />
				<input type="hidden" name="clickandpledge_indefinite" id="clickandpledge_indefinite" value="no" />
			
				<?php }?>
				<?php if($this->recurring_details['indefinite'] == 'indefinite_openfield'){?>	
				<input type="text" class="input-text " id="clickandpledge_Installment" name="clickandpledge_Installment" maxlength="3" style="width:150px; margin-right:2px;" value="<?php echo $this->recurring_details['dfltnoofpaymnts'];?>" />
				<input type="hidden" name="clickandpledge_indefinite" id="clickandpledge_indefinite" value="no" />
				<?php }?>
					<script>
					jQuery('#clickandpledge_Installment').keypress(function(e) {
						var a = [];
						var k = e.which;

						for (i = 48; i < 58; i++)
							a.push(i);

						if (!(a.indexOf(k)>=0))
							e.preventDefault();
					});
					</script>
					
				</p>
						
				<?php }
				
				?></td></tr></table>
				<?php
				echo '<span style="width:980px" id="payment_methods"> <strong>Payment Methods</strong> <br><br> ';
			     if(WC()->cart->total == 0 ){
					if($this->defaultpayment != 'CreditCard' && count($this->CustomPayments) > 0) {
					   $key = array_search($this->defaultpayment, $this->CustomPayments);
						$this->defaultpayment = $this->CustomPayments[$key];
					}
					else
					{
					 $this->defaultpayment = 'CreditCard';
					}
					
				} 			
				foreach($this->Paymentmethods as $pkey => $pval) {
					if(WC()->cart->total == 0  && !in_array($pkey, array('eCheck'))){
						if($pkey == $this->defaultpayment) {
							echo '<input type="radio" id="cnp_payment_method_selection_'.$pkey.'" name="cnp_payment_method_selection" class="cnp_payment_method_selection" onclick="displaysection(this.value);" style="margin: 0 0 0 0;" value="'.$pkey.'" checked>&nbsp<b>'.$pval.'</b>&nbsp;&nbsp;&nbsp;';
						} else {
							echo '<input type="radio" id="cnp_payment_method_selection_'.$pkey.'" name="cnp_payment_method_selection" class="cnp_payment_method_selection" onclick="displaysection(this.value);" style="margin: 0 0 0 0;" value="'.$pkey.'">&nbsp;<b>'.$pval.'</b>&nbsp;&nbsp;&nbsp;';
						}
					} else if(WC()->cart->total > 0){
						if($pkey == $this->defaultpayment) {
							echo '<input type="radio" id="cnp_payment_method_selection_'.$pkey.'" name="cnp_payment_method_selection" class="cnp_payment_method_selection" onclick="displaysection(this.value);" style="margin: 0 0 0 0;" value="'.$pkey.'" checked>&nbsp<b>'.$pval.'</b>&nbsp;&nbsp;&nbsp;';
						} else {
							echo '<input type="radio" id="cnp_payment_method_selection_'.$pkey.'" name="cnp_payment_method_selection" class="cnp_payment_method_selection" onclick="displaysection(this.value);" style="margin: 0 0 0 0;" value="'.$pkey.'">&nbsp;<b>'.$pval.'</b>&nbsp;&nbsp;&nbsp;';
						}
					}
				}
				echo '</span>';
			}
			?>
			<style>
			.cnpflow {
			-webkit-appearance:menu; 
			-webkit-border-radius: 2px; 
			-webkit-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1); 
			 -webkit-padding-end: 20px; 
			-webkit-padding-start: 2px; 
			 -webkit-user-select: none; 
			 -webkit-linear-gradient(#FAFAFA, #F4F4F4 40%, #E5E5E5); 
			 background-position: 97% center; 
			 background-repeat: no-repeat; 
			 border: 1px solid #AAA; 
			 color: #555; 
			 font-size: inherit; 
			 overflow: hidden; 
			 padding: 5px 10px;
			 text-overflow: ellipsis; 
			 white-space: nowrap; 
			 width: 150px;
	       } 
		   .cnpccflow {
			-webkit-appearance:button; 
			-webkit-border-radius: 2px; 
			-webkit-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1); 
			 -webkit-padding-end: 20px; 
			-webkit-padding-start: 2px; 
			 -webkit-user-select: none; 
			 -webkit-linear-gradient(#FAFAFA, #F4F4F4 40%, #E5E5E5); 
			 background-position: 97% center; 
			 background-repeat: no-repeat; 
			 border: 1px solid #AAA; 
			 color: #555; 
			 font-size: inherit; 
			 overflow: hidden; 
			 padding: 5px 10px;
			 text-overflow: ellipsis; 
			 white-space: nowrap; 
			 width: 150px;
	       } 
			</style>
			<script>
				function displaysection(sec) {
					if(sec == 'CreditCard') {
						jQuery('#cnp_CreditCard_div').show();					
						jQuery('#cnp_eCheck_div').hide();
						jQuery('#cnp_Custom_div').hide();
						
					} else if(sec == 'eCheck') {
						jQuery('#cnp_CreditCard_div').hide();					
						jQuery('#cnp_eCheck_div').show();
						jQuery('#cnp_Custom_div').hide();
						
					} else {
						jQuery('#cnp_CreditCard_div').hide();					
						jQuery('#cnp_eCheck_div').hide();
						jQuery('#cnp_Custom_div').show();
						
					}
				}
			</script>
			
			<div style="display:<?php if($this->defaultpayment == 'CreditCard') echo 'block'; else echo 'none';?>;" id="cnp_CreditCard_div">
			<p class="" style="margin:0 0 10px">&nbsp;</p>
				
	
				<?php
			
			if (count($available_cards) > 0) { ?>
				<p><?php 
				if(in_array('Visa', $available_cards))
					echo "<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/visa.jpg' title='Visa' alt='Visa'/>";
				if(in_array('American Express', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/amex.jpg' title='Visa' alt='Visa'/>";
				if(in_array('Discover', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/discover.jpg' title='American Express' alt='American Express'/>";
				if(in_array('MasterCard', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/mastercard.gif' title='MasterCard' alt='MasterCard'/>";
				if(in_array('JCB', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/JCB.jpg' title='JCB' alt='JCB'/>";
				?></p>
			<?php } ?>
				<p class="">
					<label for="clickandpledge_cart_number"><?php echo __("Name on Card", 'woocommerce') ?> <span class="required" style="color:red;">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_name_on_card" placeholder="Name on Card" maxlength="50"/>
				</p>
				<div class="clear"></div>
				
				<p class="form-row form-row-first">
					<label for="clickandpledge_cart_number"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_card_number" placeholder="Credit Card number" style="color:#141412; font-weight:normal;" maxlength="17"/>
				</p>
				<p class="form-row form-row-last">
					<label for="clickandpledge_card_csc"><?php _e("Card Verification (CVV)", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="clickandpledge_card_csc" name="clickandpledge_card_csc" maxlength="4" style="width:59px" placeholder="cvv"/>					
					<span class="help clickandpledge_card_csc_description"></span>
				</p>
				
				<div class="clear"></div>
				
				<p class="form-row form-row-first">
					<label for="cc-expire-month"><?php echo __("Expiration Date", 'woocommerce') ?> <span class="required">*</span></label>
					<select name="clickandpledge_card_expiration_month" id="cc-expire-month" class="cnpccflow">
						<option value=""><?php _e('Month', 'woocommerce') ?></option>
						<?php
							$months = array();
							for ($i = 1; $i <= 12; $i++) {
							    $timestamp = mktime(0, 0, 0, $i, 1);
							    $months[date('m', $timestamp)] = date('F', $timestamp);
							}
							foreach ($months as $num => $name) {
					            printf('<option value="%s">%s</option>', $num, $name);
					        }
					        
						?>
					</select>
					<select name="clickandpledge_card_expiration_year" id="cc-expire-year" class="cnpccflow">
						<option value=""><?php _e('Year', 'woocommerce') ?></option>
						<?php
							$years = array();
							for ($i = date('Y'); $i <= date('Y') + 15; $i++) {
							    printf('<option value="%u">%u</option>', $i, $i);
							}
						?>
					</select>
				</p>
				
				<div class="clear"></div>			
			</div> <!-- Credit Card Section End-->
			<div style="display:<?php if($this->defaultpayment == 'eCheck') echo 'block'; else echo 'none';?>;" id="cnp_eCheck_div">
				<p class="" style="margin:0 0 10px">&nbsp;</p>
				
				<?php
				echo "<p><img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/eCheck.png' title='eCheck' alt='eCheck'/></p>";
			 ?>
				
				<table  style="border-collapse:collapse;border: 0px solid rgba(0, 0, 0, 0.1) !important;">
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_AccountType"><?php echo __("Account Type", 'woocommerce') ?><span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
					</td><td style="border:none;outline:none;">					
					<select class="cnpflow" name="clickandpledge_echeck_AccountType" id="clickandpledge_echeck_AccountType">						
						<option value="SavingsAccount">SavingsAccount</option>
						<option value="CheckingAccount">CheckingAccount</option>
					</select>
				</td></tr>
				<div class="clear"></div>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_NameOnAccount"><?php echo __("Name On Account", 'woocommerce') ?><span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<input type="text" class="input-text required" name="clickandpledge_echeck_NameOnAccount" id="clickandpledge_echeck_NameOnAccount" placeholder="Name On Account" maxlength="17"/>
				</td></tr>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_IdType"><?php echo __("Type of ID", 'woocommerce') ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<select class="cnpflow" name="clickandpledge_echeck_IdType" id="clickandpledge_echeck_IdType">
						<option value="Driver">Driver</option>
						<option value="Military">Military</option>
						<option value="State">State</option>
					</select>
				</td></tr>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_CheckType"><?php echo __("Check Type", 'woocommerce') ?> <span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<select class="cnpflow" name="clickandpledge_echeck_CheckType" id="clickandpledge_echeck_CheckType">
						<option value="Company">Company</option>
						<option value="Personal">Personal</option>
					</select>
				</td></tr>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_CheckNumber"><?php echo __("Check Number", 'woocommerce') ?> <span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<input type="text" class="input-text required" id="clickandpledge_echeck_CheckNumber" name="clickandpledge_echeck_CheckNumber" placeholder="Check Number" maxlength="17"/>
				</p>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_RoutingNumber"><?php echo __("Routing Number", 'woocommerce') ?> <span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<input type="text" class="input-text required" id="clickandpledge_echeck_RoutingNumber" name="clickandpledge_echeck_RoutingNumber" placeholder="Routing Number" maxlength="17"/>
				</td></tr>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_AccountNumber"><?php echo __("Account Number", 'woocommerce') ?> <span class="required" style="color:red;">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label></td><td style="border:none;outline:none;">	
					<input type="text" class="input-text required" id="clickandpledge_echeck_AccountNumber" name="clickandpledge_echeck_AccountNumber" placeholder="Account Number" maxlength="17"/>
				</p>
				<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_AccountNumber"><?php echo __("Re-Type Account Number", 'woocommerce') ?> <span class="required" style="color:red;">*</span></label></td><td style="border:none;outline:none;">	
					<input type="text" class="input-text required" id="clickandpledge_echeck_retypeAccountNumber" name="clickandpledge_echeck_retypeAccountNumber" placeholder="Re-Type Account Number" maxlength="17"/>
				</td></tr>
				</table>
		
			</div>
			<div style="display:<?php if(($this->defaultpayment != 'CreditCard') && ($this->defaultpayment != 'eCheck') ) echo 'block'; else echo 'none';?>;" id="cnp_Custom_div">
			<p class="" style="margin:0 0 10px">&nbsp;</p>
			<?php if($this->ReferenceNumber_Label != "")
			{
			?><table style="border-collapse:collapse;border: 0px solid rgba(0, 0, 0, 0.1) !important;">
			<tr> <td style="border:none;outline:none;">
					<label for="clickandpledge_echeck_AccountNumber"><?php echo __($this->ReferenceNumber_Label, 'woocommerce') ?> </label></td><td style="border:none;outline:none;">	
					<input type="text"  id="clickandpledge_cp_ReferenceNumber" name="clickandpledge_cp_ReferenceNumber" placeholder="<?php echo $this->ReferenceNumber_Label;?>" maxlength="50"/>
				</td></tr>
				</table>
			<?php
			}
			
			?>
			</div>
			
			<?php 
		}
		
		/**
	     * Process the payment
	     */
		function process_payment($order_id) {
		
		global $woocommerce;
	
			$order = new WC_Order( $order_id );
	// Validate plugin settings
			
			if (!$this->validate_settings()) :
				$cancelNote = __('Order was cancelled due to invalid settings (check your API credentials and make sure your currency is supported).', 'woothemes');
				$order->add_order_note( $cancelNote );
				wc_add_notice( __( 'Payment was rejected due to configuration error.', 'woocommerce' ), 'error' );
				return false;
			endif;
	
			// Send request to clickandpledge
			try {
				$url = $this->liveurl;
				if ($this->testmode == 'yes') :
					$url = $this->testurl;
				endif;
	
				$request = new clickandpledge_request($url);
				
				$posted_settings = array();
				$posted_settings['AccountID'] = $this->AccountID;
				$posted_settings['AccountGuid'] = $this->AccountGuid;
				$posted_settings['cnp_email_customer'] = $this->settings['cnp_email_customer'];
				$posted_settings['Total'] = $order->order_total;
				$posted_settings['OrderMode'] = $this->get_option( 'testmode' );//$this->testmode
				$posted_settings['Preauthorization'] = isset($this->settings['Preauthorization']) ? $this->settings['Preauthorization'] : 'no';		
				$posted_settings['OrganizationInformation'] = $this->settings['OrganizationInformation'];				
				$posted_settings['TermsCondition'] = $this->settings['TermsCondition'];			
				//print_r($order);
				$response = $request->send($posted_settings, $_POST, $order);
			
			} catch(Exception $e) {
				wc_add_notice( __( 'There was a connection error', 'woocommerce' ) . ': "' . $e->getMessage() . '"', 'error' );
				return;
			}
	
			if ($response['status'] == 'Success') :
				$order->add_order_note( __('Click & Pledge payment completed', 'woothemes') . ' (Transaction ID: ' . $response['TransactionNumber'] . ')' );
				$order->payment_complete();
				//$order->reduce_order_stock();
				$woocommerce->cart->empty_cart();
					
				// Return thank you page redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $this->get_return_url( $order )
				);
			else :
				$cancelNote = __('Click & Pledge payment failed', 'woothemes') . ' (Transaction ID: ' . $response['TransactionNumber'] . '). ' . __('Payment was rejected due to an error', 'woothemes') . ': "' . $response['error'] . '". ';
	
				$order->add_order_note( $cancelNote );
				wc_add_notice( __( 'Payment error', 'woocommerce' ) . ': ' . $response['error'] . '('.$response['ResultCode'].')', 'error' );
			endif;

		}
	
	function cc_check($number) {

	  // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
	  $number=preg_replace('/\D/', '', $number);

	  // Set the string length and parity
	  $number_length=strlen($number);
	  $parity=$number_length % 2;

	  // Loop through each digit and do the maths
	  $total=0;
	  for ($i=0; $i<$number_length; $i++) {
		$digit=$number[$i];
		// Multiply alternate digits by two
		if ($i % 2 == $parity) {
		  $digit*=2;
		  // If the sum is two digits, add them together (in effect)
		  if ($digit > 9) {
			$digit-=9;
		  }
		}
		// Total up the digits
		$total+=$digit;
	  }

	  // If the total mod 10 equals 0, the number is valid
	  return ($total % 10 == 0) ? TRUE : FALSE;

	}
	
	function CreditCardCompany($ccNum)
	 {
			/*
				* mastercard: Must have a prefix of 51 to 55, and must be 16 digits in length.
				* Visa: Must have a prefix of 4, and must be either 13 or 16 digits in length.
				* American Express: Must have a prefix of 34 or 37, and must be 15 digits in length.
				* Diners Club: Must have a prefix of 300 to 305, 36, or 38, and must be 14 digits in length.
				* Discover: Must have a prefix of 6011, and must be 16 digits in length.
				* JCB: Must have a prefix of 3, 1800, or 2131, and must be either 15 or 16 digits in length.
			*/
	 
			if (preg_match("/^5[1-5][0-9]{14}$/", $ccNum))
					return "MasterCard";
	 
			if (preg_match("/^4[0-9]{12}([0-9]{3})?$/", $ccNum))
					return "Visa";
	 
			if (preg_match("/^3[47][0-9]{13}$/", $ccNum))
					return "American Express";
	 
			if (preg_match("/^3(0[0-5]|[68][0-9])[0-9]{11}$/", $ccNum))
					return "Diners Club";
	 
			if (preg_match("/^6011[0-9]{12}$/", $ccNum))
					return "Discover";
	 
			if (preg_match("/^(3[0-9]{4}|2131|1800)[0-9]{11}$/", $ccNum))
					return "JCB";
	 }
	/**
	     * Validate the payment form
	     */
		function validate_fields() {
			global $woocommerce;
									
			
			$name_on_card 	    = isset($_POST['clickandpledge_name_on_card']) ? $_POST['clickandpledge_name_on_card'] : '';
			$billing_country 	= isset($_POST['billing_country']) ? $_POST['billing_country'] : '';
			$card_type 			= isset($_POST['clickandpledge_card_type']) ? $_POST['clickandpledge_card_type'] : '';
			$card_number 		= isset($_POST['clickandpledge_card_number']) ? $_POST['clickandpledge_card_number'] : '';
			$card_csc 			= isset($_POST['clickandpledge_card_csc']) ? $_POST['clickandpledge_card_csc'] : '';
			$card_exp_month		= isset($_POST['clickandpledge_card_expiration_month']) ? $_POST['clickandpledge_card_expiration_month'] : '';
			$card_exp_year 		= isset($_POST['clickandpledge_card_expiration_year']) ? $_POST['clickandpledge_card_expiration_year'] : '';
			$isRecurring        = isset($_POST['clickandpledge_isRecurring']) ? $_POST['clickandpledge_isRecurring'] : '';
			
			$cnp_payment_method_selection = isset($_POST['cnp_payment_method_selection']) ? $_POST['cnp_payment_method_selection'] : 'CreditCard';
			$customerrors = array();
			
				if(isset($_POST['clickandpledge_isRecurring']) && $_POST['clickandpledge_isRecurring'] == 'Recurring') { 
					if(empty($_POST['clickandpledge_Periodicity'])) {
							array_push($customerrors, 'Please select Periodicity');
						}
					if($_POST['clickandpledge_RecurringMethod'] == 'Installment' && $_POST['clickandpledge_indefinite'] == 'on')
						{
						   array_push($customerrors, 'Recurring type Installment not allow indefinite number of payments');
						}			
					if($_POST['clickandpledge_indefinite'] =='no') {
						if(empty($_POST['clickandpledge_Installment'])) {
							if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') { 
								if(!empty($this->recurring_details['maxrecurrings_Subscription']))
								{
									array_push($customerrors, 'Please enter a periodicity between 2-'.$this->recurring_details['maxrecurrings_Subscription']);									
								} else {
									array_push($customerrors, 'Please enter number of paymenys between 2-999');
								}
							} else {
								if(!empty($this->recurring_details['maxrecurrings_Subscription']))
								{
									array_push($customerrors, 'Please enter a periodicity between 2-'.$this->recurring_details['maxrecurrings_Subscription']);
								} else {
								array_push($customerrors, 'Please enter number of payments between 2-998');
								}
							}							
						}
						if(!ctype_digit($_POST['clickandpledge_Installment'])) {
							array_push($customerrors, 'Please enter Numbers only in instalments');
						}
						if($_POST['clickandpledge_Installment'] == 1) {
							if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') {
								array_push($customerrors, 'Instalments should be greater than 2');
							} else {
								array_push($customerrors, 'Instalments should be greater than 2');
							}
						}
						if(strlen($_POST['clickandpledge_Installment']) > 3) {
							if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') {
								array_push($customerrors, 'Please enter number of paymenys between 2-999');
							} else {
								array_push($customerrors, 'Please enter number of paymenys between 2-998');
							}
						}
						
						if($_POST['clickandpledge_RecurringMethod'] == 'Subscription')
						{						
						
							if(!empty($this->recurring_details['maxrecurrings_Subscription']) && $_POST['clickandpledge_Installment'] > $this->recurring_details['maxrecurrings_Subscription']  )
							{
								array_push($customerrors, 'Please enter number of paymenys between 2-'.$this->recurring_details['maxrecurrings_Subscription'].' only');
							}
						}
						
						if($_POST['clickandpledge_RecurringMethod'] == 'Installment')
						{
							if($_POST['clickandpledge_Installment'] == 999  )
							{
								array_push($customerrors, 'Please enter number of paymenys between 2-998');
							}
							
							if(!empty($this->recurring_details['maxrecurrings_Subscription']) && $_POST['clickandpledge_Installment'] > $this->recurring_details['maxrecurrings_Subscription']  )
							{
								array_push($customerrors, 'Please enter number of paymenys between 2-'.$this->recurring_details['maxrecurrings_Subscription'].' only');
							}
						}
					} 
					
				}
			if($cnp_payment_method_selection == 'CreditCard') { //echo $_POST['clickandpledge_isRecurring'];
			
				
				// Name on card
				if(empty($name_on_card)) {
					array_push($customerrors, 'Please enter Name on Card');
				}			
				if (!preg_match("/^([a-zA-Z0-9\.\,\#\-\ \']){2,50}$/", $name_on_card)) {
					array_push($customerrors, 'Please enter the only Alphanumeric and space for Name on Card');
				}
				//Card Number
				if(empty($card_number)) {
					array_push($customerrors, 'Please enter Credit Card Number');
				}
				if(strlen($card_number) < 13) {
					array_push($customerrors, 'Invalid Credit Card Number');
				}
				if(strlen($card_number) > 19) {
					array_push($customerrors, 'Invalid Credit Card Number');
				}
				if(!$this->cc_check($card_number)) {
					wc_add_notice( __( 'Invalid Credit Card Number', 'woocommerce' ), 'error' );
					return false;
				}
				
				//CVV
				if(empty($card_csc)) {
					array_push($customerrors, 'Please enter CVV');
				}			
				else if(!ctype_digit($card_csc)) {
					array_push($customerrors, 'Please enter Numbers only in Card Verification(CVV)');
				}	
				else if(( strlen($card_csc) < 3 )) {
					array_push($customerrors, 'Please enter a number at least 3 or 4 digits in card verification (CVV)');
				}
				
				//Credit Card Validation					
				$selected_card = $this->CreditCardCompany($card_number);
				if(!in_array($selected_card, $this->available_cards))
				{
					array_push($customerrors, 'We are not accepting <b>'.$selected_card.'</b> type cards');
				}
							
				// Check card expiration data
				if(!ctype_digit($card_exp_month) || !ctype_digit($card_exp_year) ||
					 $card_exp_month > 12 ||
					 $card_exp_month < 1 ||
					 $card_exp_year < date('Y') ||
					 $card_exp_year > date('Y') + 20
				) {
					array_push($customerrors, 'Card Expiration Date is invalid');
				}
			} else if($cnp_payment_method_selection == 'eCheck') {
				$clickandpledge_echeck_AccountType 	= isset($_POST['clickandpledge_echeck_AccountType']) ? $_POST['clickandpledge_echeck_AccountType'] : '';
				$clickandpledge_echeck_NameOnAccount 	= isset($_POST['clickandpledge_echeck_NameOnAccount']) ? $_POST['clickandpledge_echeck_NameOnAccount'] : '';
				$clickandpledge_echeck_IdType 	= isset($_POST['clickandpledge_echeck_IdType']) ? $_POST['clickandpledge_echeck_IdType'] : '';
				$clickandpledge_echeck_CheckType 	= isset($_POST['clickandpledge_echeck_CheckType']) ? $_POST['clickandpledge_echeck_CheckType'] : '';
				$clickandpledge_echeck_CheckNumber 	= isset($_POST['clickandpledge_echeck_CheckNumber']) ? $_POST['clickandpledge_echeck_CheckNumber'] : '';
				$clickandpledge_echeck_RoutingNumber 	= isset($_POST['clickandpledge_echeck_RoutingNumber']) ? $_POST['clickandpledge_echeck_RoutingNumber'] : '';
				$clickandpledge_echeck_AccountNumber 	= isset($_POST['clickandpledge_echeck_AccountNumber']) ? $_POST['clickandpledge_echeck_AccountNumber'] : '';
				$clickandpledge_echeck_retypeAccountNumber 	= isset($_POST['clickandpledge_echeck_retypeAccountNumber']) ? $_POST['clickandpledge_echeck_retypeAccountNumber'] : '';
				if(empty($clickandpledge_echeck_AccountType)) {
					array_push($customerrors, 'Please select Account Type');
				}
				
				$clickandpledge_echeck_NameOnAccount_regexp = "/^([a-zA-Z0-9 ]){0,100}$/";
				if(empty($clickandpledge_echeck_NameOnAccount)) {
					array_push($customerrors, 'Please enter Name On Account');
				}				
				else if(!preg_match($clickandpledge_echeck_NameOnAccount_regexp, $clickandpledge_echeck_NameOnAccount)) {
					array_push($customerrors, 'Invalid Name On Account.');
				}
				
				if(empty($clickandpledge_echeck_IdType)) {
					array_push($customerrors, 'Please select Type of ID');
				}
				if(empty($clickandpledge_echeck_CheckType)) {
					array_push($customerrors, 'Please select Check Type');
				}
				
				$clickandpledge_echeck_CheckNumber_regexp = "/^([a-zA-Z0-9]){1,10}$/";
				if(empty($clickandpledge_echeck_CheckNumber)) {
					array_push($customerrors, 'Please enter Check Number');
				}				
				else if(!preg_match($clickandpledge_echeck_CheckNumber_regexp, $clickandpledge_echeck_CheckNumber)) {
					array_push($customerrors, 'Invalid Check Number');
				}	
				
				$clickandpledge_echeck_RoutingNumber_regexp = "/^([a-zA-Z0-9]){9}$/";
				if(empty($clickandpledge_echeck_RoutingNumber)) {
					array_push($customerrors, 'Please enter Routing Number');
				}				
				else if(!preg_match($clickandpledge_echeck_RoutingNumber_regexp, $clickandpledge_echeck_RoutingNumber)) {
					array_push($customerrors, 'Invalid Routing Number');
				}
				
				$clickandpledge_echeck_AccountNumber_regexp = "/^([a-zA-Z0-9]){1,17}$/";
				if(empty($clickandpledge_echeck_AccountNumber)) {
					array_push($customerrors, 'Please enter Account Number');
				}				
				else if(!preg_match($clickandpledge_echeck_AccountNumber_regexp, $clickandpledge_echeck_AccountNumber)) {
					array_push($customerrors, 'Invalid Account Number');
				}
				
				if(empty($clickandpledge_echeck_retypeAccountNumber)) {
					array_push($customerrors, 'Please enter Account Number Again');
				}
				else if($clickandpledge_echeck_AccountNumber != $clickandpledge_echeck_retypeAccountNumber) {
					array_push($customerrors, 'Please enter same Account Number Again');
				}								
			}
			else if($cnp_payment_method_selection != 'CreditCard' && $cnp_payment_method_selection != 'eCheck')  { 
			    if($_POST['clickandpledge_isRecurring'] == 'Recurring') {
				  array_push($customerrors, 'Sorry but recurring payments are not supported with this payment method');
				}
			}
			if(count($customerrors) > 0) {
				foreach($customerrors as $err) {
					wc_add_notice( __( $err, 'woocommerce' ), 'error' );
				}
				return false;
			} else {
				return true;
			}
			
		}
		
		/**
	     * Validate plugin settings
	     */
		function validate_settings() {
			$currency = get_option('woocommerce_currency');
	
			if (!in_array($currency, array('USD', 'EUR', 'CAD', 'GBP'))) {
				return false;
			}
	
			if (!$this->AccountID || !$this->AccountGuid) {
				return false;
			}
	
			return true;
		}
		
		/**
	     * Get user's IP address
	     */
		function get_user_ip() {			
			 $ipaddress = '';
			 if (isset($_SERVER['HTTP_CLIENT_IP']))
				 $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
			 else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
				 $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			 else if(isset($_SERVER['HTTP_X_FORWARDED']))
				 $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			 else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
				 $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			 else if(isset($_SERVER['HTTP_FORWARDED']))
				 $ipaddress = $_SERVER['HTTP_FORWARDED'];
			 else
				 $ipaddress = $_SERVER['REMOTE_ADDR'];
			$parts = explode(',', $ipaddress);
			if(count($parts) > 1) $ipaddress = $parts[0];
			 return $ipaddress; 
		}

	} // end woocommerce_clickandpledge
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function add_clickandpledge_gateway($methods) {
		$methods[] = 'WC_Gateway_ClickandPledge';
		return $methods;
	}	
	add_filter('woocommerce_payment_gateways', 'add_clickandpledge_gateway' );
} 
