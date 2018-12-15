<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ahsan\Neo4j\Facade\Cypher;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = Cypher::run("MATCH (n:Player) RETURN n");
        $players = [];

        foreach($result->getRecords() as $record)
        {
            $properties_array = $record->getPropertiesOfNode();
            $id_array = ["id" =>  $record->getIdOfNode()];
            $player = array_merge($properties_array, $id_array);
            array_push($players, $player);
        }

        return view('players.index', compact('players'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Ako je korisnik ulogovans
        return view('players.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        Cypher::run("CREATE (:Player {name: '$request[name]', height: '$request[height]', 
            weight: '$request[weight]', city: '$request[city]', bio: '$request[bio]', image: '$request[image]'})");

        // Ovde fali dodavanje globalne statistike za novog igraca u redis

        return redirect('/players');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $result = Cypher::Run("MATCH (n:Player) WHERE ID(n) = $id return n")->getRecords()[0];
        $properties = $result->getPropertiesOfNode();
        $player = array_merge(["id" => $result->getIdOfNode()], $properties);

        // 1. Fali prikaz svih timova za koje je igrao kao i trenutni tim
        // 2. Preporuka za slicne igrace
        // 3. Mozda za svaki tim da se prikazu saigraci sa kojima je igrao u tom trenutku

        dd($player);
        //return view('players.show', compact('player'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $result = Cypher::Run("MATCH (n:Player) WHERE ID(n) = $id return n")->getRecords()[0];
        $properties = $result->getPropertiesOfNode();
        $player = array_merge(["id" => $result->getIdOfNode()], $properties);

        return view('players.edit', compact('player'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Prva tri inputa su: method, token, id
        //$updatedProps = json_encode(array_slice($request->all(), 3));

        Cypher::Run("MATCH (n:Player) WHERE ID(n) = $id SET n = {
            name: '$request[name]',
            bio: '$request[bio]',
            height: '$request[height]',
            weight: '$request[weight]',
            city: '$request[city]',
            image: '$request[image]'}");

        return redirect('/players');
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
        Cypher::Run("MATCH (n:Player) WHERE ID(n) = $id DETACH DELETE n");

        // Fali brisanje tog cvora iz redisa

        return redirect('/players');
    }
}
