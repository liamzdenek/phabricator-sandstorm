<?php

final class PhabricatorSandstormAuthProvider extends PhabricatorAuthProvider {
	private $adapter;

  public function getProviderName() {
    return pht('Sandstorm');
  }

  public function getConfigurationHelp() {
    return pht("(WARNING) Must be running within sandstorm instance");
  }

  public function renderConfigurationFooter() {
    $hashers = PhabricatorPasswordHasher::getAllHashers();
    $hashers = msort($hashers, 'getStrength');
    $hashers = array_reverse($hashers);

    return id(new PHUIObjectBoxView());
  }

  public function getDescriptionForCreate() {
    return pht(
      'Allow users to login or register using a username and password.');
  }

  public function getAdapter() {
    if (!$this->adapter) {
      $adapter = new PhutilSandstormAuthAdapter();
			$adapter->setUserDataFromRequest(getallheaders());
      $this->adapter = $adapter;
    }
    return $this->adapter;
  }

  public function getLoginOrder() {
    // Make sure username/password appears first if it is enabled.
    return '100-'.$this->getProviderName();
  }

  public function shouldAllowAccountLink() {
    return false;
  }

  public function shouldAllowAccountUnlink() {
    return false;
  }

  public function isDefaultRegistrationProvider() {
    return true;
  }

  public function buildLoginForm(
    PhabricatorAuthStartController $controller) {
		//return $this->processLoginRequest($controller);
    
    $request = $controller->getRequest();

    return $this->renderPasswordLoginForm($request);
  }

  public function buildInviteForm(
    PhabricatorAuthStartController $controller) {
    $request = $controller->getRequest();
    $viewer = $request->getViewer();

    $dialog = id(new AphrontDialogView());
    return $dialog;
  }

  public function buildLinkForm(
    PhabricatorAuthLinkController $controller) {
    throw new Exception(pht("Password providers can't be linked."));
  }

  private function renderPasswordLoginForm(
    AphrontRequest $request,
    $require_captcha = false,
    $captcha_valid = false) {

    $viewer = $request->getUser();

		$script = <<<SCRIPT
function getForm(el) {
	var cur = el;
	while(cur){
		if(cur.tagName == "FORM") {
			return cur;
		}
		cur = cur.parentNode;
	}
	return null;
}
getForm(document.currentScript).submit();
SCRIPT;

    $dialog = id(new AphrontDialogView())
      ->setSubmitURI($this->getLoginURI())
      ->setUser($viewer)
      ->setTitle(pht('Login to Phabricator'))
      ->addSubmitButton(pht('Login'))
			->appendChild(
					id(new AphrontArbitraryScript())
					->setScript($script)
			)
		;

    $errors = array();
    if ($require_captcha && !$captcha_valid) {
      if (AphrontFormRecaptchaControl::hasCaptchaResponse($request)) {
        $e_captcha = pht('Invalid');
        $errors[] = pht('CAPTCHA was not entered correctly.');
      } else {
        $e_captcha = pht('Required');
        $errors[] = pht(
          'Too many login failures recently. You must '.
          'submit a CAPTCHA with your login request.');
      }
    } else if ($request->isHTTPPost()) {
      // NOTE: This is intentionally vague so as not to disclose whether a
      // given username or email is registered.
      $e_user = pht('Invalid');
      $e_pass = pht('Invalid');
      $errors[] = pht('Username or password are incorrect.');
    }

    if ($errors) {
      $errors = id(new PHUIInfoView())->setErrors($errors);
    }

    $form = id(new PHUIFormLayoutView())
      ->setFullWidth(true)
			->appendChild(
				phutil_tag(
        'input',
        array(
          'type'  => 'hidden',
          'name'  => "username",
          'value' => "password",
        ))
			)
    ;

    if ($require_captcha) {
        $form->appendChild(
          id(new AphrontFormRecaptchaControl())
            ->setError($e_captcha));
    }

    $dialog->appendChild($form);

    return $dialog;
  }

	// lifted from src/applications/auth/controller/PhabricatorAuthRegisterController.php
	public function loadDefaultAccount() {
    $providers = PhabricatorAuthProvider::getAllEnabledProviders();
    $account = null;
    $provider = null;
    $response = null;
    foreach ($providers as $key => $candidate_provider) {
      if (!$candidate_provider->shouldAllowRegistration()) {
        unset($providers[$key]);
        continue;
      }
      if (!$candidate_provider->isDefaultRegistrationProvider()) {
        unset($providers[$key]);
      }
    }
    if (!$providers) {
      $response = $this->renderError(
        pht(
          'There are no configured default registration providers.'));
      return array($account, $provider, $response);
    } else if (count($providers) > 1) {
      $response = $this->renderError(
        pht('There are too many configured default registration providers.'));
      return array($account, $provider, $response);
    }
    $provider = head($providers);
    $account = $provider->getDefaultExternalAccount();
    return array($account, $provider, $response);
	}

  public function processLoginRequest(
    PhabricatorAuthLoginController $controller) {

    $request = $controller->getRequest();
    $viewer = $request->getUser();

    $response = null;
    $account = null;
    $log_user = null;

	$adapter = $this->getAdapter();

    $user = null;
    $user_mapper = id(new UserMapper())->loadOneWhere(
      'ss_id = %s',
      $adapter->getAccountId());

    if($user_mapper) {
        $user = id(new PhabricatorUser())->loadOneWhere('id = %s', $user_mapper->getId());
    }

		if(!$user) {
			// user does not exist
			list($account, $provider, $response) = $this->loadDefaultAccount();

			$user = new PhabricatorUser();

			$user->setUsername($adapter->getAccountName());
			$user->setRealname($adapter->getAccountRealName());
			$user->setIsApproved(1);
			$user->openTransaction();
			{
				$editor = id(new PhabricatorUserEditor())->setActor($user);
				$editor->createNewUser($user, id(new PhabricatorUserEmail())->setAddress("fake+".$adapter->getAccountID()."@example.com")->setIsVerified(1), true);
				if($adapter->hasPermission("admin")) {
					$editor->makeAdminUser($user, true);
				}
				$account->setUserPHID($user->getPHID());
				$provider->willRegisterAccount($account);
				$account->save();

                id(new UserMapper())
                    ->setss_id($adapter->getAccountId())
                    ->setphabricator_user_id($user->getId())
                    ->save();
			}
			$user->saveTransaction();
			//throw new Exception("TODO, create user " . $this->getAdapter()->getAccountID());
		} else {
			// user already exists, update perms
			$user->setUsername($adapter->getAccountName());
			$user->setRealname($adapter->getAccountRealName());
			$user->openTransaction();
			{
				$editor = id(new PhabricatorUserEditor())->setActor($user);
				if($adapter->hasPermission("admin")) {
                    //throw new Exception("updating user perms");
					$editor->makeAdminUser($user, true);
				} else {
                    $editor->makeAdminUser($user, false);
                }
			}
			$user->saveTransaction();
		}

	if ($user) {
      $account = $this->loadOrCreateAccount($user->getPHID());
      $log_user = $user;
    }

    if (!$account) {
      $response = $controller->buildProviderPageResponse(
        $this,
        $this->renderPasswordLoginForm(
          $request,
          false,
          true));
    }

    //throw new Exception("Account: ".print_r($account, true));

    return array($account, $response);
  }

  public function shouldRequireRegistrationPassword() {
    return true;
  }

  public function getDefaultExternalAccount() {
    $adapter = $this->getAdapter();

    return id(new PhabricatorExternalAccount())
      ->setAccountType($adapter->getAdapterType())
      ->setAccountDomain($adapter->getAdapterDomain());
  }

  protected function willSaveAccount(PhabricatorExternalAccount $account) {
    parent::willSaveAccount($account);
    $account->setUserPHID($account->getAccountID());
  }

  public function willRegisterAccount(PhabricatorExternalAccount $account) {
    parent::willRegisterAccount($account);
    $account->setAccountID($account->getUserPHID());
  }

  public static function getPasswordProvider() {
    $providers = self::getAllEnabledProviders();

    foreach ($providers as $provider) {
      if ($provider instanceof PhabricatorPasswordAuthProvider) {
        return $provider;
      }
    }

    return null;
  }

  public function willRenderLinkedAccount(
    PhabricatorUser $viewer,
    PHUIObjectItemView $item,
    PhabricatorExternalAccount $account) {
    return;
  }

  public function shouldAllowAccountRefresh() {
    return false;
  }

  public function shouldAllowEmailTrustConfiguration() {
    return false;
  }
}
