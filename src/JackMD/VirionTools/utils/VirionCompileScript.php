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

use FilesystemIterator;
use Generator;
use Phar;
use PharException;
use PharFileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;

class VirionCompileScript
{

	/*
	 * Note:
	 *
	 * This file is an edited version of DevTools ConsoleScript.
	 *
	 * Kudos to the creator/maintainer of that plugin.
	 */

	/** @var string */
	public const VIRION_STUB = '<?php require("phar://" . __FILE__ . "/%s"); __HALT_COMPILER();';

	/** @var string */
	public const VIRION_STUB_FILE_NAME = 'virion_stub.php';

	/**
	 * @param string $virionYmlPath
	 * @return array|null
	 */
	public static function generateVirionMetadataFromYml(string $virionYmlPath): ?array {
		if (!file_exists($virionYmlPath)) {
			throw new RuntimeException("virion.yml not found. Aborting...");
		}

		$virionYml = yaml_parse_file($virionYmlPath);

		return [
			"compiler" => "VirionTools",
			"name" => $virionYml["name"],
			"version" => $virionYml["version"],
			"antigen" => $virionYml["antigen"],
			"api" => $virionYml["api"],
			"php" => $virionYml["php"] ?? [],
			"description" => $virionYml["description"] ?? "",
			"authors" => $virionYml["authors"] ?? [],
			"creationDate" => time()
		];
	}

	/**
	 * @param string $pharPath
	 * @param string $basePath
	 * @param array $includedPaths
	 * @param array $metadata
	 * @param string $stub
	 * @param int $signatureAlgo
	 * @param int|null $compression
	 * @return Generator
	 * @throws PharException
	 */
	public static function buildVirion(string $pharPath, string $basePath, array $includedPaths, array $metadata, string $stub, int $signatureAlgo = Phar::SHA1, ?int $compression = null): Generator {
		if (file_exists($pharPath)) {
			yield "Phar file already exists, overwriting...";

			Phar::unlinkArchive($pharPath);
		}

		yield "Adding files...";

		$start = microtime(true);
		$phar = new Phar($pharPath);
		$phar->setMetadata($metadata);
		$phar->setStub($stub);
		$phar->setSignatureAlgorithm($signatureAlgo);
		$phar->startBuffering();

		$excludedSubstrings = self::preg_quote_array([realpath($pharPath)], '/');
		$folderPatterns = self::preg_quote_array([
			DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR,
			DIRECTORY_SEPARATOR . '.'
		], '/');
		$basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), '/');

		foreach ($folderPatterns as $p) {
			$excludedSubstrings[] = $basePattern . '.*' . $p;
		}

		$regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
			implode('|', $excludedSubstrings),
			preg_quote($basePath, '/'),
			implode('|', self::preg_quote_array($includedPaths, '/'))
		);

		$directory = new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::CURRENT_AS_PATHNAME);
		$iterator = new RecursiveIteratorIterator($directory);
		$regexIterator = new RegexIterator($iterator, $regex);

		$count = count($phar->buildFromIterator($regexIterator, $basePath));

		yield "Added $count files";

		if ($compression !== null) {
			yield "Checking for compressible files...";
			foreach ($phar as $file => $finfo) {
				/** @var PharFileInfo $finfo */
				if ($finfo->getSize() > (1024 * 512)) {
					yield "Compressing " . $finfo->getFilename();
					$finfo->compress($compression);
				}
			}
		}
		$phar->stopBuffering();

		yield "Done in " . round(microtime(true) - $start, 3) . "s";
	}

	/**
	 * @param array $strings
	 * @param string|null $delim
	 * @return array
	 */
	private static function preg_quote_array(array $strings, string $delim = null): array {
		return array_map(function (string $str) use ($delim): string {
			return preg_quote($str, $delim);
		}, $strings);
	}
}