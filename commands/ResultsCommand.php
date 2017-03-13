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

        $week = $this->getWeekFromMessage($message); // Get week number sent by user
        $games_results = $this->getResults($week);

        $data['text'] = $this->formatMessage($games_results); // Set message to send

        return Request::sendMessage($data);       // Send message!
    }

    /**
     * Retrieves the results for a given week.
     * @param $week int
     */
    private function getResults($week = null)
    {
        $week_where = !is_null($week) ? "WHERE lr.week = $week" : "";

        $conn = new \PDO("mysql:dbname=lcs_results_bot;host=127.0.0.1", "root", "");

        $sql = "
            SELECT blueTeam.name blue_team, redTeam.name red_team,
            winner.name winner, lr.week week
            FROM lcs_results lr
            JOIN lcs_teams blueTeam on lr.team_blue = blueTeam.id
            JOIN lcs_teams redTeam on lr.team_red = redTeam.id
            JOIN lcs_teams winner on lr.winner = winner.id
            $week_where
            ORDER BY week
        ";

        $stmt = $conn->prepare($sql);

        $stmt->execute();

        $results = $stmt->fetchAll();

        return $results;
    }

    /**
     * Prepares a string with the data to send to the chat
     * @param $lcs_results_array Array with the information retrieve from
     * getResults()
     */
    private function formatMessage($lcs_results_array)
    {
        if(empty($lcs_results_array)) return "No available data";

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

    /**
     * Gets the week number from the user message
     * @param $telegram_message_object telegram api message object
     */
    private function getWeekFromMessage($telegram_message_object)
    {

        $message = $telegram_message_object->getText();


        $message_parts = explode(" ", $message );

        $size_message_parts = sizeof($message_parts);

        $week_number = null;

        if($size_message_parts === 2 && is_numeric($message_parts[1])){
            $week_number = $message_parts[1];
        }elseif ($size_message_parts != 1) {
            throw new \Exception("Bad parameters in request", 1);
        }

        return $week_number;
    }
}