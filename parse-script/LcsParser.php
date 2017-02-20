<?php

include 'simple_html_dom.php';

$conn = new PDO("mysql:dbname=lcs_results_bot;host=127.0.0.1", "root", "");

function getMatchesData($simple_html_dom_object)
{
  $matchesResults = [];

  foreach($simple_html_dom_object->find('.schedule-item') as $game){
      $blueTeam = getTeamData($game->find(".blue-team", 0));
      $redTeam = getTeamData($game->find(".red-team", 1));
      $winner = $blueTeam["result"] == "VICTORY"
                  ? $blueTeam["acronym"]
                  : $redTeam["acronym"];

      $matchesResults[] = array(
          "blue-team" => $blueTeam,
          "red-team" => $redTeam,
          "winner" => $winner
      );
  }

  return $matchesResults;
}

function getTeamData($teamObject){
   $teamName = $teamObject->find(".team-name", 0)->plaintext;
   $teamAcronym = $teamObject->find(".team-acronym", 0)->plaintext;
   $teamResult = $teamObject->find(".defeat", 0);
   if($teamResult == null){
    $teamResult = $teamObject->find(".victory", 0);
   }
   $teamResult = $teamResult->plaintext;

   $teamMatchData = array(
    "name" => $teamName,
    "acronym" => $teamAcronym,
    "result" => $teamResult
   );

   return $teamMatchData;
}

function resultAlreadyInDB($conn, $matchResult, $weekNumber)
{
  $stmt = $conn->prepare("
    SELECT *
      FROM lcs_results r
      JOIN lcs_teams bt ON bt.id = r.team_blue
      JOIN lcs_teams rt ON rt.id = r.team_red
      JOIN lcs_teams w ON w.id = r.winner
    WHERE bt.acronym = :blueTeam
      AND rt.acronym = :redTeam
      and week = :week
  ");

  $stmt->bindParam(":blueTeam", $matchResult["blue-team"]["acronym"]);
  $stmt->bindParam(":redTeam", $matchResult["red-team"]["acronym"]);
  $stmt->bindParam(":week", $weekNumber);

  $stmt->execute();

  $results = $stmt->fetchAll();

  return !empty($results);
}

function getTeamIdByAcronym($conn, $acronym){
  $stmt = $conn->prepare("
    SELECT id
      FROM lcs_teams
      WHERE acronym = :acronym
  ");

  $stmt->bindParam(":acronym", $acronym);
  $stmt->execute();

  $result = intval($stmt->fetchAll()[0]["id"]);

  return $result;
}

function insertResult($conn, $blueTeamId, $redTeamId, $winnerId, $weekNumber){
    $stmt = $conn->prepare("
      INSERT INTO lcs_results (team_blue, team_red, winner, week)
      values (:blueTeamId, :redTeamId, :winnerId, :weekNumber)
    ");

    $stmt->bindParam(":blueTeamId", $blueTeamId);
    $stmt->bindParam(":redTeamId", $redTeamId);
    $stmt->bindParam(":winnerId", $winnerId);
    $stmt->bindParam(":weekNumber", $weekNumber);

    $stmt->execute();
}

$html = file_get_html('page.html');
$matchesResults = getMatchesData($html);

$weekNumber = $html->find("div.current", 0)->plaintext;
$weekNumber = intval($weekNumber, 10);


foreach ($matchesResults as $match) {
    if(is_null($match["blue-team"]["result"])) continue; // Match not played yet

    if(!resultAlreadyInDB($conn, $match, $weekNumber)){
      $blueTeamId = getTeamIdByAcronym($conn, $match["blue-team"]["acronym"]);
      $redTeamId = getTeamIdByAcronym($conn, $match["red-team"]["acronym"]);
      $winnerId = getTeamIdByAcronym($conn, $match["winner"]);
      insertResult($conn, $blueTeamId, $redTeamId, $winnerId, $weekNumber);
    }
}
