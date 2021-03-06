<?php

namespace App;

use Ahsan\Neo4j\Facade\Cypher;
use GraphAware\Neo4j\Client\Formatter\Result;
use GraphAware\Neo4j\Client\Formatter\Type\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class Player
{
    public $id;
    public $name;
    public $city;
    public $image;
    public $bio;
    public $height;
    public $weight;

    /** @var Team */
    public $current_team;
    public $past_teams;
    public $statistics = [];

    public static function buildFromNode(Node $node)
    {
        $player = new Player();
        $player->id = $node->identity();
        $player->name = $node->value('name');
        $player->city = $node->value('city', null);
        $player->image = $node->value('image', null);
        $player->bio = $node->value('bio', null);
        $player->height = $node->value('height', null);
        $player->weight = $node->value('weight', null);

        return $player;
    }

    public static function getById($id){
        $query = Cypher::run("MATCH (p:Player) WHERE ID(p) = {$id} RETURN p");

        try {
            $record = $query->getRecord();
        } catch (\RuntimeException $exception) {
            return null;
        }

        $node = $query->firstRecord()->nodeValue('p');
        $player = self::buildFromNode($node);

        return $player;
    }

    /**
     * @param $request
     * @return Player|null
     */
    public static function savePlayer(Request $request) {
        //dd($request);
        // Ovaj upit moze da vrati id na kraju
        /** @var Result $result */
       $result = Cypher::run("CREATE (p:Player {name: '$request[name]', height: '$request[height]',
            weight: '$request[weight]', city: '$request[city]', bio: '$request[bio]', image: '$request[image]'}) RETURN p");

       $record = $result->getRecord();
       $nodePlayer = $record->value('p');
       $player = Player::buildFromNode($nodePlayer);

        //counter players
        Redis::incr("count:players");
        // Dodavanje globalne statistike za ovog igraca u redis
        PlayerStatistic::saveGlobalStats($player->id);

        return $player;
    }

    /**
     * @param $id
     * @return Team|null
     */
    public static function getCurrentTeam($id)
    {
        /** @var Result $result */
        $result = Cypher::run("MATCH (p:Player)-[:PLAYS]-(t:Team) WHERE ID(p) = {$id} return t");

        try {
            $record = $result->getRecord();
        } catch (\RuntimeException $exception) {
            return null;
        }

        $node = $record->value('t');

        $team = Team::buildFromNode($node);

        return $team;
    }

    /**
     * @return Player[]
     */
    public static function getAllWithCurrentTeam()
    {
        /** @var Result $result */
        $result = Cypher::run("MATCH (p:Player) OPTIONAL MATCH (p)-[:PLAYS]-(t:Team) return p, t");
        $players = [];

        foreach ($result->getRecords() as $record) {
            $playerNode = $record->value('p');
            $player = self::buildFromNode($playerNode);
            $player->current_team = null;

            if ($record->value('t')) {
                $teamNode = $record->value('t');
                $team = Team::buildFromNode($teamNode);

                $player->current_team = $team;
            }

            $players[] = $player;
        }

        return $players;
    }

    /**
     * @param array $ids
     * @return Player[]
     */
    public static function getSomeWithCurrentTeam(array $ids)
    {
        $id_array = implode(', ', $ids);
        $result = Cypher::run("MATCH (p:Player) WHERE ID(p) IN [{$id_array}] OPTIONAL MATCH (p)-[:PLAYS]-(t:Team) return p, t");
        $players = [];

        foreach ($result->getRecords() as $record) {
            $playerNode = $record->nodeValue('p');

            $player = Player::buildFromNode($playerNode);

            if($record->value('t') != null) {
                $teamNode = $record->nodeValue('t');
                $team = Team::buildFromNode($teamNode);
                $player->current_team = $team;
            }

            $players[$player->id] = $player;
        }

        return $players;
    }

    public static function deletePlayer($id)
    {
        // Brise cvor i sve njegove veze
        Cypher::Run("MATCH (n:Player) WHERE ID(n) = $id DETACH DELETE n");
        PlayerStatistic::deleteStats($id);

        foreach(Redis::keys("match:*:team:*:{$id}") as $key) {
            Redis::del($key);
        };

        Redis::decr("count:players");
    }

    public static function cachePlayers($seconds)
    {
        if($value = Redis::get('players:cache')) {
            return json_decode($value);
        }

        $value = self::getAllWithCurrentTeam();

        Redis::setex('players:cache', $seconds, json_encode($value));

        return $value;
    }
}
