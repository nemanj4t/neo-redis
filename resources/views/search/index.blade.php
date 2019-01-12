@extends('layouts.app')

@section('content')

    @php
        /** @var \App\Player[] $players */
        /** @var \App\Team[] $teams */
        /** @var \App\Coach[] $coaches */
    @endphp

    <div class="container" class="col-xs-1 center-block" style="margin-top: 20px;">
        <div class="col-sm-12 col-md-12">
            <form action="/search" method="GET" class="navbar-form" role="search">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search" value="{{Session::get('search')}}" name="q">
                    <div class="input-group-btn">
                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="container" style="margin-top:20px;">
        <h1>Results: </h1>
        @if (empty($players) and empty($teams) and empty($coaches))
            <h3>No results found</h3>
        @endif
        <div class="row">
            <div id="user" class="col-md-12" >
                <div class="panel panel-primary panel-table animated slideInDown">
                    <div class="panel-body">
                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="list">
                                <table class="table table-striped table-bordered table-list">
                                    <thead>
                                    <tbody>
                                    @foreach ($players as $player)
                                        <tr class="ok">
                                            <td class="avatar"><img id="img" class="avatar" src={{$player->image}}></td>
                                            <td><a href="/players/{{$player->id}}">{{$player->name}}</a></td>
                                            @if (isset($player->current_team))
                                                <td>{{$player->current_team->name}}</td>
                                            @else
                                                <td>No current team</td>
                                            @endif
                                            <td><a href="/players">Player</a></td>
                                        </tr>
                                    @endforeach
                                    @foreach ($teams as $team )
                                        <tr class="ok">
                                            <td class="avatar"><img id="img" src={{$team->image}}></td>
                                            <td><a href="/teams/{{$team->id}}">{{$team->name}}</a></td>
                                            @if (isset($team->current_coach))
                                                <td><a href="/coaches/{{$team->current_coach->coach_id}}">{{$team->current_coach->coach->name}}</a></td>
                                            @else
                                                <td>No current coach</td>
                                            @endif
                                            <td><a href="/teams">Team</a></td>
                                        </tr>
                                    @endforeach
                                    @foreach ($coaches as $coach)
                                        <tr class="ok">
                                            <td class="avatar"><img id="img" class="avatar" src={{$coach->image}}></td>
                                            <td><a href="/coaches/{{$coach->id}}">{{$coach->name}}</a></td>
                                            @if($coach->current_team != null)
                                                <td><a href="/teams/{{$coach->current_team->team_id}}">{{$coach->current_team->team->name}}</a></td>
                                            @else
                                                <td>No professional engagement currently</td>
                                            @endif
                                            <td><a href="/coaches">Coach</a></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div><!-- END id="list" -->

                        </div><!-- END tab-content -->
                    </div>
                </div><!--END panel-table-->
            </div>
        </div>
    </div>

@endsection
