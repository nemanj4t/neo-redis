<?php

namespace App\Http\Controllers;

use App\Coach;
use App\RecommendationService;
use App\Team;
use App\Team_Coach;
use GraphAware\Neo4j\Client\Formatter\Result;
use Illuminate\Http\Request;
use Ahsan\Neo4j\Facade\Cypher;
use Illuminate\Session;

class CoachController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $coaches = Coach::cacheCoaches(10);

        return view('coaches.index', compact('coaches'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $teams = Team::getAll();

        return view('coaches.create', compact('teams'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        \Session::put('name', $request['name']);
        \Session::put('team', $request['team']);
        \Session::put('bio', $request['bio']);
        \Session::put('city', $request['city']);
        \Session::put('image', $request['image']);
        \Session::put('coached_since', $request['coached_since']);
        \Session::put('coached_until', $request['coached_until']);

        $request->validate([
            'name' => 'required',
            'coached_since' => 'date|date_format:Y-m-d|before:today|nullable',
            'coached_until' => 'date|date_format:Y-m-d|after:yesterday|nullable',
            'old_team.*.team_id' => 'required',
            'old_team.*.coached_since' => 'required|date|date_format:Y-m-d|before:today',
            'old_team.*.coached_until' => 'required|date|date_format:Y-m-d|before:yesterday',
        ]);

        $result = Coach::saveCoach($request);
        $coach_id = $result->firstRecord()->getByIndex(0)->identity();


        if(isSet($_POST['old_team'])) {
            foreach ($request['old_team'] as $data) {
                $team_coach = new Team_Coach();
                $team_coach->coach_id = $coach_id;
                $team_coach->team_id = $data['team_id'];
                $team_coach->coached_since = $data['coached_since'];
                $team_coach->coached_until = $data['coached_until'];
                $team_coach->save();
            }
        }
        return redirect('/apanel/coaches')->with('success', 'Coach saved!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $coach = Coach::getById($id);
        $coached_teams = Team_Coach::getByCoachId($id);

        $recPlayers = [];
        // Vraca trenere koji su nekada trenirali taj tim
        $current_team = Team_Coach::getCurrentForCoachId($coach->id);
        $coach->current_team = $current_team;

        if ($current_team) {
            $current_team_id = $current_team->team_id;

            /** @var Result $recommendedResult */
            $recommendedResult = Cypher::run("MATCH (t:Team)-[r:TEAM_COACH]-(c:Coach) 
                WHERE ID(t) =". $current_team_id . " AND ID(c) <>" . (int)$id . " return distinct c LIMIT 5");

            foreach ($recommendedResult->getRecords() as $record) {
                $node = $record->value('c');
                $recCoach = Coach::buildFromNode($node);
                array_push($recPlayers, $recCoach);
            }
        }

        $articles = RecommendationService::recommendArticlesForCoach($id);

        return view('coaches.show', compact('coach', 'recPlayers', 'coached_teams', 'articles'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id) {
        $coach = Coach::getById($id);
        $teams = Team::getAll();
        return view("coaches.edit", compact('coach', 'teams'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'coached_since' => 'date|date_format:Y-m-d|before:today',
            'coached_until' => 'date|date_format:Y-m-d|after:yesterday',
        ]);

        Coach::update($id, $request);

        return redirect("/coaches/" . $id)->with('success', 'Saved!');
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
        Coach::delete($id);

        // Fali brisanje tog cvora iz redisa

        return redirect('/apanel/coaches')->with('danger', 'Coach deleted!');
    }
}
