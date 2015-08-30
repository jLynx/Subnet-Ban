<?php

namespace DarkN3ss\SubBan;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\Listener;
use pocketmine\Player;

class SubBan extends PluginBase implements Listener{
    
    private $subnetBanned;
    private $config;
    private $file;
    private $correct;
    private $type = Config::ENUM;

    public function onEnable(){
        $this->getLogger()->info("SubnetBan Starterd");
        
        @mkdir($this->getDataFolder());
        $this->subnetBanned = new Config($this->getDataFolder()."subnetBanned.txt", Config::ENUM);
        $this->load($this->getDataFolder()."subnetBanned.txt");
//        $this->configTXT->save();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(){
        $this->getLogger()->info("SubnetBan Stopped");
    }
    
    	public function subnetIP($ip)
	{
		list($ipPart1, $ipPart2) = explode('.', $ip);
		$subnetIP = $ipPart1 . "." . $ipPart2;
		return $subnetIP;
	}
	
	public function getAll($keys = false){
		return ($keys === true ? array_keys($this->config):$this->config);
	}
	
    public function load($file, $type = Config::ENUM, $default = array()){
        $this->correct = true;
        $this->type = (int) $type;
        $this->file = $file;
        if(!is_array($default)){
            $default = array();
        }
        $extension = explode(".", basename($this->file));
        $extension = strtolower(trim(array_pop($extension)));
        if(isset(Config::$formats[$extension]))
        {
            $this->type = Config::$formats[$extension];
            $content = @file_get_contents($this->file);
            $this->parseList($content);
            if(!is_array($this->config)){
                    $this->config = $default;
            }	
        }
        return true;
    }

    private function parseList($content){
        foreach(explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v){
            $v = trim($v);
            if($v == ""){
                continue;
            }
            $this->config[$v] = true;
        }
    }

    public function set($k, $v = true){
        $this->config[$k] = $v;
    }

    public function remove($k){
        unset($this->config[$k]);
    }

    public function save()
    {
        if($this->correct === true)
        {
            $content = implode("\r\n", array_keys($this->config));
            @file_put_contents($this->file, $content, LOCK_EX);
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isSubnetBanned($ip){
        if($this->exists($ip, true)){
            return true;
        }
        else
        {
            return false;
        }
    }

    public function exists($k, $lowercase = false){
    if($lowercase === true):
        $k = strtolower($k);
        $array = array_change_key_case($this->config, CASE_LOWER);
        return isset($array[$k]);
    else:
                return isset($this->config[$k]);
    endif;
    }
        
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		  switch($command->getName()){
			case "subban":
				if (!isset($args[0])){
					return false;
				}else{
					switch($args[0]){
						case "pardon":
				         if (!isset($args[1])){
					         $sender->sendMessage("§aPlease use /subban pradon <IP> unbanning of IP");
					         return true;
				         }
                         $ip = strtolower($args[1]);
                         $this->remove($this->subnetIP($ip));
                         $this->save();
                         $sender->sendMessage("IP \"$ip\" removed from Subnet Ban list");
						 return true;
						case "ban":
				         if (!isset($args[1])){
					         $sender->sendMessage("§aPlease use /subban ban <IP> for blocking IP");
					         return true;
				         }
                         $ip = $args[1];
                         $player = $this->getServer()->getPlayer($ip);
                         if($player instanceof Player){
                            $ip = $player->getAddress();
                            $player->close("", "§cBanned!");
                         }
                         $this->set($this->subnetIP($ip));
                         $this->save();
                         $sender->sendMessage("§aIP \"$ip\" added to Subnet Ban list");
						 return true;
						case "reload":
                         $this->subnetBanned = new Config($this->getDataFolder()."subnetBanned.txt", Config::ENUM);
                         $this->load($this->getDataFolder()."subnetBanned.txt");
                         $sender->sendMessage("§aReloaded Subnet Ban List list");
						 return true;
						case "list":
						 $sender->sendMessage("§aIP ban list: ". implode(", ", $this->getAll(true)));
						 return true;
						default:
						 return false;
					}
				}
			return true;
		}
    }
    
    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority HIGHEST
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        if($this->isSubnetBanned($this->subnetIP($event->getPlayer()->getAddress())))
        {
            $event->setKickMessage("You have been banned");
            $event->setCancelled();
        }
    }
}
