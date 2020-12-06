<?php

/**
 * Controller for registration-related actions.
 *
 * @package XenForo_Users
 */
class XenForo_ControllerPublic_Register extends XenForo_ControllerPublic_Abstract
{
	protected function _preDispatch($action)
	{
		// prevent discouraged IP addresses from registering
		if (XenForo_Application::get('options')->preventDiscouragedRegistration && $this->_isDiscouraged())
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('new_registrations_currently_not_being_accepted')
			));
		}
	}

	/**
	 * Displays a form to register a new user.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionIndex()
	{
		if (XenForo_Visitor::getUserId())
		{
			throw $this->responseException(
				$this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
					$this->getDynamicRedirect()
				)
			);
		}

		$this->_assertRegistrationActive();

		$username = '';
		$email = '';

		if ($login = $this->_input->filterSingle('login', XenForo_Input::STRING))
		{
			if (XenForo_Helper_Email::isEmailValid($login))
			{
				$email = $login;
			}
			else
			{
				$username = $login;
			}
		}

		$fields = array(
			'username' => $username,
			'email' => $email
		);

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
		if ($username !== '')
		{
			$writer->set('username', $username);
		}
		if ($email !== '')
		{
			$writer->set('email', $email);
		}

		return $this->_getRegisterFormResponse($fields, $writer->getErrors());
	}

	protected function _getRegisterFormResponse(array $fields, array $errors = array())
	{
		$options = XenForo_Application::get('options');

		if (empty($fields['timezone']))
		{
			$fields['timezone'] = $options->guestTimeZone;
			$fields['timezoneAuto'] = true;
		}

		if (!empty($fields['custom_fields']) && is_array($fields['custom_fields']))
		{
			$customFieldValues = $fields['custom_fields'];
		}
		else
		{
			$customFieldValues = array();
		}

		XenForo_Application::getSession()->set('registrationTime', time());

		$regKey = md5(uniqid('xf', true));
		XenForo_Application::getSession()->set('registrationKey', $regKey);

		$viewParams = array(
			'fields' => $fields,
			'errors' => $errors,

			'timeZones' => XenForo_Helper_TimeZone::getTimeZones(),
			'dobRequired' => $options->get('registrationSetup', 'requireDob'),

			'captcha' => XenForo_Captcha_Abstract::createDefault(),
			'tosUrl' => XenForo_Dependencies_Public::getTosUrl(),

			'regKey' => $regKey,

			'customFields' => $this->_getFieldModel()->prepareUserFields(
				$this->_getFieldModel()->getUserFields(array('registration' => true)),
				true,
				$customFieldValues,
				false
			)
		);

		return $this->responseView(
			'XenForo_ViewPublic_Register_Form',
			'register_form',
			$viewParams,
			$this->_getRegistrationContainerParams()
		);
	}

	/**
	 * Validate a single field
	 *
	 * @return XenForo_ControllerResponse_Redirect
	 */
	public function actionValidateField()
	{
		$this->_assertPostOnly();

		$field = $this->_getFieldValidationInputParams();

		if (preg_match('/^custom_field_([a-zA-Z0-9_]+)$/', $field['name'], $match))
		{
			$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
			$writer->setCustomFields(array($match[1] => $field['value']));

			if ($errors = $writer->getErrors())
			{
				return $this->responseError($errors);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				'',
				new XenForo_Phrase('redirect_field_validated', array('name' => $field['name'], 'value' => $field['value']))
			);
		}
		else
		{
			// handle normal fields
			return $this->_validateField('XenForo_DataWriter_User');
		}
	}

	/**
	 * Gets data from the registration form
	 *
	 * @return array [$data, $passwords]
	 */
	protected function _getRegistrationDataFromInput()
	{
		$data = $this->_input->filter(array(
			'username'   => XenForo_Input::STRING,
			'email'      => XenForo_Input::STRING,
			'timezone'   => XenForo_Input::STRING,
			'gender'     => XenForo_Input::STRING,
			'dob_day'    => XenForo_Input::UINT,
			'dob_month'  => XenForo_Input::UINT,
			'dob_year'   => XenForo_Input::UINT,
		));

		$passwords = $this->_input->filter(array(
			'password' => XenForo_Input::STRING,
			'password_confirm' => XenForo_Input::STRING,
		));

		return array($data, $passwords);
	}

	/**
	 * Registers a new user.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionRegister()
	{
		$this->_assertPostOnly();
		$this->_assertRegistrationActive();

		list($data, $passwords) = $this->_getRegistrationDataFromInput();

		$options = XenForo_Application::getOptions();

		$errors = array();

		if (!XenForo_Captcha_Abstract::validateDefault($this->_input))
		{
			$errors[] = new XenForo_Phrase('did_not_complete_the_captcha_verification_properly');
		}

		if (XenForo_Dependencies_Public::getTosUrl() && !$this->_input->filterSingle('agree', XenForo_Input::UINT))
		{
			$errors[] = new XenForo_Phrase('you_must_agree_to_terms_of_service');
		}

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
		if ($options->registrationDefaults)
		{
			$writer->bulkSet($options->registrationDefaults, array('ignoreInvalidFields' => true));
		}
		$writer->bulkSet($data);
		$writer->setPassword($passwords['password'], $passwords['password_confirm'], null, true);

		// if the email corresponds to an existing Gravatar, use it
		if ($options->gravatarEnable && XenForo_Model_Avatar::gravatarExists($data['email']))
		{
			$writer->set('gravatar', $data['email']);
		}

		$writer->set('user_group_id', XenForo_Model_User::$defaultRegisteredGroupId);
		$writer->set('language_id', XenForo_Visitor::getInstance()->get('language_id'));

		$customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
		$customFieldsShown = array_keys($this->_getFieldModel()->getUserFields(array('registration' => true)));
		$writer->setCustomFields($customFields, $customFieldsShown);

		if (!$this->_validateBirthdayInput($writer, $birthdayError))
		{
			$errors[] = $birthdayError;
		}

		$registerTime = XenForo_Application::getSession()->get('registrationTime');
		if (!$registerTime || ($registerTime + $options->get('registrationTimer')) > time())
		{
			$errors[] = new XenForo_Phrase('sorry_you_must_wait_longer_to_create_account');
		}

		$regKey = XenForo_Application::getSession()->get('registrationKey');
		if (!$regKey  || $regKey != $this->_input->filterSingle('reg_key', XenForo_Input::STRING))
		{
			$errors[] = new XenForo_Phrase('something_went_wrong_please_try_again');
		}

		/** @var XenForo_Model_SpamPrevention $spamModel */
		$spamModel = $this->getModelFromCache('XenForo_Model_SpamPrevention');

		if (!$errors && !$writer->getErrors())
		{
			$spamResponse = $spamModel->allowRegistration($data, $this->_request);

			switch ($spamResponse)
			{
				case XenForo_Model_SpamPrevention::RESULT_DENIED:
					$spamModel->logSpamTrigger('user', null);
					$errors[] = new XenForo_Phrase('spam_prevention_registration_rejected');
					break;

				case XenForo_Model_SpamPrevention::RESULT_MODERATED:
					$writer->set('user_state', 'moderated');
					break;
			}
		}

		$writer->advanceRegistrationUserState();
		$writer->preSave();

		$errors = array_merge($errors, $writer->getErrors());

		if ($errors)
		{
			$fields = $data;
			$fields['tos'] = $this->_input->filterSingle('agree', XenForo_Input::UINT);
			$fields['custom_fields'] = $customFields;
			return $this->_getRegisterFormResponse($fields, $errors);
		}

		$writer->save();

		$user = $writer->getMergedData();

		$spamModel->logSpamTrigger('user', $user['user_id']);

		if ($user['user_state'] == 'email_confirm')
		{
			$this->_getUserConfirmationModel()->sendEmailConfirmation($user);
		}

		return $this->_completeRegistration($user);
	}

	protected function _completeRegistration(array $user, array $extraParams = array())
	{
		XenForo_Model_Ip::log($user['user_id'], 'user', $user['user_id'], 'register');

		XenForo_Application::getSession()->changeUserId($user['user_id']);
		XenForo_Visitor::setup($user['user_id']);

		$this->_executePromotionUpdate(true);
		$this->_executeTrophyUpdate(true);

		// keep the user logged in for a while - more friendly for new users
		$this->_getUserModel()->setUserRememberCookie($user['user_id']);

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);

		$viewParams = $extraParams + array(
			'user' => XenForo_Visitor::getInstance()->toArray(),
			'redirect' => ($redirect ? XenForo_Link::convertUriToAbsoluteUri($redirect) : '')
		);

		return $this->responseView(
			'XenForo_ViewPublic_Register_Process',
			'register_process',
			$viewParams,
			$this->_getRegistrationContainerParams()
		);
	}

	/**
	 * Displays a form to join using Facebook or logs in an existing account.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFacebook()
	{
		$assocUserId = $this->_input->filterSingle('assoc', XenForo_Input::UINT);
		$redirect = $this->_getExternalAuthRedirect();

		$fbRedirectUri = XenForo_Link::buildPublicLink('canonical:register/facebook', false, array(
			'assoc' => ($assocUserId ? $assocUserId : false)
		));

		if ($this->_input->filterSingle('reg', XenForo_Input::UINT))
		{
			XenForo_Application::getSession()->set('loginRedirect', $redirect);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Helper_Facebook::getFacebookRequestUrl($fbRedirectUri)
			);
		}

		$fbToken = $this->_input->filterSingle('t', XenForo_Input::STRING);
		if (!$fbToken)
		{
			$error = $this->_input->filterSingle('error', XenForo_Input::STRING);
			if ($error == 'access_denied')
			{
				return $this->responseError(new XenForo_Phrase('access_to_facebook_account_denied'));
			}

			$code = $this->_input->filterSingle('code', XenForo_Input::STRING);
			if (!$code)
			{
				return $this->responseError(new XenForo_Phrase('error_occurred_while_connecting_with_facebook'));
			}

			$state = $this->_input->filterSingle('state', XenForo_Input::STRING);
			$session = XenForo_Application::getSession();
			if (!$state || !$session->get('fbCsrfState') || $state !== $session->get('fbCsrfState'))
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('canonical:index')
				);
			}

			$token = XenForo_Helper_Facebook::getAccessTokenFromCode($code, $fbRedirectUri);
			$fbError = XenForo_Helper_Facebook::getFacebookRequestErrorInfo($token, 'access_token');
			if ($fbError)
			{
				return $this->responseError(new XenForo_Phrase('error_occurred_while_connecting_with_facebook'));
			}

			$fbToken = $token['access_token'];
		}

		$fbUser = XenForo_Helper_Facebook::getUserInfo($fbToken);
		$fbError = XenForo_Helper_Facebook::getFacebookRequestErrorInfo($fbUser, 'id');
		if ($fbError)
		{
			return $this->responseError(new XenForo_Phrase('error_occurred_while_connecting_with_facebook'));
		}

		$userModel = $this->_getUserModel();
		$userExternalModel = $this->_getUserExternalModel();

		$fbAssoc = $userExternalModel->getExternalAuthAssociation('facebook', $fbUser['id']);
		if ($fbAssoc && $userModel->getUserById($fbAssoc['user_id']))
		{
			$userExternalModel->updateExternalAuthAssociationExtra(
				$fbAssoc['user_id'], 'facebook', array('token' => $fbToken)
			);

			XenForo_Helper_Facebook::setUidCookie($fbUser['id']);
			XenForo_Application::getSession()->changeUserId($fbAssoc['user_id']);
			XenForo_Visitor::setup($fbAssoc['user_id']);

			$this->_getUserModel()->setUserRememberCookie($fbAssoc['user_id']);

			$redirect = XenForo_Application::getSession()->get('loginRedirect');
			XenForo_Application::getSession()->remove('loginRedirect');
			if (!$redirect)
			{
				$redirect = $this->getDynamicRedirect(false, false);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		XenForo_Helper_Facebook::setUidCookie(0);

		parent::_assertBoardActive('facebook');

		if (empty($fbUser['email']))
		{
			return $this->responseError(new XenForo_Phrase('facebook_did_not_provide_email'));
		}

		$existingUser = false;
		$emailMatch = false;
		if (XenForo_Visitor::getUserId())
		{
			$existingUser = XenForo_Visitor::getInstance();
		}
		else if ($assocUserId)
		{
			$existingUser = $userModel->getUserById($assocUserId);
		}

		if (!$existingUser)
		{
			$existingUser = $userModel->getUserByEmail($fbUser['email']);
			$emailMatch = true;
		}

		$viewName = 'XenForo_ViewPublic_Register_Facebook';
		$templateName = 'register_facebook';

		XenForo_Application::getSession()->set('fbToken', $fbToken);

		if ($existingUser)
		{
			// must associate: matching user
			return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
				'associateOnly' => true,

				'fbUser' => $fbUser,

				'existingUser' => $existingUser,
				'emailMatch' => $emailMatch,
				'redirect' => $redirect
			));
		}

		$this->_assertRegistrationActive();

		if (!empty($fbUser['birthday']))
		{
			$this->_validateBirthdayString($fbUser['birthday'], 'm/d/y');
		}

		return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
			'fbUser' => $fbUser,
			'redirect' => $redirect
		));
	}

	/**
	 * Registers a new account (or associates with an existing one) using Facebook.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFacebookRegister()
	{
		$this->_assertPostOnly();

		$fbToken = XenForo_Application::getSession()->get('fbToken');

		$fbUser = XenForo_Helper_Facebook::getUserInfo($fbToken);
		if (empty($fbUser['id']))
		{
			return $this->responseError(new XenForo_Phrase('error_occurred_while_connecting_with_facebook'));
		}

		if (empty($fbUser['email']))
		{
			return $this->responseError(new XenForo_Phrase('facebook_did_not_provide_email'));
		}

		$userExternalModel = $this->_getUserExternalModel();

		$redirect = XenForo_Application::getSession()->get('loginRedirect');
		if (!$redirect)
		{
			$redirect = $this->getDynamicRedirect(false, false);
		}

		$doAssoc = ($this->_input->filterSingle('associate', XenForo_Input::STRING)
			|| $this->_input->filterSingle('force_assoc', XenForo_Input::UINT)
		);

		if ($doAssoc)
		{
			$userId = $this->_associateExternalAccount();

			$userExternalModel->updateExternalAuthAssociation(
				'facebook', $fbUser['id'], $userId, array('token' => $fbToken)
			);
			XenForo_Helper_Facebook::setUidCookie($fbUser['id']);

			XenForo_Application::getSession()->remove('loginRedirect');
			XenForo_Application::getSession()->remove('fbToken');

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		$data = $this->_input->filter(array(
			'username'   => XenForo_Input::STRING,
			'timezone'   => XenForo_Input::STRING,
		));

		if (isset($fbUser['gender']))
		{
			switch ($fbUser['gender'])
			{
				case 'man':
				case 'male':
					$data['gender'] = 'male';
					break;

				case 'woman':
				case 'female':
					$data['gender'] = 'female';
					break;
			}
		}

		if (!empty($fbUser['birthday']))
		{
			$birthday = $this->_validateBirthdayString($fbUser['birthday'], 'm/d/y');
			if ($birthday)
			{
				$data['dob_year'] = $birthday[0];
				$data['dob_month'] = $birthday[1];
				$data['dob_day'] = $birthday[2];
			}
		}

		if (!empty($fbUser['website']))
		{
			list($website) = preg_split('/\r?\n/', $fbUser['website']);
			if ($website && Zend_Uri::check($website))
			{
				$data['homepage'] = $website;
			}
		}

		$data['email'] = $fbUser['email'];
		$data['location'] = isset($fbUser['location']['name']) ? $fbUser['location']['name'] : '';

		$writer = $this->_setupExternalUser($data);
		if (!$this->_validateBirthdayInput($writer, $birthdayError))
		{
			$writer->error($birthdayError);
		}

		$writer->advanceRegistrationUserState(false);
		$writer->save();
		$user = $writer->getMergedData();

		$avatarData = XenForo_Helper_Facebook::getUserPicture($fbToken);
		$this->_applyAvatar($user, $avatarData);

		$userExternalModel->updateExternalAuthAssociation(
			'facebook', $fbUser['id'], $user['user_id'], array('token' => $fbToken)
		);
		XenForo_Helper_Facebook::setUidCookie($fbUser['id']);

		XenForo_Application::getSession()->remove('loginRedirect');
		XenForo_Application::getSession()->remove('fbToken');

		return $this->_completeRegistration($user, array('redirect' => $redirect));
	}

	public function actionTwitter()
	{
		$assocUserId = $this->_input->filterSingle('assoc', XenForo_Input::UINT);
		$oauth = XenForo_Helper_Twitter::getOauthConsumer(
			XenForo_Link::buildPublicLink('canonical:register/twitter', null, array(
				'assoc' => ($assocUserId ? $assocUserId : false)
			))
		);
		if (!$oauth)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				$this->getDynamicRedirect()
			);
		}

		$session = XenForo_Application::getSession();
		$redirect = $this->_getExternalAuthRedirect();

		if ($this->_input->filterSingle('reg', XenForo_Input::UINT))
		{
			XenForo_Application::getSession()->set('loginRedirect', $redirect);

			try
			{
				$requestToken = $oauth->getRequestToken();
			}
			catch (Zend_Oauth_Exception $e)
			{
				return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
			}

			$session->set('twitterRequestToken', serialize($requestToken));

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				$oauth->getRedirectUrl()
			);
		}

		try
		{
			$requestToken = @unserialize($session->get('twitterRequestToken'));
			if ($requestToken)
			{
				if ($this->_input->filterSingle('denied', XenForo_Input::STRING))
				{
					return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
				}

				$accessToken = $oauth->getAccessToken($this->_input->filter(array(
					'oauth_token' => XenForo_Input::STRING,
					'oauth_verifier' => XenForo_Input::STRING
				)), $requestToken);
			}
			else
			{
				$accessToken = @unserialize($session->get('twitterAccessToken'));
				if (!$accessToken)
				{
					return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
				}
			}
		}
		catch (Zend_Service_Twitter_Exception $e)
		{
			return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
		}

		$session->remove('twitterRequestToken');
		$session->set('twitterAccessToken', serialize($accessToken));

		$credentials = XenForo_Helper_Twitter::getUserFromToken($accessToken);
		if (!$credentials)
		{
			return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
		}
		$userId = $credentials['id_str'];

		$userModel = $this->_getUserModel();
		$userExternalModel = $this->_getUserExternalModel();

		$redirect = XenForo_Application::getSession()->get('loginRedirect');

		$twitterAssoc = $userExternalModel->getExternalAuthAssociation('twitter', $userId);
		if ($twitterAssoc && $userModel->getUserById($twitterAssoc['user_id']))
		{
			$userExternalModel->updateExternalAuthAssociationExtra(
				$twitterAssoc['user_id'], 'twitter', array(
					'token' => $accessToken->getToken(),
					'secret' => $accessToken->getTokenSecret()
				)
			);

			XenForo_Application::getSession()->changeUserId($twitterAssoc['user_id']);
			XenForo_Visitor::setup($twitterAssoc['user_id']);

			$this->_getUserModel()->setUserRememberCookie($twitterAssoc['user_id']);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect ? $redirect : $this->getDynamicRedirect(false, false)
			);
		}

		parent::_assertBoardActive('twitter');

		$session->set('twitterCredentials', serialize($credentials));

		$viewName = 'XenForo_ViewPublic_Register_Twitter';
		$templateName = 'register_twitter';

		$existingUser = XenForo_Visitor::getUserId() ? XenForo_Visitor::getInstance() : false;
		if ($existingUser)
		{
			// must associate: matching user
			return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
				'associateOnly' => true,
				'existingUser' => $existingUser,
				'redirect' => $redirect
			));
		}

		$this->_assertRegistrationActive();

		return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
			'redirect' => $redirect
		));
	}

	/**
	 * Registers a new account (or associates with an existing one) using Twitter.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionTwitterRegister()
	{
		$this->_assertPostOnly();

		$session = XenForo_Application::getSession();
		$accessToken = @unserialize($session->get('twitterAccessToken'));
		$credentials = @unserialize($session->get('twitterCredentials'));

		if (!$accessToken || !$credentials)
		{
			return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
		}

		$userExternalModel = $this->_getUserExternalModel();

		$doAssoc = ($this->_input->filterSingle('associate', XenForo_Input::STRING)
			|| $this->_input->filterSingle('force_assoc', XenForo_Input::UINT)
		);

		$redirect = XenForo_Application::getSession()->get('loginRedirect');
		if (!$redirect)
		{
			$redirect = $this->getDynamicRedirect(false, false);
		}

		if ($doAssoc)
		{
			$userId = $this->_associateExternalAccount();

			$userExternalModel->updateExternalAuthAssociation(
				'twitter', $credentials['id_str'], $userId, array(
					'token' => $accessToken->getToken(),
					'secret' => $accessToken->getTokenSecret()
				)
			);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		$options = XenForo_Application::getOptions();

		$data = $this->_input->filter(array(
			'username'   => XenForo_Input::STRING,
			'email'      => XenForo_Input::STRING,
			'timezone'   => XenForo_Input::STRING,
			'dob_day'    => XenForo_Input::UINT,
			'dob_month'  => XenForo_Input::UINT,
			'dob_year'   => XenForo_Input::UINT,
		));
		$data['location'] = $credentials['location'];

		if (!empty($credentials['entities']['url']['urls'][0]['expanded_url']))
		{
			$website = $credentials['entities']['url']['urls'][0]['expanded_url'];
			if (Zend_Uri::check($website))
			{
				$data['homepage'] = $website;
			}
		}

		$writer = $this->_setupExternalUser($data);

		if (!$this->_validateBirthdayInput($writer, $birthdayError))
		{
			$writer->error($birthdayError);
		}

		$writer->advanceRegistrationUserState();
		$writer->save();
		$user = $writer->getMergedData();

		if (!empty($credentials['profile_image_url']))
		{
			try
			{
				// get the original size
				$url = str_replace('_normal', '', $credentials['profile_image_url']);
				$request = XenForo_Helper_Http::getClient($url)->request();
				$avatarData = $request->getBody();
			}
			catch (Exception $e)
			{
				$avatarData = '';
			}
			$this->_applyAvatar($user, $avatarData);
		}

		$userExternalModel->updateExternalAuthAssociation(
			'twitter', $credentials['id_str'], $user['user_id'], array(
				'token' => $accessToken->getToken(),
				'secret' => $accessToken->getTokenSecret()
			)
		);

		if ($user['user_state'] == 'email_confirm')
		{
			$this->_getUserConfirmationModel()->sendEmailConfirmation($user);
		}

		return $this->_completeRegistration($user);
	}

	public function actionGoogle()
	{
		$code = $this->_input->filterSingle('code', XenForo_Input::STRING);
		$options = XenForo_Application::getOptions();
		$session = XenForo_Application::getSession();
		$redirect = $this->_getExternalAuthRedirect();

		if (!$options->googleClientId)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				$this->getDynamicRedirect()
			);
		}

		$csrf = $this->_input->filterSingle('csrf', XenForo_Input::STRING);
		if ($csrf !== $session->get('sessionCsrf'))
		{
			return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
		}

		$client = XenForo_Helper_Http::getClient('https://accounts.google.com/o/oauth2/token');
		$client->setParameterPost(array(
			'code' => $code,
			'client_id' => $options->googleClientId,
			'client_secret' => $options->googleClientSecret,
			'redirect_uri' => 'postmessage',
			'grant_type' => 'authorization_code'
		));
		$result = $client->request('POST');

		$body = @json_decode($result->getBody(), true);
		if (!$body || !empty($body['error']))
		{
			$credentials = $session->get('googleCredentials');
			if (!$credentials)
			{
				return $this->responseError(new XenForo_Phrase('error_occurred_when_connecting_to_google'));
			}
		}
		else
		{
			$idTokenParts = explode('.', $body['id_token']);

			$basicInfo = json_decode(base64_decode($idTokenParts[1]), true);
			if (!$basicInfo || empty($basicInfo['sub']))
			{
				return $this->responseError(new XenForo_Phrase('error_occurred_when_connecting_to_google'));
			}

			$credentials = array(
				'extra' => array(
					'access_token' => $body['access_token'],
					'expiry' => XenForo_Application::$time + $body['expires_in'],
					'refresh_token' => isset($body['refresh_token']) ? $body['refresh_token'] : null
				),
				'basic' => $basicInfo
			);
		}

		$basicInfo = $credentials['basic'];
		$userId = $basicInfo['sub'];

		$userModel = $this->_getUserModel();
		$userExternalModel = $this->_getUserExternalModel();

		$googleAssoc = $userExternalModel->getExternalAuthAssociation('google', $userId);
		if ($googleAssoc && $userModel->getUserById($googleAssoc['user_id']))
		{
			$existingExtra = unserialize($googleAssoc['extra_data']);
			if (!$credentials['extra']['refresh_token'] && !empty($existingExtra['refresh_token']))
			{
				$credentials['extra']['refresh_token'] = $existingExtra['refresh_token'];
			}

			$userExternalModel->updateExternalAuthAssociationExtra(
				$googleAssoc['user_id'], 'google', $credentials['extra']
			);

			XenForo_Application::getSession()->changeUserId($googleAssoc['user_id']);
			XenForo_Visitor::setup($googleAssoc['user_id']);

			$this->_getUserModel()->setUserRememberCookie($googleAssoc['user_id']);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		if (empty($basicInfo['email']) || empty($basicInfo['email_verified']) || $basicInfo['email_verified'] != 'true')
		{
			return $this->responseError(new XenForo_Phrase('you_must_have_verified_email_to_register_via_google'));
		}

		parent::_assertBoardActive('google');

		if (empty($credentials['user']))
		{
			$client = XenForo_Helper_Http::getClient('https://www.googleapis.com/plus/v1/people/me');
			$client->setParameterGet('access_token', $credentials['extra']['access_token']);
			$response = $client->request('GET');
			$userInfo = json_decode($response->getBody(), true);
			$credentials['user'] = $userInfo;
		}

		$session->set('googleCredentials', $credentials);

		$viewName = 'XenForo_ViewPublic_Register_Google';
		$templateName = 'register_google';

		$emailMatch = false;
		if (XenForo_Visitor::getUserId())
		{
			$existingUser = XenForo_Visitor::getInstance();
		}
		else
		{
			$existingUser = $userModel->getUserByEmail($basicInfo['email']);
			$emailMatch = (bool)$existingUser;
		}

		XenForo_Application::getSession()->set('loginRedirect', $redirect);

		if ($existingUser)
		{
			// must associate: matching user
			return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
				'associateOnly' => true,
				'existingUser' => $existingUser,
				'emailMatch' => $emailMatch,
				'redirect' => $redirect
			));
		}

		$this->_assertRegistrationActive();

		return $this->_getExternalRegisterFormResponse($viewName, $templateName, array(
			'redirect' => $redirect,
			'credentials' => $credentials,
			'showDob' => empty($credentials['user']['birthday'])
		));
	}

	/**
	 * Registers a new account (or associates with an existing one) using Google.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionGoogleRegister()
	{
		$this->_assertPostOnly();

		$session = XenForo_Application::getSession();
		$credentials = $session->get('googleCredentials');

		if (!$credentials)
		{
			return $this->responseError(new XenForo_Phrase('unexpected_error_occurred'));
		}

		$userExternalModel = $this->_getUserExternalModel();

		$doAssoc = ($this->_input->filterSingle('associate', XenForo_Input::STRING)
			|| $this->_input->filterSingle('force_assoc', XenForo_Input::UINT)
		);

		$redirect = XenForo_Application::getSession()->get('loginRedirect');
		if (!$redirect)
		{
			$redirect = $this->getDynamicRedirect(false, false);
		}

		if ($doAssoc)
		{
			$userId = $this->_associateExternalAccount();

			$userExternalModel->updateExternalAuthAssociation(
				'google', $credentials['basic']['sub'], $userId, $credentials['extra']
			);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		$googleUser = $credentials['user'];

		$data = $this->_input->filter(array(
			'username'   => XenForo_Input::STRING,
			'timezone'   => XenForo_Input::STRING,
			'dob_day'    => XenForo_Input::UINT,
			'dob_month'  => XenForo_Input::UINT,
			'dob_year'   => XenForo_Input::UINT,
		));
		$data['email'] = $credentials['basic']['email'];

		if (!empty($googleUser['currentLocation']))
		{
			$data['location'] = $googleUser['currentLocation'];
		}
		else if (!empty($googleUser['placesLived']) && is_array($googleUser['placesLived']))
		{
			foreach ($googleUser['placesLived'] AS $place)
			{
				if (!empty($place['primary']))
				{
					$data['location'] = $place['value'];
					break;
				}
			}
		}

		if (isset($googleUser['gender']))
		{
			switch ($googleUser['gender'])
			{
				case 'male':
				case 'female':
					$data['gender'] = $googleUser['gender'];
					break;
			}
		}

		if (!empty($googleUser['birthday']))
		{
			$birthday = $this->_validateBirthdayString($googleUser['birthday'], 'y-m-d');
			if ($birthday)
			{
				$data['dob_year'] = $birthday[0];
				$data['dob_month'] = $birthday[1];
				$data['dob_day'] = $birthday[2];
			}
		}

		$writer = $this->_setupExternalUser($data);

		if (!$this->_validateBirthdayInput($writer, $birthdayError))
		{
			$writer->error($birthdayError);
		}

		$writer->advanceRegistrationUserState(false);
		$writer->save();
		$user = $writer->getMergedData();

		if (!empty($googleUser['image']['url']))
		{
			try
			{
				// get the original size
				$url = preg_replace('/(\?|&)sz=\d+/', '', $googleUser['image']['url']);
				$request = XenForo_Helper_Http::getClient($url)->request();
				$avatarData = $request->getBody();
			}
			catch (Exception $e)
			{
				$avatarData = '';
			}
			$this->_applyAvatar($user, $avatarData);
		}

		$userExternalModel->updateExternalAuthAssociation(
			'google', $credentials['basic']['sub'], $user['user_id'], $credentials['extra']
		);

		return $this->_completeRegistration($user);
	}

	protected function _getExternalRegisterFormResponse($viewName, $templateName, array $extraParams = array())
	{
		$options = XenForo_Application::getOptions();

		$viewParams = $extraParams + array(
			'customFields' => $this->_getFieldModel()->prepareUserFields(
				$this->_getFieldModel()->getUserFields(array('registration' => true)),
				true
			),

			'timeZones' => XenForo_Helper_TimeZone::getTimeZones(),
			'tosUrl' => XenForo_Dependencies_Public::getTosUrl(),

			'dobRequired' => $options->get('registrationSetup', 'requireDob'),
		);
		return $this->responseView($viewName, $templateName, $viewParams, $this->_getRegistrationContainerParams());
	}

	protected function _setupExternalUser(array $data)
	{
		$this->_assertRegistrationActive();

		if (XenForo_Dependencies_Public::getTosUrl() && !$this->_input->filterSingle('agree', XenForo_Input::UINT))
		{
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase('you_must_agree_to_terms_of_service'))
			);
		}

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');

		$options = XenForo_Application::get('options');
		if ($options->registrationDefaults)
		{
			$writer->bulkSet($options->registrationDefaults, array('ignoreInvalidFields' => true));
		}
		$writer->bulkSet($data);

		$writer->set('user_group_id', XenForo_Model_User::$defaultRegisteredGroupId);
		$writer->set('language_id', XenForo_Visitor::getInstance()->get('language_id'));

		$customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
		$customFieldsShown = array_keys($this->_getFieldModel()->getUserFields(array('registration' => true)));
		$writer->setCustomFields($customFields, $customFieldsShown);

		$auth = XenForo_Authentication_Abstract::create('XenForo_Authentication_NoPassword');
		$writer->set('scheme_class', $auth->getClassName());
		$writer->set('data', $auth->generate(''), 'xf_user_authenticate');

		return $writer;
	}

	protected function _validateBirthdayString($birthday, $format)
	{
		$format = strtr(preg_quote($format, '#'), array(
			'm' => '(?P<month>\d{1,2})',
			'd' => '(?P<day>\d{1,2})',
			'y' => '(?P<year>\d{1,4})',
		));
		if (!preg_match('#^' . $format . '$#i', $birthday, $match))
		{
			return false;
		}

		$month = intval($match['month']);
		$day = intval($match['day']);
		$year = intval($match['year']);

		if (!$year)
		{
			return false;
		}

		$userAge = $this->_getUserProfileModel()->calculateAge($year, $month, $day);
		$options = XenForo_Application::getOptions();
		if ($userAge < intval($options->get('registrationSetup', 'minimumAge')))
		{
			throw $this->responseException(
				$this->responseError(new XenForo_Phrase('sorry_you_too_young_to_create_an_account'))
			);
		}

		return array($year, $month, $day);
	}

	protected function _validateBirthdayInput(XenForo_DataWriter_User $writer, &$error)
	{
		$options = XenForo_Application::getOptions();
		$error = false;

		$writer->checkDob();

		if (!$options->get('registrationSetup', 'requireDob'))
		{
			return true;
		}

		// dob required
		if (!$writer->get('dob_day') || !$writer->get('dob_month') || !$writer->get('dob_year'))
		{
			$error = new XenForo_Phrase('please_enter_valid_date_of_birth');
			return false;
		}
		else
		{
			$userAge = $this->_getUserProfileModel()->getUserAge($writer->getMergedData(), true);
			if ($userAge < 1)
			{
				$error = new XenForo_Phrase('please_enter_valid_date_of_birth');
				return false;
			}
			else if ($userAge < intval($options->get('registrationSetup', 'minimumAge')))
			{
				// TODO: set a cookie to prevent re-registration attempts
				$error = new XenForo_Phrase('sorry_you_too_young_to_create_an_account');
				return false;
			}
		}

		return true;
	}

	protected function _getExternalAuthRedirect()
	{
		$redirect = XenForo_Link::convertUriToAbsoluteUri($this->getDynamicRedirect(), true);
		$baseDomain = preg_replace(
			'#^([a-z]+://[^/]+).*$#i',
			'$1',
			XenForo_Link::convertUriToAbsoluteUri(XenForo_Application::getOptions()->boardUrl, true)
		);
		if (strpos($redirect, $baseDomain) !== 0)
		{
			$redirect = XenForo_Link::buildPublicLink('canonical:index');
		}

		return $redirect;
	}

	protected function _associateExternalAccount()
	{
		$associate = $this->_input->filter(array(
			'associate_login' => XenForo_Input::STRING,
			'associate_password' => XenForo_Input::STRING
		));

		$loginModel = $this->_getLoginModel();
		$userModel = $this->_getUserModel();

		if ($loginModel->requireLoginCaptcha($associate['associate_login']))
		{
			throw $this->responseException(
				$this->responseError(
					new XenForo_Phrase('your_account_has_temporarily_been_locked_due_to_failed_login_attempts')
				)
			);
		}

		$userId = $userModel->validateAuthentication(
			$associate['associate_login'], $associate['associate_password'], $error
		);
		if (!$userId)
		{
			$loginModel->logLoginAttempt($associate['associate_login']);
			throw $this->responseException(
				$this->responseError($error)
			);
		}

		XenForo_Application::getSession()->changeUserId($userId);
		XenForo_Visitor::setup($userId);

		$this->_getUserModel()->setUserRememberCookie($userId);

		return $userId;
	}

	protected function _applyAvatar(array $user, $data)
	{
		$success = false;
		if (!$data || !$user['user_id'])
		{
			return false;
		}

		$avatarFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
		if ($avatarFile)
		{
			file_put_contents($avatarFile, $data);

			try
			{
				$user = array_merge($user,
					$this->getModelFromCache('XenForo_Model_Avatar')->applyAvatar($user['user_id'], $avatarFile)
				);
				$success = true;
			}
			catch (XenForo_Exception $e) {}

			@unlink($avatarFile);
		}

		return $success;
	}

	protected function _assertRegistrationActive()
	{
		if (!XenForo_Application::get('options')->get('registrationSetup', 'enabled'))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('new_registrations_currently_not_being_accepted')
			));
		}
	}

	protected function _assertBoardActive($action)
	{
		switch (strtolower($action))
		{
			case 'facebook':
			case 'twitter':
			case 'google':
				break;

			default:
				parent::_assertBoardActive($action);
		}
	}

	protected function _assertCorrectVersion($action)
	{
		switch (strtolower($action))
		{
			case 'facebook':
			case 'twitter':
			case 'google':
				break;

			default:
				parent::_assertCorrectVersion($action);
		}
	}

	protected function _assertViewingPermissions($action) {}

	/**
	 * Session activity details.
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('registering');
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	/**
	 * @return XenForo_Model_UserProfile
	 */
	protected function _getUserProfileModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserProfile');
	}

	/**
	 * @return XenForo_Model_UserField
	 */
	protected function _getFieldModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserField');
	}

	/**
	 * @return XenForo_Model_UserConfirmation
	 */
	protected function _getUserConfirmationModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserConfirmation');
	}

	/**
	 * @return XenForo_Model_UserExternal
	 */
	protected function _getUserExternalModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserExternal');
	}

	/**
	 * @return XenForo_Model_Login
	 */
	protected function _getLoginModel()
	{
		return $this->getModelFromCache('XenForo_Model_Login');
	}
}