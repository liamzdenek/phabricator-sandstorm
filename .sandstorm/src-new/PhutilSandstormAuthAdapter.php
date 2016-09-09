<?php

final class PhutilSandstormAuthAdapter extends PhutilAuthAdapter {

  private $pageURIPattern;

  // Specific User Request Information.
  private $ssUsername;
  private $ssUserId;
  private $ssTabId;
  private $ssPermissions;
  private $ssHandle;
  private $ssPicture;
  private $ssPronouns;

  //
  // Implementation of PhutilAuthAdapter interface.
  // User information getters.
  //

  public function getAccountID() {
    return $this->ssUserId;
  }

  public function getAdapterType() {
    return 'sandstorm';
  }

  public function getAdapterDomain() {
    return 'self';
  }

  public function getAccountEmail() {
    return $this->ssUserId."@example.com";
  }

  public function getAccountName() {
    return $this->ssUserId;
  }

  public function getAccountURI() {
    if (strlen($this->pageURIPattern)) {
      return sprintf($this->pageURIPattern, $this->ssUserId);
    }
    return null;
  }

  public function getAccountImageURI() {
    return $this->ssPicture;
  }

  public function getAccountRealName() {
    return $this->ssUsername;
  }

  //
  // Extraction of user information from request headers.
  //
  public function getHeaderNames() {
    return array(
	"X-Sandstorm-Username",
	"X-Sandstorm-User-Id",
	"X-Sandstorm-Tab-Id",
	"X-Sandstorm-Permissions",
	"X-Sandstorm-Preferred-Handle",
	"X-Sandstorm-User-Picture",
	"X-Sandstorm-User-Pronouns",
    );
  }

  public function getPermissions() {
    return $this->ssPermissions;
  }

  public function hasPermission($perm) {
    return in_array($perm, $this->getPermissions());
  }

  public function setUserDataFromRequest($headers) {

    $this->ssUsername = urldecode($headers["X-Sandstorm-Username"]);
    $this->ssUserId = $headers["X-Sandstorm-User-Id"];
    $this->ssTabId = $headers["X-Sandstorm-Tab-Id"];
    $this->ssPermissions = explode(",",$headers["X-Sandstorm-Permissions"]);
    $this->ssHandle = $headers["X-Sandstorm-Preferred-Handle"];
    $this->ssPicture = $headers["X-Sandstorm-User-Picture"];
    //throw new Exception(print_r($this, true));

    //$this->ssPronouns = $headers["X-Sandstorm-User-Pronouns"];

    if (!strlen($this->ssUserId)) {
      return false;
    }

    return $this;
  }
}
