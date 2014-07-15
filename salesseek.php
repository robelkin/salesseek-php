<?php

/*
 * SalesSeek PHP Library
 * Author: Rob Elkin
 * Organisation: Server Density
 *
 * A library to make integrating with SalesSeek easy within PHP
 */

class SalesSeek
{
	private $url;
	private $email;
	private $password;
	private $client;

	private $cookie;

	public function __construct($email = '', $password = '', $url = '', $client = '')
	{
		$this->email = $email;
		$this->password = $password;
		$this->url = $url;
		$this->client = $client;

		if(!($this->email) || !($this->password) || !($this->url) || !($this->client))
		{
			return false;
		}

		$this->login();
	}

	/**
	 * Find all the lead sources for an account
	 * @return array Lead sources
	 */

	public function LeadSources()
	{
		$response = $this->_doRequest($this->url.'/lead_sources');

		$response = json_decode($response);

		return $response;
	}

	/**
	* Find all the phases for an account
	 * @return array Phases
	*/

	public function Phases()
	{
		$response = $this->_doRequest($this->url.'/phases');

		$response = json_decode($response);

		return $response;
	}

	/**
	* Find all the buckets for an account
	 * @return array Buckets
	*/

	public function Buckets()
	{
		$response = $this->_doRequest($this->url.'/buckets');

		$response = json_decode($response);

		return $response;
	}

	/**
	 * Create an organisation
	 * @param string $orgName The name for the organisation to create
	 * @return array The organisation data
	 */

	public function CreateOrg($orgName = '')
	{
		if($orgName == '')
		{
			return false;
		}

		$response = $this->_doRequest($this->url.'/organizations', 'POST', json_encode(array('name' => $orgName)));

		return json_decode($response);
	}

	/**
	 * Create a lead on the account
	 *
	 * @param string $orgId ID of the organisation to create the lead from. Returned as part of creating an organisation
	 * @param string $firstName First name of the lead
	 * @param string $lastName Surname of the lead
	 * @param string $sourceId ID of the lead source this lead has come from. Retreive IDs using the LeadSources() method
	 * @return array The created lead
	 */

	public function CreateLead($orgId = '', $firstName = '', $lastName = '', $email = '', $sourceId = '')
	{
		if(!($orgId) || !($firstName) || !($lastName)|| !($email) || !($sourceId))
		{
			return false;
		}

		$body = array(
			"first_name" => $firstName,
			"last_name" => $lastName,
			"organization_id" => $orgId,
			"communication" => array(array("name" => "Work", "medium" => "email", "value" => $email)),
			"source" => array("id" => $sourceId)
		);

		$response = $this->_doRequest($this->url.'/individuals?extra_fields=locations&extra_fields=owner&extra_fields=organization', 'POST', json_encode($body));

		return json_decode($response);
	}

	/**
	 * Create a deal on the account
	 *
	 * @param string $orgId ID of the organisation to create the deal from. Returned as part of creating an organisation
	 * @param string $phaseId ID of the phase to set the deal to. Returned from Phases()
	 * @param string $name The name to set the deal up as
	 * @param integer $value The value of the deal
	 * @param string $bucketId The ID of the bucket this deal is contained in
	 * @param string $expectedCloseDate The date the deal is expected to close
	 *
	 * @return array The created deal
	 */

	public function CreateDeal($orgId = '', $phaseId = '', $name = '', $value = 0, $bucketId = '', $expectedCloseDate = '')
	{
		if(!($orgId) || !($phaseId) || !($name))
		{
			return false;
		}

		$body = array(
			"name" => $name,
			"organization_id" => $orgId,
			"phase_id" => $phaseId
		);

		if($value > 0 && $bucketId != '')
		{
			$body["buckets"] = array(array("id" => $bucketId, "value" => $value));
		}

		if($expectedCloseDate != '')
		{
			$body["expected_close_date"] = date(DateTime::ISO8601, $expectedCloseDate);
		}

		$response = $this->_doRequest($this->url.'/opportunities?&extra_fields=phase&extra_fields=organization', 'POST', json_encode($body));

		return json_decode($response);
	}

	/**
	 * Change the phase a deal is in
	 *
	 * @param string $dealId The deal to update
	 * @param string $phaseId The phase to set the deal to
	 *
	 * @return array
	 */

	public function UpdateDealState($dealId = '', $phaseId = '')
	{
		if(!($dealId) || !($phaseId))
		{
			return false;
		}

		$body = array(
			"phase_id" => $phaseId
		);

		$response = $this->_doRequest($this->url.'/opportunities/'.$dealId, 'PATCH', json_encode($body));

		return json_decode($response);
	}

	/**
	 * Change the value of a deal
	 *
	 * @param string $dealId The deal to update
	 * @param integer $value The value of the deal
	 * @param string $bucketId The ID of the bucket this deal is contained in
	 *
	 * @return array
	 */

	public function UpdateDealAmount($dealId = '', $value = 0, $bucketId = '')
	{
		if(!($dealId) || $value == 0 || $bucketId == '')
		{
			return false;
		}

		$body["buckets"] = array(array("id" => $bucketId, "value" => $value));

		$response = $this->_doRequest($this->url.'/opportunities/'.$dealId, 'PATCH', json_encode($body));

		return json_decode($response);
	}

	private function login()
	{
		$requestBody = array("email_address" => $this->email, "password" => $this->password);
		$response = $this->_doRequest($this->url.'/login', 'POST', $requestBody, true);

		$cookies = array();
		preg_match_all('/Set-Cookie:(?<cookie>\s{0,}.*)$/im', $response, $cookies);

		$cookie = explode(';', trim($cookies['cookie'][0]), 2);
		$cookie = $cookie[0];

		$this->cookie = $cookie;
	}

	private function _doRequest($requestUrl = '', $httpVerb = 'GET', $requestBody = null, $returnHeaders = false)
	{
		$ch = curl_init($requestUrl);
		if($returnHeaders == true)
		{
			curl_setopt($ch, CURLOPT_HEADER, 1);
		}
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpVerb);

		$headers = array(
			'ClientId: '.$this->client
		);

		if(!empty($this->cookie))
		{
			$headers[] = 'Cookie: '.$this->cookie;
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		if(!empty($requestBody)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);

		curl_close($ch);

		return $result;
	}
}

?>