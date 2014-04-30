<?php
/**
 * Based on https://github.com/customerio/customerio-ruby
 *
 * @author     Lucas Brown
 * @copyright  (c) 2014 Locatrix
 */
class CustomerIO
{
	private $base_uri = 'https://track.customer.io';
	private $auth;

	public function __construct($site_id, $api_key)
	{
		$this->auth = array('username' => $site_id, 'password' => $api_key);
	}

	public function identify($attributes)
	{
		if(!isset($attributes['id']))
		{
			return false;
		}

		$url = $this->customer_path($attributes['id']);
		unset($attributes['id']);

		return $this->verify_response($this->put($url, $attributes));
	}

	public function delete($customer_id)
	{
		return $this->verify_response($this->del($this->customer_path($customer_id)));
	}

	public function track($customer_id, $event_name = null, $attributes = array())
	{
		if(empty($event_name))
		{
			//customer_id is the event
			return $this->create_anonymous_event($customer_id);
		}

		return $this->create_customer_event($customer_id, $event_name, $attributes);
	}

	public function create_customer_event($customer_id, $event_name, $attributes = array())
	{
		return $this->create_event($this->customer_path($customer_id).'/events', $event_name, $attributes);
	}

	public function create_anonymous_event($event_name, $attributes = array())
	{
		return $this->create_event('/api/v1/events', $event_name, $attributes);
	}

	public function create_event($url, $event_name, $attributes = array())
	{
		$body = array('name' => $event_name, 'data' => $attributes);
		if(isset($attributes['timestamp']) && is_numeric($attributes['timestamp']))
		{
			$body['timestamp'] = $attributes['timestamp'];
		}

		return $this->verify_response($this->post($url, $body));
	}

	private function customer_path($id)
	{
		return '/api/v1/customers/'.$id;
	}

	private function put($url, $data = array())
	{
		return $this->query('PUT', $url, $data);
	}

	private function post($url, $data = array())
	{
		return $this->query('POST', $url, $data);
	}

	private function del($url, $data = array())
	{
		return $this->query('DELETE', $url, $data);
	}

	private function query($request, $url, $data = array())
	{
		$ch = curl_init($this->base_uri.$url);

        $curlOptions = array(
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => FALSE,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => implode(':', $this->auth),
            CURLOPT_CUSTOMREQUEST => $request
        ); 

        curl_setopt_array($ch, $curlOptions);

		$result = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $code;
	}

	private function verify_response($code)
	{
		return $code >= 200 && $code < 300;
	}
}

?>