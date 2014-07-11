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
        
    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        $output = "";
        if(strtolower($command->getName()) === "subban"){
            switch($args[0]){
                case "pardon":
                case "remove":
                    $ip = strtolower($args[1]);
                    $this->remove($this->subnetIP($ip));
                    $this->save();
                    $output .= "IP \"$ip\" removed from Subnet Ban list\n";
                    break;
                case "add":
                case "ban":
                    $ip = strtolower($args[1]);
                    $player = $this->getServer()->getPlayer($ip);
                    if($player instanceof Player){
                        $ip = $player->getAddress();
                        $player->kick("Banned");
                    }
                    $this->set($this->subnetIP($ip));
                    $this->save();
                    $output .= "IP \"$ip\" added to Subnet Ban list\n";
                    break;
                case "reload":
                    $this->subnetBanned = new Config($this->getDataFolder()."subnetBanned.txt", Config::ENUM);
                    $this->load($this->getDataFolder()."subnetBanned.txt");
                    $output .= "Reloaded Subnet Ban List list\n";
                    break;
                case "list":
                    $output .= "IP ban list: ". implode(", ", $this->getAll(true)) ."\n";
                    break;
            }
            echo $output;
            return true;
        }
        return false;
    }
    
    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority HIGHEST
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        if($this->isSubnetBanned($this->subnetIP($event->getPlayer()->getAddress())))
        {
            $event->getPlayer()->kick("You have been banned");
        }
    }
}
