<?php 
use IEXBase\TronAPI\Tron;
use IEXBase\TronAPI\Support;
use Protocol\Transaction\Contract\ContractType;

define("TRX_TO_SUN",'1000000');
define("SUN_TO_TRX", '0.000001');

include_once "../libraries/vendor/autoload.php";
include_once("html_iframe_header.php");
include_once("tron_utils.php");

$supportChains = ['main'=>"Tron Mainnet", 'shasta'=>"Shasta Testnet"];

//include all php files that generated by protoc
$dir   = new RecursiveDirectoryIterator('protobuf/core/');
$iter  = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($iter, '/^.+\.php$/', RecursiveRegexIterator::GET_MATCH); // an Iterator, not an array

foreach ( $files as $file ) {
	
	if (is_array($file)) {
		foreach($file as $filename) {
			include $filename;
		}
	} else {
		include $file;
	}
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
		if ($_POST['chain'] == 'main') {
			$fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
			$solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
			$eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.trongrid.io');
		} else {
			$fullNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.shasta.trongrid.io');
			$solidityNode = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.shasta.trongrid.io');
			$eventServer = new \IEXBase\TronAPI\Provider\HttpProvider('https://api.shasta.trongrid.io');
		}
		
		//get current block
		$tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
		$newestBlock = $tron->getCurrentBlock();
		$currentHeight = (int)$newestBlock['block_header']['raw_data']['number'];
		if ($currentHeight<=0) {
			throw new Exception("Fail retrieve current block.");
		}
		
		//get last confirmed block
		$confirmation = 20;
		$targetHeight = ($currentHeight - $confirmation) + 1;
		
		$confirmedBlock = $tron->getBlockByNumber($targetHeight);
		$blockHeight = (int)$confirmedBlock['block_header']['raw_data']['number'];
		$blockTs = (int)$confirmedBlock['block_header']['raw_data']['timestamp'];
		$blockHash = $confirmedBlock['blockID'];
		
		$currentTimeMillis = round(microtime(true) * 1000);
		
		//build tx
		$contract = new \Protocol\Transaction\Contract();
		$contract->mergeFromString(hex2str($_POST['contract_hex']));
		
		if (in_array($contract->getType(), [ContractType::TriggerSmartContract,ContractType::CreateSmartContract])) {
			if (!is_numeric($_POST['feelimit'])) {
				throw new Exception("Fee limit is required");
			} else if (bccomp($_POST['feelimit'], "1000", 6) == 1) {
				throw new Exception("Fee limit should not exceed 1000 TRX");
			}
		} else {
			if (strlen($_POST['feelimit'])) {
				throw new Exception("Fee limit is only applicable to TriggerSmartContract or CreateSmartContract contract type.");
			}
		}
		
		$feeLimitInSun = bcmul($_POST['feelimit'], TRX_TO_SUN);
		$raw = new \Protocol\Transaction\Raw();
		$raw->setContract([$contract]);
		$raw->setFeeLimit($feeLimitInSun);
		
		$blockHeightIn64bits = str_pad(dechex($blockHeight), 8 * 2 /* 8 bytes = 16 hex chars*/, "0", STR_PAD_LEFT);
		
		$raw->setRefBlockBytes( hex2Str( $refBlockBytes = substr($blockHeightIn64bits, 12, 4) ));
		$raw->setRefBlockHash( hex2Str( $refBlockHash =  substr($blockHash, 16, 16) ));
		$raw->setTimestamp($currentTimeMillis);
		$raw->setExpiration( $blockTs + ((int)$_POST['expiration'] * 1000) );
		
		$txId = hash("sha256", $raw->serializeToString());
		
		$tx = new \Protocol\Transaction();
		$tx->setRawData($raw);
		
		$signature = Support\Secp::sign($txId, $_POST['privkey']);
		$tx->setSignature([hex2str( $signature )]);
	
    ?>
        <div class="alert alert-success">
			<h6 class="mt-3">Raw Tx Hex</h6>
			<textarea class="form-control" rows="5" id="comment" readonly><?php echo str2hex($tx->serializeToString());?></textarea>
			
			
			<h6 class="mt-3">Tx Byte Size</h6>
			<input class="form-control" rows="5" id="comment" readonly value="<?php echo $tx->byteSize();?>"></textarea>
			
			<h6 class="mt-3">Consume Bandwidth</h6>
			<input class="form-control" rows="5" id="comment" readonly value="<?php echo $tx->byteSize() + 64;?>"></textarea>
			
			<h6 class="mt-3">Tx Id</h6>
			<input class="form-control" rows="5" id="comment" readonly value="<?php echo $txId;?>"></textarea>
		</div>
<?php 
    } catch (Exception $e) {
        $errmsg .= "Problem found. " . $e->getMessage();

    }
} 

if ($errmsg) {
?>
    <div class="alert alert-danger">
        <strong>Error!</strong> <?php echo $errmsg?>
    </div>
<?php
}
?>
<form action='' method='post'>

	<div class="form-group">
		<label for="chain">Chain:</label>
		<select id="chain" name="chain" class="form-control" >
			<?php
			foreach($supportChains as $k=>$v) {
				echo "<option value='{$k}'".($k == $_POST['chain'] ? " selected": "").">{$v}</option>";
			}
			?>
		</select>
	</div>
	
    <div class="form-group">
        <label for="contract_hex">Contract Serialized Hex:</label>
        <input class="form-control" type='text' name='contract_hex' id='contract_hex' value='<?php echo $_POST['contract_hex']?>'>
    </div>
	
	<div class="form-group">
		<label for="feelimit">Fee Limit (Maximum TRX consumption):</label>
		
		<div class="input-group mb-3">
			<input class="form-control" type='text' name='feelimit' id='feelimit' value='<?php echo $_POST['feelimit']?>'>
			<div class="input-group-append">
			  <span class="input-group-text">TRX</span>
			</div>
		</div>
		<small>This applicable to smart contract deployment (CreateSmartContract) or execution (TriggerSmartContract) only, put blank if you are not sure.</small>
	</div>
	
	<div class="form-group">
		<label for="expiration">Expiration Time:</label>
		
		<div class="input-group mb-3">
			<input class="form-control" type='text' name='expiration' id='expiration' value='<?php echo $_POST['expiration']?>'>
			<div class="input-group-append">
			  <span class="input-group-text">Seconds</span>
			</div>
		</div>
		
		<small>
			Relative time from last confirmed block.
		</small>
	</div>
	
	<div class="form-group">
        <label for="privkey">Private Key:</label>
        <input class="form-control" type='text' name='privkey' id='privkey' value='<?php echo $_POST['privkey']?>'>
    </div>
   
    <input type='submit' class="btn btn-success btn-block"/>
</form>
<?php
include_once("html_iframe_footer.php");