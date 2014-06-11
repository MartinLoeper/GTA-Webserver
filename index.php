<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../../assets/ico/favicon.ico">

    <title>Testseite</title>

    <!-- Bootstrap core CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
	<link href="assets/slider/css/slider.css" rel="stylesheet">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<style>
		/* Move down content because we have a fixed navbar that is 50px tall */
		body {
		  padding-top: 50px;
		  padding-bottom: 20px;
		}
	</style>
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">Project name</a>
        </div>
        <div class="navbar-collapse collapse">
          <form class="navbar-form navbar-right" role="form">
            <div class="form-group">
              <input type="text" placeholder="Email" class="form-control">
            </div>
            <div class="form-group">
              <input type="password" placeholder="Password" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Sign in</button>
          </form>
        </div><!--/.navbar-collapse -->
      </div>
    </div>
	
	<script>
		function money(amount) {
			$.ajax({
			  type: "POST",
			  url: "/api/remote?data="+amount+"&destination=game&type=100&ret=1",
			  //data: { name: "John", location: "Boston" },
			  dataType: "json"
			})
			.done(function( msg ) {
				$("#money").html( msg.data );
			 });
		}
		
		$(function() {
			$('#sl1').slider({
			  min : 0,
			  max : 1000,
			  value: 200,
			  formater: function(value) {
				return 'Current value: '+value;
			  }
			}).on('slideStop', function(ev){
				money($('#sl1').slider("getValue").val());
			});
		});
		
		function SpecialMove() {
		$.ajax({
			  type: "POST",
			  url: "api/remote?playerid="+$("#playerid").val()+"&destination=game&type=101&actionid="+$("#move").val(),
			  //data: { name: "John", location: "Boston" },
			  dataType: "json"
			})
			.done(function( msg ) {
				
			 });
		}
	</script>
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
        <h1>Money</h1>
        <p><div>Current Money Amount: <span id="money">???</span></div></p>
		<p><a class="btn btn-primary btn-lg" onclick="money(200);" role="button">Add 200 money. &raquo;</a></p>
		<p>
		  <div class="well">
            <input type="text" class="span2 slider" value="200" id="sl1" >
          </div>
		</p>
		<p>
		<!-- http://wiki.sa-mp.com/wiki/SpecialActions -->
		Special Move:<br />
		<input id="playerid">
		<input id="move">
		<button onclick="SpecialMove();">Run</button>
		</p>
      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-md-4">
          <h2>Heading</h2>
          <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
          <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
        </div>
        <div class="col-md-4">
          <h2>Heading</h2>
          <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
          <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
       </div>
        <div class="col-md-4">
          <h2>Heading</h2>
          <p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
          <p><a class="btn btn-default" href="#" role="button">View details &raquo;</a></p>
        </div>
      </div>

      <hr>

      <footer>
        <p>&copy; Company 2014</p>
      </footer>
    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="assets/js/bootstrap.min.js"></script>
	<script src="assets/slider/js/bootstrap-slider.js"></script>
  </body>
</html>
