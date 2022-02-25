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

namespace JackMD\VirionTools;

use JackMD\VirionTools\commands\CompileVirionCommand;
use JackMD\VirionTools\commands\InjectAllCommand;
use JackMD\VirionTools\commands\InjectVirionCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use Webmozart\PathUtil\Path;
use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function is_dir;
use function mkdir;
use function stream_copy_to_stream;
use function trim;

class VirionTools extends PluginBase {

	public const PREFIX = "§2[§6Virion§eTools§2]§r ";

	protected function onLoad(): void {
		if (!is_dir($builds = Path::join($this->getDataFolder(), "builds"))) mkdir($builds);
		if (!is_dir($plugins = Path::join($this->getDataFolder(), "plugins"))) mkdir($plugins);

		$this->saveResource(Path::join("data", "virion.php"), true);
		$this->saveResource(Path::join("data", "virion_stub.php"), true);
	}

	protected function onEnable(): void {
		$commands = [
			new CompileVirionCommand($this),
			new InjectVirionCommand($this),
			new InjectAllCommand($this)
		];

		foreach ($commands as $command) {
			$this->getServer()->getCommandMap()->register("viriontools", $command);
		}
	}

	public function virionDirectoryExists(string $virionName): bool {
		return is_dir(Path::join($this->getServer()->getDataPath(), "virions", $virionName));
	}

	public function virionPharExists(string $virionName): bool {
		return file_exists(Path::join($this->getDataFolder(), "builds", $virionName));
	}

	public function pluginPharExists(string $pluginName): bool {
		return file_exists(Path::join($this->getDataFolder(), "plugins", $pluginName));
	}

	public function addFile(string $virion, string $filename, bool $replace = false): bool {
		if (trim($filename) === "") return false;
		if (($resource = $this->getResource(Path::join("data", $filename))) === null) return false;

		$out = Path::join($this->getServer()->getDataPath(), "virions", $virion, $filename);

		if (!file_exists(dirname($out))) mkdir(dirname($out), 0755, true);
		if (file_exists($out) && !$replace) return false;

		$fp = fopen($out, "wb");
		if ($fp === false) throw new AssumptionFailedError("fopen() should not fail with wb flags");

		$ret = stream_copy_to_stream($resource, $fp) > 0;
		fclose($fp);
		fclose($resource);

		return $ret;
	}
}