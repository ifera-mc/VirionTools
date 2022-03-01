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

use Ifera\VirionTools\utils\VirionCompileScript;
use Ifera\VirionTools\VirionTools;
use Phar;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use Webmozart\PathUtil\Path;
use function file_exists;
use function ini_get;
use function is_array;
use function php_ini_loaded_file;
use function sprintf;
use function unlink;
use function yaml_parse_file;

class CompileVirionCommand extends Command implements PluginOwned {

	public function __construct(private VirionTools $plugin) {
		parent::__construct(
			"compilevirion",
			"Compile a virion.phar from a virion",
			"/cv [string:virion]",
			["cv", "bv", "buildvirion"]
		);

		$this->setPermission("vt.cmd.cv");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if (!$this->testPermission($sender)) return;

		if (ini_get('phar.readonly') !== '0') {
			$sender->sendMessage(VirionTools::PREFIX . "§cThis command requires §4\"phar.readonly\" §cto be set to 0. Set it in §4" . php_ini_loaded_file() . " §cand restart the server.");
			return;
		}

		if (!isset($args[0])) {
			$sender->sendMessage(VirionTools::PREFIX . "§cUsage: §7/cv [string:virion]");
			return;
		}

		$virion = (string) $args[0];

		if (!$this->plugin->virionDirectoryExists($virion)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cVirion with the name §d" . $virion . " §cwas not found.");
			$sender->sendMessage(VirionTools::PREFIX . "§aMake sure that the virion you want to build is located in the virions folder and the virions folder should be located in the folder where PocketMine-MP.phar is located.");
			return;
		}

		$pharPath = Path::join($this->plugin->getDataFolder(), "builds", $virion . ".phar");
		$basePath = Path::join($this->plugin->getServer()->getDataPath(), "virions", $virion);

		if (!file_exists($virionYmlPath = Path::join($basePath, "virion.yml"))) {
			$sender->sendMessage(VirionTools::PREFIX . "§cvirion.yml not found in virion §6$virion");
			return;
		}

		$virionYml = yaml_parse_file($virionYmlPath);

		if (!is_array($virionYml)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cCorrupted virion.yml, could not use virion §6$virion");
			return;
		}

		$checks = ["name", "version"];

		foreach ($checks as $check) {
			if (!isset($virionYml[$check])) {
				$sender->sendMessage(VirionTools::PREFIX . "§cKey §4$check §cis missing in §4virion.yml §cof virion §6$virion");
				return;
			}
		}

		$this->plugin->addFile($virion, "virion.php", true);
		$this->plugin->addFile($virion, "virion_stub.php", true);

		$this->buildVirion(
			$sender,
			$pharPath,
			$basePath,
			[],
			VirionCompileScript::generateVirionMetadataFromYml(Path::join($basePath, "virion.yml")),
			$this->getStub(),
			Phar::SHA1
		);

		$sender->sendMessage(VirionTools::PREFIX . "§aPhar virion has been created on §2" . $pharPath);

		unlink(Path::join($basePath, "virion.php"));
		unlink(Path::join($basePath, "virion_stub.php"));
	}

	public function getOwningPlugin(): VirionTools {
		return $this->plugin;
	}

	private function getStub(): string {
		return sprintf(VirionCompileScript::VIRION_STUB, VirionCompileScript::VIRION_STUB_FILE_NAME);
	}

	private function buildVirion(CommandSender $sender, string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = Phar::SHA1): void {
		foreach (VirionCompileScript::buildVirion($pharPath, $basePath, $includedPaths, $metadata, $stub, $signatureAlgo, $signatureAlgo) as $line) {
			$sender->sendMessage(VirionTools::PREFIX . "§a" . $line);
		}
	}
}