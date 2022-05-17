<?php

namespace App;

use App\Models\Fixture;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EntitySport
{
    public function http($url, $params = []): Response
    {
        $token = Redis::get('entity_sport');
        if (!$token || is_null($token)) {
            $token = '6640b417118874e128c3f0c6c9d29d98';
            //a050f9a3cbf51783280a08debe3f9431 new token
            //9f5c5f136b9a5ceeb21a432ea66c462d old token
        }else{
            $arrayToken=json_decode($token,true);
            $token=!empty($arrayToken['token'])?$arrayToken['token']:$token;
        }
        //Log::info('urlllllllllhttps://rest.entitysport.com/v2/' . $url.'------'.$token);


        return Http::get('https://rest.entitysport.com/v2/' . $url, array_merge(['token' => $token], $params));
    }

    public function login()
    {
        // https://doc.entitysport.com/#obtaining-token
        $keys = Redis::get('entity_sport');
        // Log::info(json_encode($keys)."Entity Key");

        $params = json_decode($keys, true);
        $response = Http::get('https://rest.entitysport.com/v2/auth', array_merge(['extend' => 1], $params));

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response.token');
                if (!is_null($data)) {
                    Redis::set('entity_sport', $data);
                    return $data;
                }
            }
        }

        return null;
    }

    public function getSchedule($params = []): array
    {
        // https://doc.entitysport.com/#matches-list-api
        $response = $this->http('matches', $params);

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response.items');
                if (!is_null($data)) {
                    return $data;
                }
            } elseif ($status == 'unauthorized') {
                $this->login();
            }
        }

        return [];
    }

    public function getSquads(Fixture $fixture): array
    {
        // https://doc.entitysport.com/#fantasy-match-roaster-api
        $response = $this->http('competitions/' . $fixture->competition_id . '/squads/' . $fixture->id);

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response.squads');
                if (!is_null($data)) {
                    return $data;
                }
            } elseif ($status == 'unauthorized') {
                $this->login();
            }
        }

        return [];
    }

    public function getLineup(Fixture $fixture)
    {
        // https://doc.entitysport.com/#match-squads-api
        $response = $this->http('matches/' . $fixture->id . '/squads');

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response');
                if (!is_null($data)) {
                    return $data;
                }
            } elseif ($status == 'unauthorized') {
                $this->login();
            }
        }

        return null;
    }

    public function getFantasyPoints($fixtureId)
    {
        // https://doc.entitysport.com/#match-fantasy-points-api
        $response = $this->http('matches/' . $fixtureId . '/newpoint');

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response');
                if (!is_null($data)) {
                    return $data;
                }
            } elseif ($status == 'unauthorized') {
                $this->login();
            }
        }

        return null;
    }

    public function getScorecard($id)
    {
        // https://doc.entitysport.com/#match-scorecard-api
        $response = $this->http('matches/' . $id . '/scorecard');

        if ($response->successful()) {
            $status = $response->json('status');
            if ($status == 'ok') {
                $data = $response->json('response');
                if (!is_null($data)) {
                    return $data;
                }
            } elseif ($status == 'unauthorized') {
                $this->login();
            }
        }

        return null;
    }
}
