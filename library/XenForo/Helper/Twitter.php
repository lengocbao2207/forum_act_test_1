<?php

class XenForo_Helper_Twitter
{
	/**
	 * @param string $callbackUrl URL to return to
	 *
	 * @return bool|Zend_Oauth_Consumer False if no Twitter app configured, otherwise Oauth consumer
	 */
	public static function getOauthConsumer($callbackUrl = '')
	{
		$options = XenForo_Application::getOptions();

		if (!$options->twitterAppKey || !$options->twitterAppSecret)
		{
			return false;
		}

		return new Zend_Oauth_Consumer(array(
			'callbackUrl' => $callbackUrl,
			'siteUrl' => 'https://api.twitter.com/oauth',
			'authorizeUrl' => 'https://api.twitter.com/oauth/authenticate',
			'consumerKey' => $options->twitterAppKey,
			'consumerSecret' => $options->twitterAppSecret,
		));
	}

	/**
	 * Gets the Twitter service object for a token
	 *
	 * @param string|Zend_Oauth_Token_Access $token Access token object or access token string
	 * @param null|string $secret Access token secret if token is provided as string
	 *
	 * @return Zend_Service_Twitter
	 */
	public static function getService($token, $secret = null)
	{
		$options = XenForo_Application::getOptions();

		if ($token instanceof Zend_Oauth_Token_Access)
		{
			$accessToken = $token;
		}
		else
		{
			$accessToken = new Zend_Oauth_Token_Access();
			$accessToken->setToken($token);
			$accessToken->setTokenSecret($secret);
		}

		return new Zend_Service_Twitter(array(
			'accessToken' => $accessToken,
			'oauthOptions' => array(
				'consumerKey' => $options->twitterAppKey,
				'consumerSecret' => $options->twitterAppSecret,
			)
		));
	}

	/**
	 * Gets the user information from a token
	 *
	 * @param string|Zend_Oauth_Token_Access $token Access token object or access token string
	 * @param null|string $secret Access token secret if token is provided as string
	 *
	 * @return array|boolean
	 */
	public static function getUserFromToken($token, $secret = null)
	{
		//try
		//{
			$twitter = self::getService($token, $secret);
			$details = $twitter->accountVerifyCredentials();

			// force array return
			return json_decode(json_encode($details->toValue()), true);
		/*}
		catch (Exception $e)
		{
			return false;
		}*/
	}
}