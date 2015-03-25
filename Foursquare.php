<?php

namespace garyrutland\foursquare;

use Yii;
use yii\base\Component;
use Guzzle\Http\Client;
use Jcroll\FoursquareApiClient\Client\FoursquareClient;

class Foursquare extends Component
{
    public $clientId;

    public $secret;

    private $client;
    private $accessToken;

    public function init()
    {
        parent::init();

        $this->client = FoursquareClient::factory([
            'client_id' => $this->clientId,
            'client_secret' => $this->secret,
        ]);

        $accessToken = Yii::$app->session->get('fsAccessToken');
        if ($accessToken !== null) {
            $this->setAccessToken($accessToken);
        }
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        $this->client->addToken($this->accessToken);
        Yii::$app->session->set('fsAccesstoken', $this->accessToken);
    }

    public function getLoginUrl($redirectUrl)
    {
        $url = 'https://foursquare.com/oauth2/authenticate';
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
        ];
        return $url . '?' . http_build_query($params);
    }
    
    public function getLoginSession($redirectUrl)
    {
        $code = Yii::$app->request->get('code');
        $guzzle = new Client();

        $request = $guzzle->post('https://foursquare.com/oauth2/access_token', [], [
            'client_id' => $this->clientId,
            'client_secret' => $this->secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUrl,
            'code' => $code,
        ]);
        $response = $request->send()->json();

        $this->setAccessToken($response['access_token']);
    }

    public function getUser($userId = 'self')
    {
        $request = $this->client->getCommand('users', [
            'user_id' => $userId,
        ])->execute();

        if (!empty($request['response']['user'])) {
            return $request['response']['user'];
        }

        return [];
    }

    public function getFriends($userId = 'self')
    {
        $request = $this->client->getCommand('users/friends', [
            'user_id' => $userId,
            'limit' => 500,
        ])->execute();

        if (!empty($request['response']['friends']['items'])) {
            return $request['response']['friends']['items'];
        }

        return [];
    }

    public function getCheckIns($userId = 'self', $dateTime = null)
    {
        $request = $this->client->getCommand('users/checkins', [
            'user_id' => $userId,
            'afterTimestamp' => !empty($dateTime) ? strtotime($dateTime) : null,
            'limit' => 250,
        ])->execute();

        if (!empty($request['response']['checkins']['items'])) {
            return $request['response']['checkins']['items'];
        }

        return [];
    }
}