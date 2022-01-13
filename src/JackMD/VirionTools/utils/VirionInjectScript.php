<?php
declare(strict_types=1);

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

namespace JackMD\VirionTools\utils;

use AssertionError;
use JackMD\VirionTools\VirionTools;
use Phar;
use pocketmine\command\CommandSender;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class VirionInjectScript
{

	/*
	 * Note:
	 *
	 * This file is an updated/edited version of poggit `virion.php` and `virion_stub.php`.
	 * Most of the code is obtained from the aforementioned scripts with necessary changes for VirionTools.
	 *
	 * Kudos to the creator/maintainer of those scripts.
	 */

	/**
	 * @param CommandSender $sender
	 * @param string $virusName
	 * @param Phar $virus
	 * @param string $hostName
	 * @param Phar $host
	 * @return bool
	 */
	public static function virion_infect(CommandSender $sender, string $virusName, Phar $virus, string $hostName, Phar $host): bool {
		//$virus->startBuffering();
		$host->startBuffering();

		/* Check to make sure virion.yml exists in the virion */
		if (!isset($virus["virion.yml"])) {
			$sender->sendMessage(VirionTools::PREFIX . "§cvirion.yml not found in §6$virusName");

			return false;
		}

		$virusPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $virus->getPath()) . "/";
		$virionYml = yaml_parse(file_get_contents($virusPath . "virion.yml"));

		if (!is_array($virionYml)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cCorrupted virion.yml, could not activate virion §6$virusName");

			return false;
		}

		/* Check to make sure plugin.yml exists in the plugin */
		if (!isset($host["plugin.yml"])) {
			$sender->sendMessage(VirionTools::PREFIX . "§cplugin.yml not found in §6$hostName");

			return false;
		}

		$hostPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath()) . "/";
		$pluginYml = yaml_parse(file_get_contents($hostPath . "plugin.yml"));

		if (!is_array($pluginYml)) {
			$sender->sendMessage(VirionTools::PREFIX . "§cCorrupted plugin.yml found in plugin §6$hostName");

			return false;
		}

		/* Infection Log. File that keeps all the virions injected into the plugin */
		$infectionLog = isset($host["virus-infections.json"]) ? json_decode(file_get_contents($hostPath . "virus-infections.json"), true) : [];

		/* Virion injection process now starts */

		$genus = $virionYml["name"];
		$antigen = $virionYml["antigen"];

		foreach ($infectionLog as $old) {
			if ($old["antigen"] === $antigen) {
				$sender->sendMessage(VirionTools::PREFIX . "§cPlugin §6$hostName §cis already infected with §d$virusName");

				return false;
			}
		}

		$antibody = self::getPrefix($pluginYml) . $antigen;
		$infectionLog[$antibody] = $virionYml;

		$sender->sendMessage(VirionTools::PREFIX . "§aUsing antibody §2$antibody §afor virion §d$genus §2($antigen)");

		$hostPharPath = "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $host->getPath());

		$hostChanges = 0;
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($hostPharPath)) as $name => $chromosome) {
			if ($chromosome->isDir()) {
				continue;
			}

			if ($chromosome->getExtension() !== "php") {
				continue;
			}

			$rel = self::cut_prefix($name, $hostPharPath);
			$data = self::change_dna(file_get_contents($name), $antigen, $antibody, $hostChanges);

			if ($data !== "") {
				$host[$rel] = $data;
			}
		}

		$restriction = "src/" . str_replace("\\", "/", $antigen) . "/";
		$ligase = "src/" . str_replace("\\", "/", $antibody) . "/";

		$viralChanges = 0;

		foreach (new RecursiveIteratorIterator($virus) as $name => $genome) {
			if ($genome->isDir()) {
				continue;
			}

			$rel = self::cut_prefix($name, "phar://" . str_replace(DIRECTORY_SEPARATOR, "/", $virus->getPath()) . "/");

			if (str_starts_with($rel, "resources/")) {
				$host[$rel] = file_get_contents($name);
			} elseif (str_starts_with($rel, "src/")) {
				if (!str_starts_with($rel, $restriction)) {
					$sender->sendMessage(VirionTools::PREFIX . "§cWarning: File $rel in virion is not under the antigen $antigen ($restriction)");

					$newRel = $rel;
				} else {
					$newRel = $ligase . self::cut_prefix($rel, $restriction);
				}

				$data = self::change_dna(file_get_contents($name), $antigen, $antibody, $viralChanges);

				$host[$newRel] = $data;
			}
		}

		$host["virus-infections.json"] = json_encode($infectionLog);

		//$virus->stopBuffering();
		$host->stopBuffering();

		$sender->sendMessage(VirionTools::PREFIX . "§aShaded §c$hostChanges §areferences in §6$hostName §aand §c$viralChanges §areferences in §d$virusName.");

		return true;
	}

	/**
	 * @param array $pluginYml
	 * @return string
	 */
	private static function getPrefix(array $pluginYml): string {
		$main = $pluginYml["main"];
		$mainArray = explode("\\", $main);

		array_pop($mainArray);

		$path = implode("\\", $mainArray);
		return $path . "\\libs\\";
	}

	/**
	 * @param string $string
	 * @param string $prefix
	 * @return string
	 */
	private static function cut_prefix(string $string, string $prefix): string {
		if (!str_starts_with($string, $prefix)) {
			throw new AssertionError("\$string does not start with \$prefix:\n$string\n$prefix");
		}

		return substr($string, strlen($prefix));
	}

	/**
	 * @param string $chromosome
	 * @param string $antigen
	 * @param string $antibody
	 * @param int $count
	 * @return string
	 */
	private static function change_dna(string $chromosome, string $antigen, string $antibody, int &$count = 0): string {
		$tokens = token_get_all($chromosome);
		$tokens[] = ""; // should not be valid though

		foreach ($tokens as $offset => $token) {
			if (!is_array($token) or $token[0] !== T_WHITESPACE) {
				list($id, $str, $line) = is_array($token) ? $token : [
					-1,
					$token,
					$line ?? 1
				];

				if (isset($init, $current, $prefixToken)) {
					if ($current === "" && $prefixToken === T_USE and $id === T_FUNCTION || $id === T_CONST) {
					} elseif ($id === T_NS_SEPARATOR || $id === T_STRING) {
						$current .= $str;
					} elseif (!($current === "" && $prefixToken === T_USE and $id === T_FUNCTION || $id === T_CONST)) {
						// end of symbol reference
						if (str_starts_with($current, $antigen)) { // case-sensitive!
							$new = $antibody . substr($current, strlen($antigen));

							for ($o = $init + 1; $o < $offset; ++$o) {
								if ($tokens[$o][0] === T_NS_SEPARATOR || $tokens[$o][0] === T_STRING) {
									$tokens[$o][1] = $new;
									$new = ""; // will write nothing after the first time
								}
							}

							++$count;
						} elseif (stripos($current, $antigen) === 0) {
							throw new RuntimeException("§c[WARNING] Not replacing FQN §2$current §ccase-insensitively.");
						}

						unset($init, $current, $prefixToken);
					}
				} else {
					if ($id === T_NS_SEPARATOR || $id === T_NAMESPACE || $id === T_USE) {
						$init = $offset;
						$current = "";
						$prefixToken = $id;
					}
				}
			}
		}

		$ret = "";
		foreach ($tokens as $token) {
			$ret .= is_array($token) ? $token[1] : $token;
		}

		return $ret;
	}
}