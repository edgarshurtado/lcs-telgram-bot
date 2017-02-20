<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class ResultsCommand extends UserCommand
{

    public function execute()
    {
        $message = $this->getMessage();           // Get Message object

        $chat_id = $message->getChat()->getId();  // Get the current Chat ID

        $data = [];                               // Set up the new message data
        $data['chat_id'] = $chat_id;              // Set Chat ID to send the message to

        $games_results = $this->getResults();

        $data['text'] = $this->formatMessage($games_results); // Set message to send

        return Request::sendMessage($data);       // Send message!
    }

    private function getResults()
    {

        $conn = new \PDO("mysql:dbname=lcs_results_bot;host=127.0.0.1", "root", "");

        $sql = "
            SELECT blueTeam.name blue_team, redTeam.name red_team,
            winner.name winner, lr.week week
            FROM lcs_results lr
            JOIN lcs_teams blueTeam on lr.team_blue = blueTeam.id
            JOIN lcs_teams redTeam on lr.team_red = redTeam.id
            JOIN lcs_teams winner on lr.winner = winner.id
            ORDER BY week
        ";

        $stmt = $conn->prepare($sql);

        $stmt->execute();

        $results = $stmt->fetchAll();

        return $results;
    }

    private function formatMessage($lcs_results_array)
    {
        $formatted_array = [];

        $message = "";

        foreach ($lcs_results_array as $match) {
            $formatted_array[$match["week"]][] = [
                "team_blue" => $match["blue_team"],
                "team_red" => $match["red_team"],
                "winner" => $match["winner"]
            ];
        }

        ksort($formatted_array);

        foreach ($formatted_array as $week => $matches) {
            $message .= "WEEK $week\n------------\n";
            foreach ($matches as $match) {
                $team_blue = ucwords($match["team_blue"], " ");
                $team_red = ucwords($match["team_red"], " ");
                $winner = ucwords($match["winner"]);

                if($team_blue == $winner){
                    $team_blue .= " (W)";
                }else{
                    $team_red .= " (W)";
                }

                $message .= $team_blue . " Vs. " . $team_red . "\n";
            }
            $message .= "\n";
        }

        return $message;
    }
}