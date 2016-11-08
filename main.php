<?php

require 'vendor/autoload.php';

use  GuzzleHttp\Client;

class Log
{
    const LOG_FILE = "storage/log.txt";

    public static function info($message)
    {
        static::logMessage("INFO", $message);
    }

    public static function error($message)
    {
        static::logMessage("ERROR", $message);
    }

    private static function logMessage($level, $message)
    {
        $date = date("y-m-d H:i:s");
        $wrappedMessage = "[$level] [$date] $message \n";
        echo $wrappedMessage;
        file_put_contents(static::LOG_FILE, $wrappedMessage, FILE_APPEND);
    }
}

class Stat
{
    const STAT_FILE = "storage/stats.txt";
    private $stats;

    /**
     * Stat constructor.
     */
    public function __construct()
    {
        $this->stats = [];
    }


    public function loadStats()
    {
        $this->stats = file(self::STAT_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function isDuplicated($value)
    {
        $value = (string) $value;
        return in_array($value, $this->stats);
    }

    public function addStats($value)
    {
        $this->stats[] = $value;
        file_put_contents(static::STAT_FILE, $value . "\n", FILE_APPEND);
    }
}

class Spider
{
    const USERS_FILE = "storage/users.txt";

    private $stats;

    /**
     * Spider constructor.
     * @param $stats
     */
    public function __construct(Stat $stats)
    {
        $this->stats = $stats;
        $this->stats->loadStats();
    }

    function getPage($page)
    {
        try {
            $client = new Client();
            $response = $client->request('POST', 'http://www.coroflot.com/people/search', [
                'headers' => ['Accept' => 'application/json'],

                'form_params' => ['page_number' => $page, 'sort_by' => "1", 'currently_featured' => "false"]
            ]);

            $jsonResponse = json_decode($response->getBody(), true);
            $data = json_decode($jsonResponse['data'], true);
            Log::info("Get page ($page) success.");
            return $data['job_seeker_profiles'];
        } catch (Exception $e) {
            Log::error("Get page ($page) error.");
            return [];
        }
    }

    function downloadSaveAvatar($avatarName)
    {
        $client = new Client();
        $avatarUrlPrefix = "http://s3images.coroflot.com/user_files/individual_files/avatars/";
        $avatarUrl = $avatarUrlPrefix . $avatarName;

        try {
            $avatarResponse = $client->get($avatarUrl);
            $extension = pathinfo($avatarName, PATHINFO_EXTENSION);

            $fileName = md5(uniqid()) . "." . $extension;
            $filePath = "storage/img/$fileName";
            file_put_contents($filePath, $avatarResponse->getBody());
            Log::info("Download avatar success: $avatarUrl");
            return $fileName;
        } catch (Exception $e) {
            Log::error("Download avatar failed: $avatarUrl");
            return false;
        }
    }

    function recordUser($username, $avatar)
    {
        $username = trim(preg_replace("/[^a-zA-Z0-9 ]+/", "", $username));
        file_put_contents(self::USERS_FILE, "$username,$avatar\n", FILE_APPEND);
    }

    function run()
    {
        for ($page = 1; $page < 2; $page++) {
            $profiles = $this->getPage($page);
            foreach ($profiles as $profile) {
                if ($profile['avatar_image'] && !$this->stats->isDuplicated($profile['job_seeker_id'])) {
                    $avatar = $this->downloadSaveAvatar($profile['avatar_image']);
                    if ($avatar) {
                        $this->recordUser($profile['first_name'], $avatar);
                        $this->stats->addStats($profile['job_seeker_id']);
                    }
                }
            }
        }
    }
}

$spider = new Spider(new Stat());
$spider->run();
