<?php

final class AphrontArbitraryScript
  extends AphrontView
  implements AphrontResponseProducerInterface {

	private $script = "";

	public function setScript($script) {
		$this->script = $script;
		return $this;
	}

	public function render() {
		return phutil_safe_html(sprintf(
			'<script adsf type="text/javascript">%s</script>',
			$this->script
		));
	}

	public function produceAphrontResponse() {
		return id(new AphrontDialogResponse())
			->setDialog($this);
	}
}
