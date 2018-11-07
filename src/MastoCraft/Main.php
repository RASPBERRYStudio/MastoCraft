<?php
namespace MastoCraft;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
require __DIR__ . '/../../vendor/autoload.php';
use WebSocket\Client;

class Main extends PluginBase implements Listener {
	public function onEnable() : void{
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getScheduler()->scheduleRepeatingTask(new ReceiveTask($this), 5);
    $this->mastoConfig = new Config($this->getDataFolder() . "MastoCraft.yml", Config::YAML, array(
      "url" => "seichi.work",
      "token" => "xxxx",
      "stream" => "public",
    ));
    $this->client = new Client(
      "wss://{$this->mastoConfig->get('url')}/api/v1/streaming?access_token={$this->mastoConfig->get('token')}&stream={$this->mastoConfig->get('stream')}",
      array('timeout' => 10000000)
    );
  }

  public function onChat(PlayerChatEvent $event)
  {
    $p = $event->getPlayer();
    $msg = $event->getMessage();
    $pname = $p->getName();
    $url = 'https://' . $this->mastoConfig->get('url') . '/api/v1/statuses';
    $curl = curl_init($url);
    $header = "Authorization: Bearer {$this->mastoConfig->get('token')}";
    $post_data = array(
        'status' => '[' . $pname . '] ' . $msg ,
    );
    $options = array(
      CURLOPT_HTTPHEADER => array($header),
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query($post_data),
    );
    curl_setopt_array($curl, $options);
    $result = curl_exec($curl);
  }
}

class ReceiveTask extends Task{
	public function __construct($owner){
		$this->owner = $owner;
	}
	public function onRun(int $currentTick) : void{
    $this->owner->client->send("heartbeat");
    $json_array = json_decode($this->owner->client->receive(), true);
    if($json_array["event"] == "update") {
      $status = json_decode($json_array["payload"], true);
      $this->owner->getServer()->broadcastMessage("[" . $status["account"]["display_name"] . "]"  . strip_tags($status["content"]));
    } 
	}
}