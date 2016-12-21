#!/usr/local/bin/php

<?php

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;

require_once 'vendor/autoload.php';

$ini_array = parse_ini_file("settings.ini", TRUE);

$host = $ini_array['jira']['host'];
$username = $ini_array['jira']['username'];
$password = $ini_array['jira']['password'];
$jql = $ini_array['jira']['jql'];

if( count($argv) > 1 ) {
    $jql .= " AND created > $argv[1]"; 
}
if( count($argv) > 2 ) {
    $jql .= " AND created < $argv[2]";
}

function getMaxReactionTime( $priorityID ) {
    global $ini_array;
    return $ini_array['sla']['maxReactionTime'][intval($priorityID)];
}

function getSLAData($issue) {

    $SLAData = [];

    $priority = $issue->getPriority();
    $timeInStatusField = $issue->get('Time in status');

    $maxReactionTime = intval(getMaxReactionTime($priority['id']));
    $SLAData['issue'] = $issue->getKey();
    $SLAData['priority'] = $priority['name'];
    $SLAData['reactionTime'] = '';
    $SLAData['maxReactionTime'] = $maxReactionTime;
    $SLAData['violationTime'] = 0;

    if(is_null($timeInStatusField)) {    
        $SLAData['reactionTime'] = 'unknown';
        $SLAData['violationTime'] = 'unknown';
    } else {
        $statuses = explode('_*|*_', $timeInStatusField);
        foreach ( $statuses as $status ) {
            $subfields = explode('_*:*_', $status);

            if ( $subfields[0] == '10017' ) {
                $unTriagedTimeInMilliseconds = intval($subfields[2]);
                $reactionTime = intval($unTriagedTimeInMilliseconds / 1000 / 60);
                $maxReactionTimeInMilliseconds = $maxReactionTime * 60 *  1000;

                $SLAData['reactionTime'] = $reactionTime;

                if ( $unTriagedTimeInMilliseconds > $maxReactionTimeInMilliseconds ) {
                    $SLAData['violationTime'] = $reactionTime - $maxReactionTime;
                }
            }
        }
    }

    return $SLAData;

}

$api = new Api(
    $host,
    new Basic($username, $password)
);

$walker = new Walker($api);
$walker->push($jql);

$issues = [];

foreach ( $walker as $issue ) {
    array_push($issues, getSLAData($issue));
}

$fp = fopen('php://stdout', 'w');

foreach ($issues as $issue) {
    fputcsv($fp, $issue);
}


?>
