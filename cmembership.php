<?php
/*Plugin Name: Cmembership
Plugin URI: http://www.devxtech.com
Author: Oluwaseun Paul
Author URI: http://www.devxtech.com
Description: A Custom Wordpress membership plugin with voguepay payment provider
Version: 0.1.0
Text Domain: cmembership
License: MIT*/

class CmemberShip {

  private static $instance = null;

  public function get_instance(){
    if( self::$instance == null ){
      self::$instance = new self;
    }

    return self::$instance;
  }

   public function __construct(){
    add_shortcode( 'cmember-register-page', array( $this, 'wpd_register_shortcode_handler' ) );
    add_shortcode('cmember-login', array($this, 'wpd_cmember_login_shortcode_handler'));
    add_shortcode('cmember-account', array($this, 'member_account_shortcode_handler'));
    add_shortcode('cmembership-plans', array($this, 'wpd_cmembership_plans_shortcode_handler'));
    add_shortcode( 'member_sub_upgrade', array($this, 'member_sub_upgrade_handler') );
    add_shortcode( 'voguepay_payment', array($this, 'handle_voguepay_payment') );
    add_shortcode( 'voguepay_reg_payment', array($this, 'voguepay_payment_reg_pay') );
    add_action('wp_enqueue_scripts',array($this,'wpd_loadd_plugin_assets' ) );
    add_action('login_form_register', array($this, 'wpd_do_register_user' ));
    add_action('login_form_login', array($this, 'wpd_redirect_get_login'));
    add_filter('authenticate', array($this, 'wpd_maybe_redirect'), 105, 3);
    add_action('login_redirect', array($this, 'wpd_login_redirect_user'),10, 3 );
    add_action( 'login_form', array($this, 'custom_admin_messages') );
    add_action('add_meta_boxes', array($this, 'membership_levels_metabox'));
    add_action('save_post', array($this, 'save_membership_post_access'), 10,3 );
    add_action( 'the_post', array($this, 'check_post_access_level') );



  }
  public function wpd_loadd_plugin_assets(){
    //load the boostrap css styles file
    wp_enqueue_style('cmber_bts', plugin_dir_url(__FILE__).'assets/vendor/bootstrap/css/bootstrap.min.css',array(),'0.1.0', 'all');
    //enqueue plugin specific css file
    wp_enqueue_style('cmember_css', plugin_dir_url(__FILE__).'assets/css/plugin.css',array(),'0.1.0', 'all');
    //remove shipped jquery from wordpress
    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', plugin_dir_url(__FILE__).'assets/vendor/jquery/jquery.min.js', array(), '0.1.0', false);

    //register bootstrap minified js file
    wp_enqueue_script('bootstrap_js', plugin_dir_url(__FILE__).'assets/vendor/bootstrap/js/bootstrap.min.js', array('jquery'), '0.1.0', true);
  }

public function wpd_cmembership_pages(){
$cmembership_pages = array(

'cmember-register'=>array(
'title'=>'Membership Registration',
'content'=>'[cmember-register-page]',
),

'cmember-login'=>array(
  'title'=>'Member Login',
  'content'=>'[cmember-login]',
),

'cmember-account'=>array(
  'title'=>'Your Account Dashboard',
  'content'=>'[cmember-account]',
),

'cmembership-plans'=>array(
  'title'=>'membership Plans',
  'content'=>'[cmembership-plans]',
),

);

foreach( $cmembership_pages as $slug=>$page ){
//check if the pages do not exists before creating them.
$check_pages = new WP_Query('pagename='.$slug);
if(!$check_pages->have_posts()){
$post_id = wp_insert_post( array(
'post_name'=>$slug,
'post_title'=>$page['title'],
'post_content'=>$page['content'],
'post_type'=>'page',
'comment_status'=>'closed',
'ping_statuse'=>'closed',
'post_status'=>'publish') );
//save the page id for deleting the page after plugin deactivation
update_option( $slug, $post_id );
  }
    }
  }

//load plugins templates dynamically

public function wpd_cmember_get_template_html( $template_name, $attributes = null ) {
if( !$attributes ){
  $attributes = array();
}

ob_start();

do_action('before_render'.$template_name);

require('templates/' .$template_name. '.php');

do_action('after_render'.$template_name);

$html = ob_get_contents();

ob_end_clean();

return $html;
}


public function wpd_register_shortcode_handler( $default_attributes, $attributes ){
$default_attributes = array( 'show_title'=>false );
$attributes = shortcode_atts( $default_attributes,$attributes );
$show_title = $attributes['show_title'];
$attributes['redirect'] = '';
if( isset( $_REQUEST['redirect_to'] ) ){
$attributes['redirect'] = wp_validate_redirect( isset($_REQUEST['redirect_to']), $attributes['redirect'] );
}

if(is_user_logged_in()){
wp_redirect(home_url('cmember-account'));
}
elseif(!get_option('users_can_register')){
  return __('Registration is currently not supported', 'cmembership');
}
else{
return $this->wpd_cmember_get_template_html('member_register', $attributes);
}
}

public function wpd_redirect_logged_in_user($redirect_to = null ){
  $user= get_current_user();
  if(user_can($user, 'manage_options')){
    if($redirect_to){
      wp_safe_redirect($redirect_to);
    }
    else{
      wp_redirect(admin_url());
    }
  }
  else{
    wp_redirect(home_url('member-account'));
  }
}

public function wpd_redirect_custom_reg(){
  if("GET"==$_SERVER['REQUEST_METHOD']){
    if(is_user_logged_in()){
      $this->wpd_redirect_logged_in_user();
    }
    else{
      wp_redirect(home_url('cmember-register'));
    }
    exit;
  }
}

private function wpd_register_user($email, $first_name, $last_name, $phone_number,$pwd){
  $errors = new WP_Error();
  if(!is_email($email)){
    $errors->add('email', $this->get_error_message('email'));
    return $errors;
  }
  if(username_exists($email) || email_exists($email) ){
    $errors->add('email_exists', $this->get_error_message('email_exists'));
    return $errors;
  }

  //generate password
  //$password = wp_generate_password(12, false);

  $user_data = array(
    'user_login'=>$email,
    'user_email'=>$email,
    'user_pass'=>$pwd,
    'first_name'=>$first_name,
    'last_name'=>$last_name,
    'nickname'=>$first_name
  );
  $user_id = wp_insert_user($user_data);

  //wp_new_user_notification($user_id, $password);

  return $user_id;

}

public function wpd_do_register_user(){
if("POST"==$_SERVER['REQUEST_METHOD']){
  $redirect_url = home_url('cmember-register');
}
  if(!get_option('users_can_register')){
    $redirect_url = add_query_arg('registr_errors', 'closed', $redirect_url);
  }
  else{
    $email= $_POST['email'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $phone_number = sanitize_text_field($_POST['phone_number']);
    $amount = sanitize_text_field($_POST['sub_plan']);
    $pwd = sanitize_text_field($_POST['pwd']);

    $register_result = $this->wpd_register_user($email, $first_name, $last_name, $phone_number,$pwd );
    if(is_wp_error($register_result)){
      //unsuccessful Registration
      $errors = join(',', $register_result->get_error_codes());
      $redirect_url = add_query_arg('register_errors',$errors, $redirect_url );

      wp_redirect($redirect_url);

    }
    else{
      global $wpdb;
      $members_table = $wpdb->prefix.'cmembership_users';
      $member_data = array();
      $member_data['email'] = $email;
      $member_data['first_name'] = $first_name;
      $member_data['last_name'] = $last_name;
      $member_data['phone_number'] = $phone_number;
      $member_data['membership_level'] = '';
      $member_data['member_status'] = '';
      $member_data['created_at'] = current_time( 'mysql');
      $wpdb->insert($members_table,$member_data );
      $blog_name = get_bloginfo( 'name');
      $to = $member_data['email'];
      $blog_url = home_url('cmember-login');
      $subject = "Your Registration on $blog_name was successful";
      $message = "Dear $first_name , Your Registration on $blog_name Was successful, please <a href='$blog_url'>log in</a> to start enjoying your membership benefits.";
      //send a mail to welcome the user
      wp_mail($to, $subject,$message,$headers);


      if( !session_id() ){
        session_start();
        $_SESSION['user_making_payment'] = $email;
        $_SESSION['user_id'] = $register_result;
      }

      ?>

      <form method='POST' action='https://voguepay.com/pay/' id='start_payment'>
      <input type='hidden' name='v_merchant_id' value='6635-0049683' />
      <input type='hidden' name='merchant_ref' value='<?php echo rand(998388, 3838);?>'/>
      <input type='hidden' name='memo' value='Membership Plan payment' />
      <input type='hidden' name='notify_url' value='<?php echo home_url('cmember-login');?>'/>
      <input type='hidden' name='success_url' value='<?php echo home_url('cmember-login');?>'/>
      <input type='hidden' name='fail_url' value='<?php echo home_url('cmember-login');?>'/>
      <input type='hidden' name='cur' value='NG'/>
      <input type='hidden' name='total' id='total_value' value='<?php echo $amount;?>'/>

      </form>
      <script>
      var amt = document.getElementById('total_value').value;
      console.log(amt);
    document.getElementById('start_payment').submit();
  </script>"

      <?php
    }
  }
}

public function get_error_message($error){
  switch($error){
    case "email":
    return __('Email address is invalid', 'cmembership');
    break;

    case "email_exists":
    return __('Email address exists, please choose another one', 'cmembership');
    break;

    case 'closed':
    return __( 'Registering new users is currently not allowed.', 'personalize-login' );
    break;

  }
}

public function wpd_cmember_login_shortcode_handler($default_attributes, $attributes){
  $default_attributes = array('show_title'=>FALSE);
  $attributes = shortcode_atts($default_attributes, $attributes);
  $show_title = $attributes['show_title'];

  if(is_user_logged_in()){
    wp_redirect(home_url('cmember-account'));
  }

  $attributes['redirect']='';
  if(isset($_REQUEST['redirect_to'])){
    $attributes['redirect'] = wp_validate_redirect(isset($_REQUEST['redirect_to']), $attributes['redirect'] );
  }

  return $this->wpd_cmember_get_template_html('cmember_login', $attributes);

}

public function wpd_redirect_get_login(){
  if($_SERVER['REQUEST_METHOD']=="GET"){
    $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
    if(is_user_logged_in()){
      $this->wpd_redirect_logged_in_user($redirect_to);
    }

    $login_url = home_url('cmember-login');
    if(!empty($redirect_to)):
    $login_url = add_query_arg('redirect_to', $redirect_to,$login_url );
  endif;

  wp_redirect($login_url);
  exit;
  }


}

public function wpd_maybe_redirect($user, $username,$password){

  if("POST"==$_SERVER['REQUEST_METHOD']){
    if(is_wp_error($user)){
      $errors = join(',', $user->get_error_codes());
      $login_url = home_url('cmember-login');
      $login_url = add_query_arg('login_errors',$errors,  $login_url);
      wp_redirect($login_url);
      exit;

    }
  }
  return $user;
}

public function wpd_login_redirect_user($redirect_to,$requested_redirect_to,$user ){
  $redirect_url = home_url();

    if ( ! isset( $user->ID ) ) {
        return $redirect_url;
    }

    if ( user_can( $user, 'manage_options' ) ) {
        // Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
        if ( $requested_redirect_to == '' ) {
            $redirect_url = admin_url();
        } else {
            $redirect_url = $requested_redirect_to;
        }
    } else {
        // Non-admin users always go to their account page after login
        $redirect_url = home_url( 'cmember-account' );
    }

    return wp_validate_redirect( $redirect_url, home_url() );
}

public function wpd_cmembership_plans_shortcode_handler($default_attributes, $attributes)
{
$default_attributes = array('show_title'=>FALSE);
$attributes = shortcode_atts($default_attributes,$attributes );

return $this->wpd_cmember_get_template_html('membership_plans', $attributes = null);
}

public function wpd_cmember_delete_pages(){
$page_id = get_option('cmember-register',true);
$login_page = get_option('cmember-login', true);
wp_delete_post($page_id);
wp_delete_post($login_page);
wp_delete_post(get_option('cmember-account',true));
wp_delete_post(get_option('cmembership-plans',true));

}

public function member_account_shortcode_handler(){
global $wpdb;
$cmembers = $wpdb->prefix.'cmembership_users';
$current_user = wp_get_current_user();
$cur_user_email =   $current_user->user_email;
//echo $cur_user_email;
$member_info = [];

//fetch user details from our custom database table
$query = "SELECT * FROM $cmembers WHERE email='$cur_user_email'";
$user_details = $wpdb->get_row($query, ARRAY_A);
//var_export($user_details);
if(!empty($user_details)){
foreach ($user_details as $key=>$value ) {
  $member_info[$key]=$value;

}
}


?>
<div class="col-md-6">
<div class="panel panel-primary">
  <div class="panel-heading">
    <h1 class="panel-title">Your Membership Details</h1>
  </div>
  <div class="panel-body">

    <p>First Name: <?php echo $member_info["first_name"]?> </p>
    <p>Last Name: <?php echo $member_info['last_name']?> </p>
    <p>Email: <?php echo $member_info['email']?> </p>
    <p>Phone Number:<?php echo $member_info['phone_number']?> </p>
    <p>Registered on: <?php echo $member_info['created_at']?> </p>
    <p>Membership Level: <span class="badge badge-primary"><?php echo $member_info['membership_level']?></span> </p>
    <p>Membership Status: <span class="badge badge-primary"><?php echo $member_info['member_status']?> </span></p>

  </div>
  <div class="panel-footer">

  </div>
</div>
</div>


<div class="col-md-6">
<div class="panel panel-primary">
  <div class="panel-heading">
    <h1 class="panel-title">Subscribe / Upgrade Account</h1>
  </div>
  <div class="panel-body">
    <?php echo do_shortcode( '[member_sub_upgrade]' ); ?>
  </div>
  <div class="panel-footer">

  </div>
</div>
</div>

<?php
}

public function member_sub_upgrade_handler()
{ if(!session_id()){
  session_start();
}
  $_SESSION['user_making_payment'] = wp_get_current_user()->user_email;
  //var_export($_SESSION);

  $membership_levels = get_option('member_levels');

  $upgrade_html ="<form action='https://voguepay.com/pay/' method='post' class='form-horizontal'>
  <div class='form-group'>
  <label class='control-label col-md-4'>Select Plan</label>
  <div class='col-md-8'>
  <select id='sub_plan_cost' class='form-control'>
  <option selected='selected'>Select Membership Plan</option>";
  foreach ($membership_levels as $key => $value) {
  $upgrade_html.="<option value='$value'>$key - &#8358;$value</option>";
  }
  $upgrade_html.="</select></div></div>";
  echo $upgrade_html;
  ?>
  <input type='hidden' name='v_merchant_id' value='6635-0049683' />
  <input type='hidden' name='merchant_ref' value='<?php echo rand(998388, 3838);?>'/>
  <input type='hidden' name='memo' value='Membership Plan payment' />
  <input type='hidden' name='notify_url' value='<?php echo home_url('cmember-account');?>'/>
  <input type='hidden' name='success_url' value='<?php echo home_url('cmember-account');?>'/>
  <input type='hidden' name='fail_url' value='<?php echo home_url('cmember-account');?>'/>
  <input type='hidden' name='cur' value='NG'/>
  <input type='hidden' name='total' id='sub_total_value' value='<?php echo $amount;?>'/>
  <div class='form-group'>
    <div class="col-md-8 col-md-offset-4">
  <input type='submit' id='sub_upgrade' class='btn btn-active btn-primary form-control' value='SUBSCRIBE'>
  </div>
  </div>
</form>
<script>
$("#sub_plan_cost").on('change', function(){
  var amt_pay = $("#sub_plan_cost").val();
  $("#sub_total_value").val(amt_pay);
});
</script>

<?php

echo do_shortcode( "[voguepay_payment]" );

}

public function create_members_tbl()
{
  global $wpdb;
  require_once ( ABSPATH.'/wp-admin/includes/upgrade.php');
  $cmembership_users = $wpdb->prefix.'cmembership_users';
  $charset = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE $cmembership_users (
    id int(9) NOT NULL AUTO_INCREMENT,
    email varchar(55) NOT NULL DEFAULT '',
    phone_number varchar(55) NOT NULL DEFAULT '',
    first_name varchar(55) NOT NULL DEFAULT '',
    last_name varchar(255) NOT NULL DEFAULT '',
    membership_level varchar(55) NOT NULL DEFAULT '',
    member_status varchar(55) NOT NULL DEFAULT '',
    created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY  (id)
  )$charset;";

  dbDelta($sql);

}

public function membership_levels_metabox()
{
  add_meta_box( 'membership_level', 'Select Post Membership Access', array($this,'member_post_access'), 'post', 'side','high');

}

public function member_post_access(){

  $members_allowed = get_post_meta(get_the_ID(), 'membership_access',TRUE);
  $members_level = get_option('member_levels');
  wp_nonce_field(basename(__FILE__), 'member_access_nonce');
  echo '<select name="membership_access" id="" style="width:100%;">
  <option selected="selected" value="null">Select Member Access</option>';
  foreach($members_level as $levels){
	echo "<option value='$levels'>$levels</option>";
}
echo "</select>
<div class='notice-info'><p class='inline'>This Post is Accessible to : $members_allowed </p></div>";



//associate the post with the membership level
}

public function save_membership_post_access($post_id,$post,$update)
{

  if(!isset($_POST['member_access_nonce']) || !wp_verify_nonce( $_POST['member_access_nonce'], basename(__FILE__) )){
    return $post_id;
  }

  if(!current_user_can( "edit_post", $post_id )){
    return $post_id;
  }

  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
    return $post_id;

  }

  $slug = "post";

  if( $slug != $post->post_type ){
    return $post_id;
  }

  $post_membership_access = "";

  if(isset($_POST['membership_access']) && !empty($_POST['membership_access']) && $_POST['membership_access'] !=="null"){

    $post_membership_access = $_POST['membership_access'];

  }

  update_post_meta($post_id, 'membership_access', $post_membership_access );


}

public function create_memberships()
{
    $membership_levels = ['Monthly Member'=>2000,'Quartely Member'=>5000,'Bi-Annual Member'=>9000, 'Annual Member'=>16800];
    update_option('member_levels', $membership_levels);

}

public function check_post_access_level()
{
  $sub_url="";
  if(is_user_logged_in()){
    $sub_url = home_url('cmember-account');
  }
  else{
    $sub_url = home_url('cmember-register');
  }
  global $post;
  if(is_user_logged_in() || ! is_user_logged_in() ){

if(get_post_type($post->ID) =="post" && !current_user_can( 'administrator' )){

  $post_access = get_post_meta(get_the_ID(), 'membership_access',true);

  $current_user = wp_get_current_user();
  $current_user_id = $current_user->ID;

  $check_user = new WP_User($current_user_id);
  $user_caps = $check_user->caps;

  if( ! array_key_exists($post_access,$user_caps)){

    die("<div class='alert alert-error'><h1 class='text-uppercase text-danger'>You are not allowed to view this post</h1><hr> You Need To Have Membership Level Of: <span class='text-uppercase badge badge-default'>$post_access</span> To Be Able To View This Post.
    <a href='$sub_url'>UPGRADE</a>
    </div>");
  }


  }


    }

  }

  public function handle_voguepay_payment()
  {
    $merchant_id = '6635-0049683';
    //6635-0049683
    if(isset($_POST['transaction_id'])){
      //get the full transaction details as an json from voguepay
      $json = file_get_contents('https://voguepay.com/?v_transaction_id='.$_POST['transaction_id'].'&type=json');
      //create new array to store our transaction detail
      $transaction = json_decode($json, true);
      //var_export($transaction['total']);
      if($transaction['total'] == 0)die("<div class='alert alert-danger'><p>Your Transaction Was Not successful , Please Try Again</p>");
      if($transaction['status'] == 'Approved'){
        global $wpdb;
        //echo $transaction['transaction_id'];
        //echo $transaction['total'];
        $cmembers_table = $wpdb->prefix.'cmembership_users';
        $membership_level="";
        //update the person as a subscribed member with the specfic membership plan.
        //check and doa switch depending on the amount paid and give the respective membership level
        switch($transaction['total']){
          case '2000':
          $membership_level = "Monthly Member";
          break;

          case '5000':
            $membership_level = "Quartely Member";
          break;

          case '9000':
          $membership_level = "Bi-annual Member";
          break;

          case '16800':
          $membership_level = "Annual Member";
          break;
        }

        $update_data = array();

        $update_data['membership_level']= $membership_level;
        $update_data['member_status'] = 'active';

        $update_where = array();
        $update_where['email'] = $_SESSION['user_making_payment'];

        $user_id = wp_get_current_user()->ID;



        if( $wpdb->update($cmembers_table,$update_data, $update_where )){
          $user = new WP_User($user_id);
          $user_role = $user->add_role($membership_level);

        echo "<div class='alert alert-success'><p>Your Transaction Was successful</p></div>";
        //session_destroy();
      }

      }
    else{
      echo "<div class='alert alert-danger'><p>Your Transaction Was Not successful, please try again</p></div>";
    }

    }

  }

  public function voguepay_payment_reg_pay()
  {
    $merchant_id = '6635-0049683';
    //6635-0049683
    if(isset($_POST['transaction_id'])){
      //get the full transaction details as an json from voguepay
      $json = file_get_contents('https://voguepay.com/?v_transaction_id='.$_POST['transaction_id'].'&type=json');
      //create new array to store our transaction detail
      $transaction = json_decode($json, true);

      if($transaction['total'] == 0)die("<div class='alert alert-danger'><p>Your Transaction Was Not successful , Please Try Again</p>");
      if($transaction['status'] == 'Approved'){
        global $wpdb;
        //echo $transaction['transaction_id'];
        //echo $transaction['total'];
        $cmembers_table = $wpdb->prefix.'cmembership_users';
        $membership_level="";
        //update the person as a subscribed member with the specfic membership plan.
        //check and doa switch depending on the amount paid and give the respective membership level
        switch($transaction['total']){
          case '2000' :
          $membership_level = "Monthly Member";
          break;

          case '5000' :
            $membership_level = "Quartely Member";
          break;

          case '9000' :
          $membership_level = "Bi-annual Member";
          break;

          case '16800' :
          $membership_level = "Annual Member";
          break;
        }
        $update_data = array();

        $update_data['membership_level']= $membership_level;
        $update_data['member_status'] = 'active';

        $update_where = array();
        $update_where['email'] = $_SESSION['user_making_payment'];

        $user_id = $_SESSION['user_id'];

        if( $wpdb->update($cmembers_table,$update_data, $update_where )){

          $user = new WP_User($user_id);
          $user_role = $user->add_role($membership_level);

        echo "Your Transaction Was successful";
        session_destroy();
      }

      }
    else{
      echo "<div class='alert alert-danger'><p>Your Transaction Was Not successful, please try again</p></div>";
    }

    }

  }


}

$cmembership = new CmemberShip();

//create plugin pages on plugin action -> register_activation_hook
register_activation_hook(__FILE__, array('CmemberShip', 'wpd_cmembership_pages'));
register_deactivation_hook(__FILE__, array('CmemberShip', 'wpd_cmember_delete_pages'));
register_activation_hook( __FILE__, array('CmemberShip', 'create_members_tbl') );
register_activation_hook( __FILE__, array('CmemberShip', 'create_memberships') );
