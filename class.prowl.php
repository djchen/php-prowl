<?php
/**
 * php-prowl
 *
 * This class provides a simple mechanism for interacting with the prowlapp.com
 * API service for pushing notifications to iOS devices.
 * @author Dan Chen <dan@djc.me>
 * @author Scott Wilcox <scott@dor.ky>
 * @version 0.1
 * @package prowl
 */

class Prowl {

	private $config = array(
		'apiUrl' => 'https://api.prowlapp.com/publicapi/',
		'userAgent' => 'php-prowl 0.1',
		'apiKey' => null,
		'apiProviderKey' => null,
		'requestMethod' => 'GET',
		'debug' => false
	);

	public $remainingCalls = 0;
	public $resetdate = 0;

	public function __construct($settings) {
		foreach ($settings as $setting => $value) {
			$this->config[$setting] = $value;
		}
		if (!defined('LINE_ENDING')) {
			define('LINE_ENDING', isset($_SERVER['HTTP_USER_AGENT']) ? '<br />' : "\n");
		}
		print_r($this->config);
	}

	private function buildQuery($params) {
		$queryString = '';
		if ($this->config['apiKey'] !== null) {
			$queryString .= 'apikey=' . $this->config['apiKey'] . '&';
		} else if ($this->config['apiProviderKey'] !== null) {
			$queryString .= 'providerkey=' . $this->config['apiProviderKey'] . '&';
		}

		if (count($params)) {
			foreach ($params as $key => $value) {
				$queryString .= $key . '=' . urlencode($value) . '&';
			}
		}

		return substr($queryString, 0, -1);
	}

	public function add($params) {
		if (empty($this->config['apiKey'])) {
			throw new Exception('No API key(s) set.');
		}

		foreach ($params as $key => $value) {
			$fields[$key] = $value;
		}

		return $this->request('add', $fields);
	}
	
	public function verify($key) {
		$this->setRequestMethod('GET');
	}

	public function requestToken() {
		if (empty($this->apiProviderKey)) {
			throw new Exception("No provider key(s) set.");
		}

		// Set GET method
		$this->setRequestMethod('GET');

		$response = $this->request("retrieve/token");
		if ($response) {
			if ($response->success["code"] == 200) {
				return $response->retrieve;
			} else {
				throw new Exception("API Request Failed: ".var_dump($response));
			}
		}
	}
	
	public function retrieveApiKey($token) {
		if (empty($this->apiProviderKey)) {
			throw new Exception("No provider key(s) set.");
		}

		// Set GET method
		$this->setRequestMethod("GET");		
		
		// Send our request out
		$response = $this->request("retrieve/apikey",array("token" => $token));
		if ($response) {	
			if ($response->success["code"] == 200) {
				return $response->retrieve['apikey'][0];
			} else {
				throw new Exception('API Request Failed: ' . var_dump($response));
			}
		}		
	}
	
	private function request($endpoint, $params = null) {
		// Push the request out to the API
		$url = $this->config['apiUrl'] . $endpoint;		
		$params = $this->buildQuery($params);

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $params);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_HTTPHEADER, array("Expect:"));
		curl_setopt($c, CURLOPT_HEADER, false);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);		
		curl_setopt($c, CURLINFO_HEADER_OUT, true);
		curl_setopt($c, CURLOPT_USERAGENT, $this->config['userAgent']);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($c, CURLOPT_TIMEOUT, 6);

		$response = simplexml_load_string(curl_exec($c));
		//$httpCode = curl_getinfo($c, CURLINFO_HTTPCODE);		

		if ($this->config['debug'] === true) {
			echo 'API URL: ' . LINE_ENDING . $url . LINE_ENDING;
			echo "<hr />";			
			if (!empty($params)) {
				echo 'Payload: ' . LINE_ENDING;			
				echo var_dump($params);
				echo '<hr />' . LINE_ENDING;				
			}
			echo 'HTTP Header: ' .LINE_ENDING;
			echo curl_getinfo($c, CURLINFO_HEADER_OUT) . LINE_ENDING;
			echo '<hr />';			
		}
		
		if (!empty($response_xml->success)) {
			$this->remainingCalls = $response->success["remaining_calls"];
			$this->resetDate = $response->success["resetdate"];		
		}
		curl_close($c);
		return $response;	
	}

}