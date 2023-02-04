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
 * Copyright (c) 2018 Ifera  < https://github.com/Ifera >
 *
 * Discord: ifera#3717
 * Twitter: ifera_tr
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

namespace Ifera\VirionTools\commands;

use Ifera\VirionTools\utils\VirionInjectScript;
use Ifera\VirionTools\VirionTools;
use Phar;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use Symfony\Component\Filesystem\Path;
use function microtime;
use function round;
use function strpos;

class InjectVirionCommand extends Command implements PluginOwned {

	public function __construct(private VirionTools $plugin) {
		parent::__construct(
			"injectvirion",
			"Inject a virion.phar into a plugin.phar",
			"injectvirion <string:virion> <string:plugin>",
			["iv"]
		);

		$this->setPermission("vt.cmd.iv");
	}

	public function getOwningPlugin(): VirionTools {
		return $this->plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if (!$this->testPermission($sender)) return;

		if ((!isset($args[0])) || (!isset($args[1]))) {
			$sender->sendMessage(VirionTools::PREFIX . "§cUsage: §7/injectvirion [string:virion] [string:plugin]");
			return;
		}

		$virion = (string) $args[0];
		$plugin = (string) $args[1];

		if (strpos($virion, ".phar") === false) $virion = $virion . ".phar";
		if (strpos($plugin, ".phar") === false) $plugin = $plugin . ".phar";

		$pluginDirectory = Path::join($this->plugin->getDataFolder(), "plugins");
		$virionDirectory = Path::join($this->plugin->getDataFolder(), "builds");

		if (!$this->plugin->virionPharExists($virion)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cVirion with the name §d" . $virion . " §cwas not found.");
			$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the virion you want to inject is located in §2plugin_data\VirionTools\builds.");
			return;
		}

		if (!$this->plugin->pluginPharExists($plugin)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cPhar plugin with the name §d" . $plugin . " §cwas not found.");
			$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the phared plugin, to which the virion is to be injected in, is located in §2plugin_data\VirionTools\plugins.");
			return;
		}

		$start = microtime(true);
		$sender->sendMessage(VirionTools::PREFIX . "§aInitiating virion injection process");

		$virus = new Phar(Path::join($virionDirectory, $virion));
		$host = new Phar(Path::join($pluginDirectory, $plugin));

		if (VirionInjectScript::virion_infect($sender, $virion, $virus, $plugin, $host)) {
			$sender->sendMessage(VirionTools::PREFIX . "§aDone in " . round(microtime(true) - $start, 3) . "s");
			$sender->sendMessage(VirionTools::PREFIX . "§aVirion §d$virion §asuccessfully injected in plugin §6$plugin");
		}
	}
}