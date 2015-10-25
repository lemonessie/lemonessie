<?php
	$method = $_SERVER['REQUEST_METHOD'];
	$accID = $_ENV["ACCOUNT_ID"];
	$apiKey = $_ENV["NESSIE_KEY"];
	
	switch($method) {
		case "POST":
		// *** GET DATA FROM FORM ***
		// default durations for light and small
		$water = "3000";
		$mix = "1000";

		if (isset($_POST['strength'])){
			$strength = trim($_POST["strength"]);
			switch($strength) {
				case "medium":
				$mix = "2000";
				break;
				case "strong":
				$mix = "3000";
				break;
			}
		}
		if (isset($_POST['amount'])){
			$amount = trim($_POST["amount"]);
			switch($strength) {
				case "1.50":
				$water = "6000";
				break;
				case "2.00":
				$water = "9000";
				break;
			}
		}

		// *** NESSIE API CODE ***
		$url = "http://api.reimaginebanking.com/accounts/" . $accID . "/purchases?key=" . $apiKey;
		$merchantID = "562bbe830afebb140066cd5b";
		$date = date("n-j-y");
		$purchase = array(
			"merchant_id" => $merchantID,
			"medium" => "balance",
			"purchase_date" => $date,
			"amount" => floatval($amount),
			"status" => "pending",
			"description" => $strength
		);
		$content = json_encode($purchase);

		// Make the POST request

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, 
			array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($status != 201) {
			die("Error: call to URL $url failed with status $status");
		}

		curl_close($curl);

		// $response = json_decode($json_response, true);

		// *** PARTICLE REQUEST - WATER ***


		// Water Code
		$deviceID = $_ENV["PARTICLE_DEVICE_ID"];
		$functionName = "waterMe";
		$accessToken = $_ENV["PARTICLE_ACCESS_TOKEN"];
		$particleUrl = "https://api.particle.io/v1/devices/" . $deviceID . "/" . $functionName . "?access_token=" . $accessToken;

		$waterArgs = array(
			"args" => $water
		);
		$waterJson = json_encode($waterArgs);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $particleUrl);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $waterArgs);

		$result = curl_exec($curl);

		curl_close($curl);

		// echo "Done with water";



		// *** PARTICLE REQUEST - MIX ***


		$functionName = "mixMe";
		$particleUrl = "https://api.particle.io/v1/devices/" . $deviceID . "/" . $functionName . "?access_token=" . $accessToken;

		$mixArgs = array(
			"args" => $mix
		);
		$mixJson = json_encode($mixArgs);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $particleUrl);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $mixArgs);

		$result = curl_exec($curl);

		curl_close($curl);
		header('Location: nessie_lemonade.html', true, 301);
		die();
		// echo "Done with mix";
		break;
		case "GET":
		// merchant
		$url = "http://api.reimaginebanking.com/accounts/" . $accID . "/purchases?key=" . $apiKey;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($status != 200) {
			die("Error: call to URL $url failed with status $status");
		}

		curl_close($curl);

		$results = json_decode($json_response, true);
		
		$response = array(
			"total" => 0
		);
		foreach($results as $item) {
			$response["total"] += $item["amount"];
		}
		echo json_encode($response);
		break;

	}

?>