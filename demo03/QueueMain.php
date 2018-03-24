<?php

require_once("../vendor/autoload.php");
$ariConnector = new phpari('monkey-business');
$channels = new channels($ariConnector);

include_once "db_info.php";
$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or die($db->connect_error);

$i = 0;
while (true) {
	$sql1 = "SELECT * FROM agents WHERE status = 'R'";
	$res1 = $db->query($sql1) or die($db->error);
	if ($res1->num_rows > 0) { // there are agents, look for calls
		$sql2 = "SELECT * FROM calls WHERE status = 'W'";
		$res2 = $db->query($sql2) or die($db->error);
		if ($res2->num_rows > 0) { // there are calls, look for an agent
			$call = $res2->fetch_object();
			$agent = $res1->fetch_object();
			print "Connect {$call->call_id} to {$agent->exten}\n";

			print "Originate Call to PJSIP/{$agent->exten}\n";
			$originate_data = array(
				'app' => 'agentCall',
				'appArgs' => $agent->exten,
			);
                	$originate_result = $channels->channel_originate( "PJSIP/{$agent->exten}", NULL, $originate_data);
        		print_r($originate_result);
                	$agent_call_id = $originate_result['id'];

			$sql3 = "SELECT * FROM agents WHERE id = {$agent->id} AND status = 'A'";
			do {
				sleep(1);
				$res3 = $db->query($sql3) or die($db->error);
			} while ($res3->num_rows == 0);

                	print "Create a bridge\n";
                	$bridge_id = 'Q' . time() . $i;
                	$bridge_result = $ariConnector->bridges()->bridge_create( 'mixing', $bridge_id, "myBridge_{$bridge_id}");
        		print_r($bridge_result);

                	print "Stop Moh on {$call->call_id}\n";
                	$moh_result = $ariConnector->channels()->channel_moh_stop($call->call_id);
        		print_r($moh_result);

                	print "Add {$agent_call_id} to bridge\n";
                	$addAgent_result = $ariConnector->bridges()->bridge_addchannel($bridge_id, $agent_call_id, 'role');
        		print_r($addAgent_result);

                	print "Add {$call->call_id} to bridge\n";
                	$addCaller_result = $ariConnector->bridges()->bridge_addchannel($bridge_id, $call->call_id, 'role');
        		print_r($addAgent_result);

			$sql3 = "UPDATE calls SET status = 'C' WHERE id = {$call->id}";
			$db->query($sql3) or die($db->error);
			$sql3 = "UPDATE agents SET status = 'C' WHERE id = {$agent->id}";
			$db->query($sql3) or die($db->error);
		} else {
			print "No calls\n";
		}
	} else {
		print "No agents\n";
	}
	sleep (1);
}
?>
