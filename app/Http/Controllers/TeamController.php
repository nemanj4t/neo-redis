<?php

namespace App\Http\Controllers;

use App\Coach;
use App\Player_Team;
use App\RecommendationService;
use App\Team;
use App\Team_Coach;
use App\PlayerStatistic;
use Illuminate\Http\Request;
use Ahsan\Neo4j\Facade\Cypher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teams = Team::cacheTeams(10);

        return view('teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $coaches = [];

        $allCoaches = Coach::getAll();

        foreach ($allCoaches as $coach) {
            if ($coach->current_team === null) {
                array_push($coaches, $coach);
            }
        }

        return view('teams.create', compact('coaches'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'coached_since' => 'required|date|date_format:Y-m-d|before:today',
            'coached_until' => 'required|date|date_format:Y-m-d|after:yesterday',
            'city' => 'required',
            'description' => 'required',
            'image' => 'required',
            'background_image' => 'required',
        ]);

        Team::save($request);

        return redirect('/apanel/teams')->with('success', 'Team saved!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $team = Team::getById($id);
        $standings = Redis::hgetall("team:standings:{$id}");

        $best_players = ["points" => null, "blocks" => null, "steals" => null, "rebounds" => null, "assists" => null];

        foreach ($team->current_players as $player)
            $player->player->statistics = PlayerStatistic::getById($player->player->id);


        foreach ($best_players as $key => $stat) {
            foreach ($team->current_players as $player) {
                if ($best_players[$key] === null) {
                    $best_players[$key] = $player;
                }
                else {
                    if ($player->player->statistics[$key] != null and intval($best_players[$key]->player->statistics[$key]) < intval($player->player->statistics[$key]))
                        $best_players[$key] = $player;
                }
            }
        }

        $articles = RecommendationService::recommendArticlesForTeam($id);

        return view('teams.show', compact('team', 'standings', 'best_players', 'articles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {
        $team = Team::getById($id);

        $coaches = [];

        $allCoaches = Coach::getAll();

        foreach ($allCoaches as $coach) {
            if ($coach->current_team === null)
                array_push($coaches, $coach);
        }

        return view('teams.edit', compact('team', 'coaches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'short_name' => 'required',
            'coached_since' => 'required|date|date_format:Y-m-d|before:today',
            'coached_until' => 'required|date|date_format:Y-m-d|after:yesterday',
            'city' => 'required',
            'description' => 'required',
            'image' => 'required',
            'background_image' => 'required',
        ]);

        Team::update($id, $request);

        return redirect("/teams/" . $id)->with('success', 'Saved!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Brise cvor i sve njegove veze
        Team::delete($id);

        return redirect('/apanel/teams')->with('danger', 'Team deleted!');
    }
}
