<?php
require_once("../models/config.php");

$fileNameID = $_POST['fileNameID'];
echo getWidgetConfig($fileNameID);
?>
