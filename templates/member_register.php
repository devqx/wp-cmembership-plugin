<?php
$attributes['errors']= array();
if(isset($_REQUEST['register-errors'])){
  $errors = explode(',', $_REQUEST['register-errors']);

  foreach($errors as $error){
    $attributes['errors'][]= $this->get_error_message($error);
  }
}
?>


<div class="container">
  <div class="row">
    <form class="form-horizontal" action="<?php echo wp_registration_url();?>" method="post">
      <div class="col-md-8">
      <div class="panel panel-default">
      <div class="panel-body">
        <div class="form-group">
          <div class="col-md-12">
        <center>
    <p>Choose Your Subscription Circle</p>
        <p>
        <label for="monthly" class="btn btn-primary"> Monthly &#8358;2000 <input id="monthly" value="4000" name="sub_plan" type="radio"></label>
        <label for="quartely" class="btn btn-primary"> Quartely &#8358;5000 <input id="quarterly" value="13000" name="sub_plan" type="radio"></label>
        <label for="bi_annually" class="btn btn-primary"> Bi-anually &#8358;9000 <input id="bi_annually" value="20000" name="sub_plan" type="radio"></label>
        <label for="annually" class="btn btn-primary"> Annually &#8358;16,800 <input id="yearly" value="30000" name="sub_plan" type="radio"></label>

        </p>
      </center>
      </div>
      <div class="row">
        <div class="col-md-10">
    <div class="form-group">
      <label class="control-label col-md-4" for="email">Email</label>
      <div class="col-md-8">
      <input type="text" class="form-control" id="" name="email" placeholder="">
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-md-4" for="first_name">First Name</label>
      <div class="col-md-8">
      <input type="text" class="form-control" name="first_name" id="" placeholder="">
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-md-4" for="last_name">Last Name</label>
      <div class="col-md-8">
      <input type="text" class="form-control" name="last_name" id="" placeholder="">
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-md-4" for="phone_number">Phone Number</label>
      <div class="col-md-8">
      <input type="text" class="form-control" name="phone_number" id="" placeholder="">
      </div>
    </div>

    <div class="form-group">
      <label class="control-label col-md-4" for="pwd">Password</label>
      <div class="col-md-8">
      <input type="password" class="form-control" name="pwd" id="" placeholder="">
      </div>
    </div>

    <div class="form-group">
      <div class="col-md-8 col-md-offset-4">
      <input type="submit" class="form-control btn btn-primary" name="submit" id="" placeholder="">
      </div>
    </div>

  </div>



      </div>
    </div>
  </div>
    </form>
    </div>
  </div>

</div>
</div>
