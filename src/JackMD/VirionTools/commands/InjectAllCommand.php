<?php
declare(strict_types = 1);

/**
 *  _    _ _      _           _____           _
 * | |  | (_)    (_)         |_   _|         | |
 * | |  | |_ _ __ _  ___  _ __ | | ___   ___ | |___
 * | |  | | | '__| |/ _ \| '_ \| |/ _ \ / _ \| / __|
 *  \ \_/ / | |  | | (_) | | | | | (_) | (_) | \__ \
 *   \___/|_|_|  |_|\___/|_| |_\_/\___/ \___/|_|___/
 *
 * VirionTools, a VirionTools plugin like DevTools for PocketMine-MP.
 * Copyright (c) 2018 JackMD  < https://github.com/JackMD >
 *
 * Discord: JackMD#3717
 * Twitter: JackMTaylor_
 *
 * This software is distributed under "GNU General Public License v3.0".
 * This license allows you to use it and/or modify it but you are not at
 * all allowed to sell this plugin at any cost. If found doing so the
 * necessary action required would be taken.
 *
 * VirionTools is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License v3.0 for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 * ------------------------------------------------------------------------
 */

namespace JackMD\VirionTools\commands;

use JackMD\VirionTools\utils\VirionInjectScript;
use JackMD\VirionTools\VirionTools;
use Phar;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use function file_get_contents;
use function is_array;
use function microtime;
use function round;
use function str_replace;
use function strpos;
use function yaml_parse;
use const DIRECTORY_SEPARATOR;

class InjectAllCommand extends PluginCommand{

	/** @var VirionTools */
	private $plugin;

	/**
	 * InjectAllCommand constructor.
	 *
	 * @param VirionTools $plugin
	 */
	public function __construct(VirionTools $plugin){
		parent::__construct("injectall", $plugin);

		$this->setDescription("Inject all virions in the plugin using a single command.");
		$this->setUsage("/injectall [string:plugin]");
		$this->setPermission("vt.cmd.id");
		$this->setAliases(
			[
				"id",
				"ia"
			]
		);

		$this->plugin = $plugin;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		if(!$this->testPermission($sender)){
			return;
		}

		if(!isset($args[0])){
			$sender->sendMessage(VirionTools::PREFIX . "§cUsage: §7/ia [string:plugin]");

			return;
		}

		$plugin = (string) $args[0];

		if(strpos($plugin, ".phar") === false){
			$plugin = $plugin . ".phar";
		}

		if(!$this->plugin->pluginPharExists($plugin)){
			$sender->sendMessage(VirionTools::PREFIX . "§cPhar plugin with the name §d" . $plugin . " §cwas not found.");
			$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the phared plugin, to which the virion is to be injected in, is located in §2plugin_data\VirionTools\plugins.");

			return;
		}

		$pluginDirectory = $this->plugin->getDataFolder() . "plugins" . DIRECTORY_SEPARATOR;
		$virionDirectory = $this->plugin->getDataFolder() . "builds" . DIRECTORY_SEPARATOR;

		$hostPath = $pluginDirectory . $plugin;

		$host = new Phar($hostPath);
		$host->startBuffering();

		$hostPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath()) . "/";

		if(!isset($host["plugin.yml"])){
			$sender->sendMessage(VirionTools::PREFIX . "§4plugin.yml §cnot found in plugin §6{$plugin}§c. Aborting");

			return;
		}

		$pluginYml = yaml_parse(file_get_contents($hostPath . "plugin.yml"));

		if(!is_array($pluginYml)){
			$sender->sendMessage(VirionTools::PREFIX . "§cCorrupted plugin.yml found in plugin §6$plugin");

			return;
		}

		if(!isset($pluginYml["virions"])){
			$sender->sendMessage(VirionTools::PREFIX . "§4virions §ckey not found in plugin.yml of §6{$plugin}§c. Aborting.");

			return;
		}

		$start = microtime(true);
		$count = 0;

		$sender->sendMessage(VirionTools::PREFIX . "§aInitiating virion injection process");

		$virions = $pluginYml["virions"];

		foreach($virions as $virion){
			if(strpos($virion, ".phar") === false){
				$virion = $virion . ".phar";
			}

			if(!$this->plugin->virionPharExists($virion)){
				$sender->sendMessage(VirionTools::PREFIX . "§cVirion with the name §d" . $virion . " §cwas not found.");
				$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the virion you want to inject is located in §2plugin_data\VirionTools\builds.");

				continue;
			}

			$virus = new Phar($virionDirectory . $virion);

			if(VirionInjectScript::virion_infect($sender, $virion, $virus, $plugin, $host)){
				$sender->sendMessage(VirionTools::PREFIX . "§aVirion §d$virion §asuccessfully injected in plugin §6$plugin");

				$count++;
			}
		}

		$host->stopBuffering();

		$sender->sendMessage(VirionTools::PREFIX . "§aDone in §e" . round(microtime(true) - $start, 3) . "s");
		$sender->sendMessage(VirionTools::PREFIX . "§aPlugin §6$plugin §ainfected with §d$count virion(s) §asuccessfully.");
	}
}