<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib\managers;

use Closure;
use pocketmine\player\Player;
use function array_key_exists;
use function boolval;
use function explode;
use function is_null;
use function is_array;
use function is_string;
use function str_contains;

final class WaterdogManager
{

	/**
	 * @param array $data
	 */
	public function __construct(private array $data)
	{
	}

	/**
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return boolval($this->data["enabled"]);
	}

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function transfer(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if (!$this->isEnabled()) {
			if (!is_null($onFail)) $onFail("Waterdog is not enabled");
			return;
		}

		if (!array_key_exists("settings", $this->data)) {
			if (!is_null($onFail)) $onFail("waterdog settings inside data not found");
			return;
		}

		$settings = $this->data["settings"];
		if (!is_array($settings)) {
			if (!is_null($onFail)) $onFail("waterdog settings is not an array");
			return;
		}

		if (!array_key_exists("lobby", $settings)) {
			if (!is_null($onFail)) $onFail("the lobby address not found in waterdog settings");
			return;
		}

		$lobbyIpAndPort = $settings["lobby"];

		if (!is_string($lobbyIpAndPort)) {
			if (!is_null($onFail)) $onFail("the lobby address that is inside the waterdog settings is not string");
			return;
		}

		if (!str_contains($lobbyIpAndPort, ":")) {
			if (!is_null($onFail)) $onFail("invalid format used for the waterdog lobby address, default: ip:port");
			return;
		}

		$lobbyAddress = explode(":", $lobbyIpAndPort);
		$player->getNetworkSession()->transfer(strval($lobbyAddress[0]), intval(strval($lobbyAddress[1])), "transfered from waterdog");

		if (!is_null($onSuccess)) $onSuccess();
	}
}
