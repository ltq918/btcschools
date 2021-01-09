<?php
$_HTML['title'] = 'Tron System Contract - Transfer Contract';
$_HTML['meta']['keywords'] = "Tron Transfer Contract,Tron Transfer Contract In PHP,PHP";
include_once "../common.php";
include_once("html_header.php");

?>
<h2 class="mt-3">Tron System Contract . Transfer Contract</h2>
<hr/>
	<p>
	The typical way to transfer TRX from owner address to recipient address.
	</p>
	
<hr/>


<h3 class="mt-3" id='hashtag3'>Generate Contract Serialized Hex:</h3>
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a data-toggle="tab" class="nav-link active" href="#form1_tabitem1">Visual</a>
	</li>
	<li class="nav-item">
		<a data-toggle="tab" class="nav-link" href="#form1_tabitem2">Coding</a>
	</li>
	<li class="nav-item">
		<a data-toggle="tab" class="nav-link" href="#form1_tabitem3">Protobuf Message</a>
	</li>
	<li class="nav-item">
		<a data-toggle="tab" class="nav-link" href="#form1_tabitem4">PHP Built By Protoc</a>
	</li>
</ul>
<div class="tab-content">
	<div id="form1_tabitem1" class="tab-pane fade show active">
		<iframe src="tron_sysc_transfercontract_form.php" width="100%" scrolling="no" frameborder="no"></iframe>
	</div>
	<div id="form1_tabitem2" class="tab-pane fade">
<pre style='border-radius:none;'><?php echo htmlentities(file_get_contents("tron_sysc_transfercontract_form.php"));?></pre> 		
	</div>
	<div id="form1_tabitem3" class="tab-pane fade">
<pre style='border-radius:none;'>
message TransferContract {
	
	#The owner of the current account
    bytes owner_address = 1;
	
	#The target address to transfer
    bytes to_address = 2;
	
	#The amount of TRX to transfer
    int64 amount = 3;
}
</pre> 		
	</div>
	
	<div id="form1_tabitem4" class="tab-pane fade">
<pre style='border-radius:none;'><?php echo htmlentities(file_get_contents("protobuf/core/contract/Protocol/TransferContract.php"));?></pre> 		
	</div>
</div>


<?php
include_once("html_footer.php");