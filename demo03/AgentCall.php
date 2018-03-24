#!/usr/bin/php
<?php

    require_once "../vendor/autoload.php";

    class AgentCall
    {

        private $ariEndpoint;
        private $stasisClient;
        private $stasisLoop;
        private $phpariObject;
        private $stasisChannelID;
        private $dtmfSequence = "";

        public $stasisLogger;

        public function __construct($appname = NULL)
        {
            try {
                if (is_null($appname))
                    throw new Exception("[" . __FILE__ . ":" . __LINE__ . "] Stasis application name must be defined!", 500);

                $this->phpariObject = new phpari($appname);

                $this->ariEndpoint  = $this->phpariObject->ariEndpoint;
                $this->stasisClient = $this->phpariObject->stasisClient;
                $this->stasisLoop   = $this->phpariObject->stasisLoop;
                $this->stasisLogger = $this->phpariObject->stasisLogger;
                $this->stasisEvents = $this->phpariObject->stasisEvents;
            } catch (Exception $e) {
                echo $e->getMessage();
                exit(99);
            }
        }

        // process stasis events
        public function StasisAppEventHandler()
        {
            $this->stasisEvents->on('StasisStart', function ($event) {
                $this->stasisLogger->notice("Event received: StasisStart");
		include "db_info.php";
                $db = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or die($db->connect_error);
                $sql = "UPDATE agents SET call_id = '{$event->channel->id}', status = 'A' WHERE exten = '{$event->args[0]}'";
		print "{$sql}\n";
                $db->query($sql) or die($db->error);
            });
	    $this->stasisEvents->on('StasisEnd', function ($event) {
                $this->stasisLogger->notice("Event received: StasisEnd");
		include "db_info.php";
                $db = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or die($db->connect_error);
                $sql = "UPDATE agents SET status = 'R', call_id = '' WHERE call_id = '{$event->channel->id}'";
                $db->query($sql) or die($db->error);
                $this->phpariObject->channels()->channel_delete($this->stasisChannelID);
            });


        }

        public function StasisAppConnectionHandlers()
        {
            try {
                $this->stasisClient->on("request", function ($headers) {
                    $this->stasisLogger->notice("Request received!");
                });

                $this->stasisClient->on("handshake", function () {
                    $this->stasisLogger->notice("Handshake received!");
                });

                $this->stasisClient->on("message", function ($message) {
                    $event = json_decode($message->getData());
                    $this->stasisLogger->notice('Received event: ' . $event->type . print_r($event, true));
                    $this->stasisEvents->emit($event->type, array($event));
                });

            } catch (Exception $e) {
                echo $e->getMessage();
                exit(99);
            }
        }

        public function execute()
        {
            try {
                $this->stasisClient->open();
                $this->stasisLoop->run();
            } catch (Exception $e) {
                echo $e->getMessage();
                exit(99);
            }
        }

    }

    $basicAriClient = new AgentCall("agentCall");

    $basicAriClient->stasisLogger->info("Starting Stasis Program... Waiting for handshake...");
    $basicAriClient->StasisAppEventHandler();

    $basicAriClient->stasisLogger->info("Initializing Handlers... Waiting for handshake...");
    $basicAriClient->StasisAppConnectionHandlers();

    $basicAriClient->stasisLogger->info("Connecting... Waiting for handshake...");
    $basicAriClient->execute();

    exit(0);
?>
