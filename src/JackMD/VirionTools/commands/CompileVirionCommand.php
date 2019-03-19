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

use JackMD\VirionTools\utils\VirionScript;
use JackMD\VirionTools\VirionTools;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;

class CompileVirionCommand extends PluginCommand{

	/** @var VirionTools */
	private $plugin;

	/**
	 * BuildVirionCommand constructor.
	 *
	 * @param VirionTools $plugin
	 * @param string      $name
	 */
	public function __construct(VirionTools $plugin, string $name){
		parent::__construct($name, $plugin);

		$this->setDescription("Compile a virion.phar from a virion.");
		$this->setUsage("/cv [string:virion]");
		$this->setPermission("vt.cmd.cv");
		$this->setAliases(
			[
				"cv",
				"bv",
				"buildvirion"
			]
		);

		$this->plugin = $plugin;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 * @return bool|mixed
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}

		if(!isset($args[0])){
			$sender->sendMessage(VirionTools::PREFIX . "§cUsage: §7/cv [string:virion]");

			return false;
		}

		$virion = (string) $args[0];

		if(!$this->plugin->virionDirectoryExists($virion)){
			$sender->sendMessage(VirionTools::PREFIX . "§cVirion with the name §d" . $virion . " §cwas not found.");
			$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the virion you want to build is located in the virions folder and the virions folder should be located in the folder where PocketMine-MP.phar is located.");

			return false;
		}

		$this->plugin->addFile($virion, "virion.php");
		$this->plugin->addFile($virion, "virion_stub.php");

		$virionDirectory = $this->plugin->getServer()->getDataPath() . "virions" . DIRECTORY_SEPARATOR;

		$pharPath = $this->plugin->getDataFolder() . "builds" . DIRECTORY_SEPARATOR . $virion . ".phar";
		$basePath = $virionDirectory . $virion . "\\";

		$entry = $basePath . VirionScript::VIRION_STUB_FILE_NAME;
		$realEntry = realpath($entry);
		
		if($realEntry === false){
			throw new \RuntimeException("Entry point not found");
		}

		$realEntry = addslashes(str_replace(
			[
				$basePath,
				"\\"
			],

			[
				"",
				"/"
			],

			$realEntry
		));

		$stub = sprintf(VirionScript::VIRION_ENTRY_STUB, $realEntry);
		$metadata = VirionScript::generateVirionMetadataFromYml($basePath . "virion.yml");

		$this->buildVirion($sender, $pharPath, $basePath, [], $metadata, $stub, \Phar::SHA1);

		$sender->sendMessage(VirionTools::PREFIX . "§aPhar virion has been created on §2" . $pharPath);

		return true;
	}

	public function buildVirion(CommandSender $sender, string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = \Phar::SHA1): void{
		foreach(VirionScript::buildVirion($pharPath, $basePath, $includedPaths, $metadata, $stub, $signatureAlgo, $signatureAlgo) as $line){
			$sender->sendMessage(VirionTools::PREFIX . "§a" . $line);
		}
	}
}