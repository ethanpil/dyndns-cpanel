<?php
// Based on the hard work of Mitchel Haan
// https://haanenterprises.com/2013/04/host-your-own-dynamic-dns-using-php-and-cpanel-apis/
//
// usage:
// http://username:password@website.com/dyndns.php?hostname=remote&myip=192.168.1.1
//
// per the settings below, the above will update the IP remote.example.com to 192.168.1.1
// myip is not required, will default to the remote IP calling the script
//
// most dyndns clients will work with a custom url setting. you will likely need to only
// provide the subdomain and not the full address.
// (ie: with this script, hostname=remote   instead of hostname=remote.example.com

/***** Variables *****/
#The username and password used by the updater to send the request.
#HTTP Basic authentication
$php_auth_user='username';
$php_auth_pw='password';

#The url of the cpanel server
$dyndnsCpanel = 'https://example.com:2083';

#username and password used to login to cpanel
$dyndnsCpanelUser = 'username';
$dyndnsCpanelPass = 'password';

#the main domain name of the account on cpanel
$dyndnsDomain = 'example.com';

// Plain text output
header('Content-type: text/plain');


if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="CPanel DynDyns"');
    header('HTTP/1.0 401 Unauthorized');
    die("badauth\n");
}

if(!($_SERVER['PHP_AUTH_USER']==$php_auth_user && $_SERVER['PHP_AUTH_PW']==$php_auth_pw)) {
	sleep(10);
	die("badauth\n");
}

// Make sure a host was specified
if (empty($_GET['hostname']))
	die("notfqdn\n");

// Use server value for IP if none was specified
$ip = $_GET['myip'];
if (empty($ip))
	$ip = $_SERVER['REMOTE_ADDR'];	

// Validate IP address
if (!filter_var($ip, FILTER_VALIDATE_IP))
	die('Invalid IP address');
	
// Get and validate ttl
$ttl = $_GET['ttl'];
if (!is_numeric($ttl) || $ttl < 60)
	$ttl = 300;

// Create class object
$dyn = new DynDnsUpdater();

// Connection information
$dyn->setCpanelHost($dyndnsCpanel);
$dyn->setDomain($dyndnsDomain);

// Set username
$dyn->setCpanelUsername($dyndnsCpanelUser);

// Set password
$dyn->setCpanelPassword($dyndnsCpanelPass);


$dyn->updateHost($_GET['hostname'], $ip);

// End of processing
exit;

/**********************************/
/*** Function definitions below ***/
/**********************************/

class DynDnsUpdater
{
	private $curl;	
	private $cpanelHost;
	private $cpanelUsername;
	private $cpanelPassword;
	private $domain;


	/***** Constructor / Destructor *****/

	function __construct()
	{
		// Create curl object
		$this->curl = curl_init();		

		$curlDefaults = array(
			CURLOPT_SSL_VERIFYPEER => false, 	// Allow self-signed certs
			CURLOPT_SSL_VERIFYHOST => false,	// Allow certs that do not match the hostname
			CURLOPT_RETURNTRANSFER => true,		// Return contents
			);

		curl_setopt_array($this->curl, $curlDefaults);
	}
	
	function __destruct()
	{
		// Release curl object
		curl_close($this->curl);
	}


	/***** Setters *****/

	function setCpanelHost($host)
	{
		$this->cpanelHost = $host;
	}
	
	function setCpanelUsername($username)
	{
		$this->cpanelUsername = $username;
	}
	
	function setCpanelPassword($password)
	{
		$this->cpanelPassword = $password;
	}
	
	function setDomain($domain)
	{
		$this->domain = $domain;
	}

	/***** Public Functions *****/
	
	

	public function updateHost($host, $ip)
	{
		$hosts = $this->getHost($host);
		        
		if ($hosts === false)
			return false;
		
		foreach ($hosts as $hostInfo)
		{
			if ($hostInfo['address'] == $ip)
			{
				echo "nochg $ip\n";
				return true;
			}
		
			$updateParams = array(
				'cpanel_jsonapi_module' => 'ZoneEdit',    
				'cpanel_jsonapi_func' => 'edit_zone_record',
				'domain' => $this->domain,
				'Line' => $hostInfo['Line'],
				'type' => $hostInfo['type'],
				'address' => $ip
			);
		
               
        
			$result = $this->cpanelRequest($updateParams);
		
			if ($result)
				echo "good $ip\n";
			else
				echo "Update failed: {$hostInfo['name']}\n";
		}
	}
	

	/***** Private Functions *****/

	private function getHost($host)
	{
		$fetchzoneParams = array(
			'cpanel_jsonapi_module' => 'ZoneEdit',    
			'cpanel_jsonapi_func' => 'fetchzone_records',
			'domain' => $this->domain,
			'customonly' => 1
		);
		
		$result = $this->cpanelRequest($fetchzoneParams);

		if (empty($result['data']))
			return false;
		
		// Get the payload
		$zoneFile = $result['data'];
		
		$hosts = array();
		foreach ($zoneFile as $line)
		{
			if ( ($line['type'] == 'A') && 
				 ($host == DYNDNS_ALLHOSTS || (strcasecmp($line['name'], $host.'.') === 0)) )
			{	
				$hosts[] = $line;
			}
		}

		if (!empty($hosts))
			return $hosts;
		else
			echo "nohost\n";
		
		return false;
	}
	
	private function cpanelRequest($params)
	{
		if (empty($this->curl) || empty($params))
			return false;
	
		curl_setopt($this->curl, CURLOPT_URL, $this->cpanelHost.'/json-api/cpanel?'.http_build_query($params));		
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array( 'Authorization: Basic ' . base64_encode($this->cpanelUsername.':'.$this->cpanelPassword)) );
		
		$result = curl_exec($this->curl);
		$error = false;
		
		// Check for valid result
		if ($result === false)
		{
			echo curl_error($this->curl)."\n";
			
			// If curl didn't return anything, there's nothing else to check
			return false;
		}
				
		// Check for error code
		if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != '200')
		{
			$err = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
			echo "Error $err\n";
			$error = true;
		}
		
		// Attempt to process result
		$jsonResult = json_decode($result, true);
		
		if (empty($jsonResult))
		{
			echo "Invalid JSON: \n".$result."\n";
			return false;
		}

		// Check for cpanelresult object
		if (isset($jsonResult['cpanelresult']))
		{
			$jsonResult = $jsonResult['cpanelresult'];
		}
		else
		{
			$error = true;
		}
		
		// Check for cpanel error
		if (isset($jsonResult['error']))
		{
			echo $jsonResult['error']."\n";
			$error = true;
		}
		
		if ($error)
		{
			// No sense going past here... no more information to get
			return false;
		}

		return $jsonResult;
	}
}

?>
