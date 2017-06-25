<?php
session_start();
echo do_shortcode( "[voguepay_reg_payment]" );
?>

<div class="container">
  <div class="row">
    <div class="col-md-8">
    <?php if($attributes['show_title']):?>
      <h2><?php _e('Sign In', 'cmembership');?></h2>
    <?php endif;?>
    <div class="panel panel-default">
      <div class="panel-body">
        <form class="form-horizontal" action="<?php echo wp_login_url();?>" method="post">
          <div class="form-group">
            <label class="control-label col-md-4" for="">Email Address</label>
            <div class="col-md-6">
              <input type="text" class="form-control" name="log" id="" placeholder="">
            </div>
          </div>

          <div class="form-group">
            <label class="control-label col-md-4" for="">Password</label>
            <div class="col-md-6">
              <input type="password" class="form-control" name="pwd" id="" placeholder="">
            </div>
          </div>

          <div class="form-group">
            <div class="col-md-6 col-md-offset-4">
              <input type="submit" class="form-control btn btn-primary" name="submit" id="" value="Sign In">
            </div>
          </div>
        </form>
      </div>

    </div>




  </div>

</div>
  </div>
