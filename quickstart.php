<?php 

require_once __DIR__ . '/vendor/autoload.php';

if(php_sapi_name() !== 'cli' ){
    die('The application should be running from a command line.');
}

function getAccessToken(\Google_Client $client){
    $localPath = __DIR__ . '/token.json';
    if(file_exists($localPath)){
        return json_decode(file_get_contents($localPath), true);
    } else {
        $url = $client->createAuthUrl();
        printf("Open this url in your browser and get the auth code :\n%s\n", $url);
        print "Type the auth code:";
        $authCode = trim(fgets(STDIN));

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        if(array_key_exists('error', $accessToken)){
            throw new \Exception('Error while getting the accessToken : ' . $accessToken['error']);
        }
        file_put_contents($localPath, json_encode($accessToken));
        return $accessToken;
    }
}

function getClient(){
    $client = new \Google_Client();
    $client->setApplicationName('Google Calendar Tutorial - CLI');
    $client->setAuthConfig('client-secret.json');
    $client->setScopes(\Google_Service_Calendar::CALENDAR_READONLY);
    $client->setAccessType('offline');
    $client->setAccessToken(getAccessToken($client));

    return $client;
}


try{
    $client = getClient();
    $calendar = new \Google_Service_Calendar($client);
    $calendarId = 'primary';
    $optParmas = [
        'maxResults'=>10,
        'orderBy'=>'startTime',
        'singleEvents'=>true,
        'timeMin'=>date('c'),
    ];
    $events = $calendar->events->listEvents($calendarId, $optParmas)->getItems();
    foreach($events as $event){
        $start = $event->getStart();
        if($start->getDatetime()){
            $start = $start->getDatetime();
        } else {
            $start = $start->getDate();
        }
        printf("- %s at %s\n", $event->getSummary(), $start);
    }
    echo 'Ok' . PHP_EOL;
} catch(\Exception $ex){
    echo $ex->getMessage() . PHP_EOL;
}