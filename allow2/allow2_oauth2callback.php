<HTML>
	<HEAD>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
		<?php $host = 'https://api.allow2.com'; ?>
		<link rel="stylesheet" type="text/css" href="<?php echo $host; ?>/css/styles.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js" type="text/javascript"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	</HEAD>
	<BODY>
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="jumbotron">
						<h1 class="display-3">Pairing...</h1>
						<p class="lead">
							<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
						</p>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			var loc = window.location;
			var baseUrl = loc.protocol + "//" + loc.hostname + (loc.port? ":"+loc.port : "");
			window.onload = function() {
				var result = new RegExp("code=([^&]*)", "i").exec(window.location.search); 
				var code = result && unescape(result[1]) || ""; 
				window.opener.postMessage(code, baseUrl);
			};
		</script>
	</BODY>
</HTML>