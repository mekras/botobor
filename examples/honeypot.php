<?php

require '../src/libbotobor.php';

Botobor::setSecret('secret_key');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	$validator = new BotoborFormValidator();
	var_dump($validator->validate());
	var_dump($_POST['name']);

} else {

	$script = basename(__FILE__);

	$html = <<<EOT
<form action="$script" method="post">
	<div><input type="text" name="name" /></div>
	<div><input type="submit" /></div>
</form>
EOT;

	$form = new BotoborHtmlForm($html);
	$form->setHoneypot('name');

	echo $form->produce();

}
