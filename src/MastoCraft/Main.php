<?php
namespace MastoCraft;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\scheduler\Task;
require __DIR__ . '/../../vendor/autoload.php';
use WebSocket\Client;

class Main extends PluginBase implements Listener {
  public function onLoad() : void{
    
	}
	public function onEnable() : void{
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getScheduler()->scheduleRepeatingTask(new ReceiveTask($this), 500);
    $this->client = new Client('wss://odakyu.app/api/v1/streaming?access_token=5185c395db5643907a936a63961b2d942d4f69f24547779b04cad378be207ed3&stream=public', array('timeout' => 10000000));
  }

  public function onChat(PlayerChatEvent $event)
  {
    $p = $event->getPlayer();
    $msg = $event->getMessage();
    $pname = $p->getName();
    $host = "odakyu.app";
    $url = 'https://' . $host . '/api/v1/statuses';
    $curl = curl_init($url);
    $access_token = '5185c395db5643907a936a63961b2d942d4f69f24547779b04cad378be207ed3';
    $header = "Authorization: Bearer $access_token";
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
    try {
      $this->owner->client->send("heartbeat");
      $json_array = json_decode($this->owner->client->receive(), true);
      if($json_array["event"] == "update") {
        $status = json_decode($json_array["payload"], true);
        $this->owner->getServer()->broadcastMessage("[" . $status["account"]["display_name"] . "]"  . $status["content"]);
      }
    } catch (Exception $e) {
      $this->owner->client = new Client('wss://odakyu.app/api/v1/streaming?access_token=5185c395db5643907a936a63961b2d942d4f69f24547779b04cad378be207ed3&stream=public', array('timeout' => 10000000));
    }  
	}
}