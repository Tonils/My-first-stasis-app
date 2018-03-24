<?php

require_once "../vendor/autoload.php"; 

include_once "class.basicStasisApplication.php";

$basicAriClient = new BasicStasisApplication("hello-world-auto");

$basicAriClient->stasisLogger->info("Starting Stasis Program... Waiting for handshake...");
$basicAriClient->StasisAppEventHandler();

$basicAriClient->stasisLogger->info("Initializing Handlers... Waiting for handshake...");
$basicAriClient->StasisAppConnectionHandlers();

$basicAriClient->stasisLogger->info("Connecting... Waiting for handshake...");
$basicAriClient->execute();

?>
